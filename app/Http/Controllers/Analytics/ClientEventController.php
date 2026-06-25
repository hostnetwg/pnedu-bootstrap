<?php

namespace App\Http\Controllers\Analytics;

use App\Enums\Analytics\AnalyticsEventName;
use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContextService;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\AnalyticsSessionService;
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
    /**
     * Dozwolone wartości metadata.section_key (sekcje formularza). Tylko whitelist — nigdy tekst z DOM.
     *
     * @var list<string>
     */
    private const ALLOWED_SECTION_KEYS = [
        'buyer_data',
        'recipient_data',
        'participants',
        'payment_method',
        'invoice',
        'consents',
        'summary',
    ];

    /**
     * Dozwolone wartości metadata.cta_key (ważne akcje). Tylko whitelist.
     *
     * @var list<string>
     */
    private const ALLOWED_CTA_KEYS = [
        'add_participant',
        'remove_participant',
        'select_online_payment',
        'select_deferred_invoice',
        'back_to_course',
        'submit_order',
    ];

    /**
     * Dozwolone wartości metadata.trigger (powód wysłania eventu).
     *
     * @var list<string>
     */
    private const ALLOWED_TRIGGERS = [
        'first_interaction',
        'field_change',
        'section_click',
        'payment_select',
        'cta_click',
        'page_focus',
    ];

    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly AnalyticsContextService $context,
        private readonly AnalyticsSessionService $sessionService,
        private readonly OrderFormSessionService $orderFormSessionService,
        private readonly FunnelSkipService $funnelSkip,
        private readonly MarketingBotDetector $botDetector,
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
            $orderFormSessionId = $this->orderFormSessionService->id($request, $courseId);
            $context = $this->context->fromRequest($request);
            $priceVariantId = $this->positiveInt($request->input('price_variant_id'));

            foreach ($events as $event) {
                $this->trackSingleEvent(
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
        array $event,
        int $courseId,
        ?int $priceVariantId,
        ?string $analyticsSessionId,
        ?string $orderFormSessionId,
        array $context,
    ): void {
        $eventName = is_string($event['event_name'] ?? null) ? $event['event_name'] : null;

        // Z przeglądarki akceptujemy tylko 4 jawnie dozwolone eventy JS.
        if ($eventName === null || ! AnalyticsEventName::isClientJsEvent($eventName)) {
            return;
        }

        $eventEnum = AnalyticsEventName::from($eventName);

        $metadata = $this->buildMetadata($eventEnum, $event, $priceVariantId);
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
    private function buildMetadata(AnalyticsEventName $eventEnum, array $event, ?int $priceVariantId): ?array
    {
        $metadata = [];

        if ($priceVariantId !== null) {
            $metadata['price_variant_id'] = $priceVariantId;
            $metadata['has_price_variant'] = true;
        }

        $trigger = $this->whitelisted($event['trigger'] ?? null, self::ALLOWED_TRIGGERS);
        if ($trigger !== null) {
            $metadata['trigger'] = $trigger;
        }

        if ($eventEnum === AnalyticsEventName::OrderFormSectionInteracted) {
            $section = $this->whitelisted($event['section_key'] ?? null, self::ALLOWED_SECTION_KEYS);
            if ($section === null) {
                return null;
            }
            $metadata['section_key'] = $section;
        }

        if ($eventEnum === AnalyticsEventName::OrderFormCtaClicked) {
            $cta = $this->whitelisted($event['cta_key'] ?? null, self::ALLOWED_CTA_KEYS);
            if ($cta === null) {
                return null;
            }
            $metadata['cta_key'] = $cta;
        }

        return $metadata;
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
