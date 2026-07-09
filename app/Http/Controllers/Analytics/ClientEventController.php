<?php

namespace App\Http\Controllers\Analytics;

use App\Enums\Analytics\AnalyticsEventName;
use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContextService;
use App\Services\Analytics\AnalyticsEventContract;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\AnalyticsSessionService;
use App\Services\Analytics\OrderFormAttributionService;
use App\Services\Analytics\OrderFormSessionService;
use App\Services\FunnelSkipService;
use App\Services\MarketingBotDetector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Etap B1 — publiczny endpoint przyjmujący batch eventów JS z formularza zamówienia.
 *
 * Zasady (kontrakt bezpieczeństwa):
 *  - fail-silent: endpoint NIGDY nie zwraca błędu blokującego użytkownika (zawsze 204),
 *  - RODO: zapisujemy wyłącznie techniczne klucze z whitelisty, nigdy wartości pól ani tekstu z DOM,
 *  - tryby analityki decydują czy event w ogóle trafi do kolejki (AnalyticsService::track),
 *  - czas zdarzenia bierzemy z serwera (occurred_at_client jest ignorowane).
 */
class ClientEventController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly AnalyticsContextService $context,
        private readonly AnalyticsSessionService $sessionService,
        private readonly OrderFormSessionService $orderFormSessionService,
        private readonly FunnelSkipService $funnelSkip,
        private readonly MarketingBotDetector $botDetector,
        private readonly OrderFormAttributionService $formAttribution,
    ) {}

    public function store(Request $request): Response
    {
        // Neutralna odpowiedź zawsze 204 — analityka nie może wpływać na klienta.
        $noContent = response()->noContent();

        try {
            if (! config('analytics.enabled', true)) {
                return $noContent;
            }

            // Same-origin guard dla trasy CSRF-exempt: odrzucamy ciche żądania z obcego hosta.
            if ($this->isCrossOriginRequest($request)) {
                return $noContent;
            }

            // Ruch wewnętrzny (admin) oraz boty/preview pomijamy w całości.
            if ($this->funnelSkip->shouldSkipAnalytics($request)
                || $this->funnelSkip->shouldSkipTracking($request)
                || $this->botDetector->isBotOrPreviewCrawler($request)) {
                return $noContent;
            }

            // Limit rozmiaru payloadu — odrzucamy cały batch po przekroczeniu.
            $maxBytes = max(1, (int) config('analytics.client_events.max_payload_bytes', 10240));
            if (strlen((string) $request->getContent()) > $maxBytes) {
                return $noContent;
            }

            $courseId = $this->positiveInt($request->input('course_id'));
            if ($courseId === null) {
                return $noContent;
            }

            $events = $request->input('events');
            if (! is_array($events) || $events === []) {
                return $noContent;
            }

            // Limit liczby eventów — bierzemy maks. N pierwszych, resztę ignorujemy.
            $maxEvents = max(1, (int) config('analytics.client_events.max_events_per_batch', 20));
            $events = array_slice(array_values($events), 0, $maxEvents);

            $analyticsSessionId = $this->sessionService->id($request);
            $preferredFormSessionId = $this->clientUuidOrNull($request->input('form_session_id'));
            $orderFormSessionId = $this->orderFormSessionService->id($request, $courseId, $preferredFormSessionId);
            $context = $this->context->fromRequest($request);
            $priceVariantId = $this->positiveInt($request->input('price_variant_id'));

            foreach ($events as $event) {
                $this->trackSingleEvent(
                    $request,
                    is_array($event) ? $event : [],
                    $courseId,
                    $priceVariantId,
                    $analyticsSessionId,
                    $orderFormSessionId,
                    $context,
                );
            }

            // Utrzymujemy spójność sesji między kolejnymi batchami (jeśli cookie jeszcze nie istniało).
            $this->sessionService->appendCookie($noContent, $request);
            $this->orderFormSessionService->appendCookie($noContent, $request, $courseId);

            return $noContent;
        } catch (Throwable) {
            // Cokolwiek się stanie — nie wolno zwrócić błędu do klienta.
            return $noContent;
        }
    }

    private function trackSingleEvent(
        Request $request,
        array $event,
        int $courseId,
        ?int $priceVariantId,
        ?string $analyticsSessionId,
        ?string $orderFormSessionId,
        array $context,
    ): void {
        $eventName = is_string($event['event_name'] ?? null) ? $event['event_name'] : null;

        // Z przeglądarki akceptujemy wyłącznie jawnie dozwolone eventy z kontraktu.
        if ($eventName === null || ! AnalyticsEventContract::isClientEvent($eventName)) {
            return;
        }

        $eventEnum = AnalyticsEventName::from($eventName);

        $metadata = $this->buildMetadata($request, $eventEnum, $event, $priceVariantId, $orderFormSessionId);
        if ($metadata === null) {
            // Event wymagał klucza spoza whitelisty (np. nieznana sekcja/CTA) — pomijamy.
            return;
        }

        // Klientowski UUID jest TYLKO seedem deduplikacji — nigdy finalnym, globalnym event_uuid.
        // Serwer buduje deterministyczny, namespacowany event_uuid (sesja + nazwa eventu + seed).
        $clientEventUuid = $this->clientUuidOrNull($event['event_uuid'] ?? null);
        $eventUuid = $clientEventUuid !== null
            ? $this->namespacedEventUuid($orderFormSessionId, $eventEnum->value, $clientEventUuid)
            : null; // null => AnalyticsService wygeneruje serwerowy event_uuid

        $payload = array_merge($context, [
            'course_id' => $courseId,
            'analytics_session_id' => $analyticsSessionId,
            'order_form_session_id' => $orderFormSessionId,
            'event_uuid' => $eventUuid,
            'metadata' => $metadata,
        ]);

        // AnalyticsService sam egzekwuje tryb analityki i sanitizację; tu tylko dostarczamy bezpieczny payload.
        $this->analytics->track($eventEnum, $payload);
    }

    /**
     * Buduje bezpieczną metadata wyłącznie z whitelistowanych kluczy technicznych.
     * Zwraca null, gdy event wymaga klucza, którego nie ma na whiteliście (sygnał: pomiń event).
     *
     * @return array<string, mixed>|null
     */
    private function buildMetadata(Request $request, AnalyticsEventName $eventEnum, array $event, ?int $priceVariantId, ?string $formSessionId): ?array
    {
        $metadata = [
            'tracking_schema_version' => AnalyticsEventContract::SCHEMA_VERSION,
        ];

        if ($formSessionId !== null) {
            $metadata['form_session_id'] = $formSessionId;
        }

        if ($priceVariantId !== null) {
            $metadata['price_variant_id'] = $priceVariantId;
            $metadata['has_price_variant'] = true;
        }

        $metadata = array_merge($metadata, $this->formAttribution->reportingMetadata($request));

        $trigger = $this->whitelisted($event['trigger'] ?? null, AnalyticsEventContract::TRIGGERS);
        if ($trigger !== null) {
            $metadata['trigger'] = $trigger;
        }

        if ($eventEnum === AnalyticsEventName::OrderFormCtaClicked) {
            $cta = $this->whitelisted($event['cta_key'] ?? null, AnalyticsEventContract::CTA_KEYS);
            if ($cta === null) {
                return null;
            }
            $metadata['cta_key'] = $cta;
        }

        foreach (AnalyticsEventContract::allowedPropertiesFor($eventEnum->value) as $property) {
            $value = $this->safePropertyValue($property, $event[$property] ?? null);

            if ($value !== null && $value !== []) {
                $metadata[$property] = $value;
            }
        }

        if (in_array($eventEnum, [
            AnalyticsEventName::OrderFormSectionInteracted,
            AnalyticsEventName::FormSectionViewed,
            AnalyticsEventName::FormSectionStarted,
            AnalyticsEventName::FormSectionCompleted,
            AnalyticsEventName::GusLookupClicked,
            AnalyticsEventName::GusDataApplied,
            AnalyticsEventName::FormFieldEditedAfterGus,
            AnalyticsEventName::GusManualFallbackStarted,
        ], true) && ! isset($metadata['section_key'])) {
            return null;
        }

        return $metadata;
    }

    private function safePropertyValue(string $property, mixed $value): mixed
    {
        return match ($property) {
            'section_key', 'first_section_key', 'first_error_section', 'last_section_key' => $this->whitelisted($value, AnalyticsEventContract::SECTION_KEYS),
            'field_key', 'first_field_key', 'first_error_field', 'last_field_key' => $this->whitelisted($value, AnalyticsEventContract::FIELD_KEYS),
            'field_type' => $this->whitelisted($value, AnalyticsEventContract::FIELD_TYPES),
            'source' => $this->whitelisted($value, AnalyticsEventContract::FIELD_SOURCES),
            'trigger' => $this->whitelisted($value, AnalyticsEventContract::TRIGGERS),
            'has_value' => is_bool($value) ? $value : null,
            'seconds_from_page_load',
            'required_fields_count',
            'completed_fields_count',
            'completed_sections_count',
            'visible_validation_errors_count',
            'errors_count' => is_numeric($value) ? max(0, (int) $value) : null,
            'error_sections' => $this->whitelistedList($value, AnalyticsEventContract::SECTION_KEYS),
            'error_fields' => $this->whitelistedList($value, AnalyticsEventContract::FIELD_KEYS),
            'validation_error_codes' => $this->technicalList($value),
            'selected_payment_method' => $this->whitelisted($value, ['deferred_invoice', 'online_payment', 'deferred', 'online']),
            'first_interaction_type' => $this->whitelisted($value, ['focus', 'click', 'change', 'input', 'unknown']),
            'last_activity_type' => $this->whitelisted($value, AnalyticsEventContract::LAST_ACTIVITY_TYPES),
            'last_event_name' => is_string($value) && AnalyticsEventContract::isClientEvent($value) ? $value : null,
            'target', 'gus_target' => $this->whitelisted($value, AnalyticsEventContract::GUS_TARGETS),
            'nip_present', 'nip_format_valid_client', 'retry_possible' => is_bool($value) ? $value : null,
            'latency_ms',
            'fields_returned_count',
            'fields_applied_count',
            'overwritten_manual_fields_count',
            'http_status',
            'seconds_after_gus_success',
            'seconds_after_gus_error' => is_numeric($value) ? max(0, (int) $value) : null,
            'response_source' => $this->whitelisted($value, AnalyticsEventContract::GUS_RESPONSE_SOURCES),
            'result_type' => $this->whitelisted($value, AnalyticsEventContract::GUS_RESULT_TYPES),
            'error_type' => $this->whitelisted($value, AnalyticsEventContract::GUS_ERROR_TYPES),
            'started_at' => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T/', $value) === 1 ? $value : null,
            default => null,
        };
    }

    /**
     * @param  list<string>  $whitelist
     */
    private function whitelisted(mixed $value, array $whitelist): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return in_array($value, $whitelist, true) ? $value : null;
    }

    /**
     * @param  list<string>  $whitelist
     * @return list<string>
     */
    private function whitelistedList(mixed $value, array $whitelist): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $item): ?string => $this->whitelisted($item, $whitelist),
            $value
        ))));
    }

    /**
     * @return list<string>
     */
    private function technicalList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $values = [];

        foreach ($value as $item) {
            if (! is_string($item) && ! is_int($item)) {
                continue;
            }

            $normalized = strtolower(trim((string) $item));
            if ($normalized !== '' && preg_match('/^[a-z0-9_.:-]{1,80}$/', $normalized) === 1) {
                $values[] = $normalized;
            }
        }

        return array_values(array_unique($values));
    }

    private function clientUuidOrNull(mixed $value): ?string
    {
        // UUID od klienta akceptujemy tylko gdy ma poprawny format (seed deduplikacji); inaczej null.
        return is_string($value) && Str::isUuid($value) ? $value : null;
    }

    /**
     * Buduje deterministyczny, namespacowany event_uuid z seedu klienta.
     *
     * UUIDv5 (NAMESPACE_URL) zwraca poprawny, 36-znakowy UUID, który mieści się w kolumnie
     * `analytics_events.event_uuid` (typ uuid / char(36), unique). Namespacowanie sesją formularza
     * i nazwą eventu gwarantuje:
     *  - dedup: ten sam seed + ta sama sesja + ten sam event_name => ten sam event_uuid,
     *  - brak kolizji między różnymi sesjami formularza,
     *  - brak kolizji między różnymi event_name.
     */
    private function namespacedEventUuid(?string $orderFormSessionId, string $eventName, string $clientEventUuid): string
    {
        $seed = sprintf('client_js|%s|%s|%s', $orderFormSessionId ?? '', $eventName, $clientEventUuid);

        return Uuid::uuid5(Uuid::NAMESPACE_URL, $seed)->toString();
    }

    /**
     * Lekki same-origin guard dla trasy CSRF-exempt (wspierającej sendBeacon).
     *
     * Reguła (porównanie po HOŚCIE, nie po pełnym URL; bez logowania URL-i/referrerów):
     *  - jest Origin i host != host aplikacji => cross-origin (blokuj),
     *  - brak Origin, jest Referer i host != host aplikacji => cross-origin (blokuj),
     *  - oba puste => best-effort, nie blokuj (false).
     */
    private function isCrossOriginRequest(Request $request): bool
    {
        $appHost = $request->getHost();

        $origin = (string) $request->headers->get('Origin', '');
        if ($origin !== '') {
            return ! $this->hostMatches($origin, $appHost);
        }

        $referer = (string) $request->headers->get('Referer', '');
        if ($referer !== '') {
            return ! $this->hostMatches($referer, $appHost);
        }

        return false;
    }

    private function hostMatches(string $url, string $appHost): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' && strcasecmp($host, $appHost) === 0;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
