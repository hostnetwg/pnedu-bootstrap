<?php

namespace App\Services\Analytics;

use App\Models\Analytics\OrderFormAttribution;
use App\Services\MarketingAttributionService;
use App\Services\OrderEntryPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderFormAttributionService
{
    public const SESSION_KEY = 'marketing.form_attribution';

    public function __construct(
        private readonly TrafficChannelClassifier $classifier,
        private readonly MarketingAttributionService $marketingAttribution,
        private readonly OrderEntryPlacementService $placement,
    ) {}

    public function captureFromRequest(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $signals = $this->buildSignals($request);
        $classified = $this->classifier->classify($signals);
        $touch = $this->buildTouchRecord($request, $signals, $classified);
        $state = $this->getState($request);

        $state['current'] = $touch;
        $state['fbclid_present'] = (bool) ($signals['fbclid_present'] ?? false);
        $state['gclid_present'] = (bool) ($signals['gclid_present'] ?? false);

        $channel = (string) ($classified['channel'] ?? 'unknown');

        if ($channel === 'internal_site') {
            $this->applyInternalTouch($request, $state, $touch);
        } elseif ($channel === 'direct') {
            $this->applyDirectTouch($state, $touch);
        } else {
            $this->applyExternalTouch($state, $touch);
        }

        $state['reporting'] = $this->buildReportingFields($state);
        $this->saveState($request, $state);
    }

    public function persistForFormSession(Request $request, string $formSessionId, int $courseId, ?int $priceVariantId = null): ?OrderFormAttribution
    {
        if ($formSessionId === '') {
            return null;
        }

        $this->captureFromRequest($request);
        $state = $this->getState($request);
        if ($state === []) {
            return null;
        }

        $attributes = $this->stateToPersistenceAttributes($state, $formSessionId, $courseId, $priceVariantId);

        return OrderFormAttribution::query()->updateOrCreate(
            ['form_session_id' => $formSessionId],
            $attributes,
        );
    }

    public function findByFormSessionId(string $formSessionId): ?OrderFormAttribution
    {
        if ($formSessionId === '') {
            return null;
        }

        return OrderFormAttribution::query()->where('form_session_id', $formSessionId)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function reportingMetadata(Request $request): array
    {
        $this->captureFromRequest($request);
        $state = $this->getState($request);
        $reporting = is_array($state['reporting'] ?? null) ? $state['reporting'] : $this->buildReportingFields($state);

        return array_merge($reporting, [
            'fbclid_present' => (bool) ($state['fbclid_present'] ?? false),
            'gclid_present' => (bool) ($state['gclid_present'] ?? false),
            'referrer_domain' => $state['current']['referrer_domain'] ?? null,
            'internal_promo_touched' => (bool) ($state['internal_promo_touched'] ?? false),
            'first_touch_channel' => $state['first']['channel'] ?? null,
            'last_external_touch_channel' => $state['last_external']['channel'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fullSnapshotMetadata(Request $request): array
    {
        $this->captureFromRequest($request);
        $state = $this->getState($request);
        $reporting = is_array($state['reporting'] ?? null) ? $state['reporting'] : $this->buildReportingFields($state);

        return array_merge(
            array_merge($reporting, [
                'fbclid_present' => (bool) ($state['fbclid_present'] ?? false),
                'gclid_present' => (bool) ($state['gclid_present'] ?? false),
                'referrer_domain' => $state['current']['referrer_domain'] ?? null,
                'internal_promo_touched' => (bool) ($state['internal_promo_touched'] ?? false),
                'first_touch_channel' => $state['first']['channel'] ?? null,
                'last_external_touch_channel' => $state['last_external']['channel'] ?? null,
            ]),
            $this->touchMetadata('current', $state['current'] ?? null),
            $this->touchMetadata('first_touch', $state['first'] ?? null),
            $this->touchMetadata('last_touch', $state['last'] ?? null),
            $this->touchMetadata('last_external_touch', $state['last_external'] ?? null),
            $this->internalTouchMetadata($state),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function orderCreatedSnapshot(?string $formSessionId, Request $request): array
    {
        $record = $formSessionId !== null && $formSessionId !== ''
            ? $this->findByFormSessionId($formSessionId)
            : null;

        if ($record !== null) {
            return [
                'traffic_channel' => $record->traffic_channel,
                'traffic_source' => $record->traffic_source,
                'traffic_medium' => $record->traffic_medium,
                'traffic_campaign' => $record->traffic_campaign,
                'attribution_source' => $record->attribution_source,
                'conversion_reporting_channel' => $record->conversion_reporting_channel,
                'first_touch_channel' => $record->first_touch_channel,
                'last_external_touch_channel' => $record->last_external_touch_channel,
                'internal_promo_touched' => (bool) $record->internal_promo_touched,
                'fbclid_present' => (bool) $record->fbclid_present,
                'gclid_present' => (bool) $record->gclid_present,
            ];
        }

        return $this->reportingMetadata($request);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $touch
     */
    private function applyExternalTouch(array &$state, array $touch): void
    {
        if (! isset($state['first'])) {
            $state['first'] = $touch;
        }

        $state['last'] = $touch;
        $state['last_external'] = $touch;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $touch
     */
    private function applyDirectTouch(array &$state, array $touch): void
    {
        if (! isset($state['first'])) {
            $state['first'] = $touch;
        }

        $state['last'] = $touch;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $touch
     */
    private function applyInternalTouch(Request $request, array &$state, array $touch): void
    {
        $state['last'] = $touch;
        $state['internal_promo_touched'] = true;
        $state['internal'] = [
            'source' => 'pnedu',
            'medium' => 'internal',
            'context' => $this->resolveInternalContext($request),
            'path' => '/'.ltrim($request->path(), '/'),
            'at' => now()->toIso8601String(),
            'promo_id' => null,
            'promo_name' => null,
            'promo_placement' => $this->resolveInternalPlacement($request),
            'promo_context' => $this->resolveInternalContext($request),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function buildReportingFields(array $state): array
    {
        $conversionChannel = $state['last_external']['channel']
            ?? $state['current']['channel']
            ?? 'unknown';

        $sourceTouch = $state['last_external'] ?? $state['current'] ?? [];

        return [
            'traffic_channel' => $conversionChannel,
            'traffic_source' => $sourceTouch['source'] ?? null,
            'traffic_medium' => $sourceTouch['medium'] ?? null,
            'traffic_campaign' => $sourceTouch['campaign'] ?? null,
            'conversion_reporting_channel' => $conversionChannel,
            'attribution_source' => $sourceTouch['attribution_source'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSignals(Request $request): array
    {
        $utm = $this->resolveUtm($request);
        $referrerDomain = $this->referrerDomain($request->headers->get('referer'));

        return [
            'utm_source' => $utm['utm_source'] ?? null,
            'utm_medium' => $utm['utm_medium'] ?? null,
            'utm_campaign' => $utm['utm_campaign'] ?? null,
            'utm_content' => $utm['utm_content'] ?? null,
            'utm_term' => $utm['utm_term'] ?? null,
            'referrer_domain' => $referrerDomain,
            'fbclid_present' => $request->has('fbclid'),
            'gclid_present' => $request->has('gclid'),
            'is_internal_referrer' => $this->isInternalReferrer($referrerDomain),
        ];
    }

    /**
     * @param  array<string, mixed>  $signals
     * @param  array<string, mixed>  $classified
     * @return array<string, mixed>
     */
    private function buildTouchRecord(Request $request, array $signals, array $classified): array
    {
        $referrerDomain = $signals['referrer_domain'] ?? null;

        return [
            'source' => $classified['source'] ?? null,
            'medium' => $classified['medium'] ?? null,
            'campaign' => $signals['utm_campaign'] ?? null,
            'content' => $signals['utm_content'] ?? null,
            'term' => $signals['utm_term'] ?? null,
            'referrer' => $referrerDomain,
            'referrer_domain' => $referrerDomain,
            'landing_url' => $this->safePath($request),
            'channel' => $classified['channel'] ?? 'unknown',
            'attribution_source' => $classified['attribution_source'] ?? 'unknown',
        ];
    }

    /**
     * @return array{utm_source?: ?string, utm_medium?: ?string, utm_campaign?: ?string, utm_content?: ?string, utm_term?: ?string}
     */
    private function resolveUtm(Request $request): array
    {
        $fromQuery = array_filter([
            'utm_source' => $this->stringOrNull($request->query('utm_source')),
            'utm_medium' => $this->stringOrNull($request->query('utm_medium')),
            'utm_campaign' => $this->stringOrNull($request->query('utm_campaign')),
            'utm_content' => $this->stringOrNull($request->query('utm_content')),
            'utm_term' => $this->stringOrNull($request->query('utm_term')),
        ], fn (?string $value): bool => $value !== null);

        if ($fromQuery !== []) {
            if (! isset($fromQuery['utm_campaign'])) {
                $campaign = $this->marketingAttribution->resolveCampaignCode($request);
                if ($campaign !== null) {
                    $fromQuery['utm_campaign'] = $campaign;
                }
            }

            return $fromQuery;
        }

        $session = $request->hasSession()
            ? $request->session()->get(MarketingAttributionService::SESSION_KEY, [])
            : [];
        $cookie = $this->marketingAttribution->readCookiePayload($request);

        $utmSource = is_array($session) ? ($session['utm_source'] ?? null) : null;
        $utmMedium = is_array($session) ? ($session['utm_medium'] ?? null) : null;
        $utmContent = is_array($session) ? ($session['utm_content'] ?? null) : null;
        $utmCampaign = $this->marketingAttribution->resolveCampaignCode($request);

        return array_filter([
            'utm_source' => $this->stringOrNull($utmSource ?: ($cookie['utm_source'] ?? null)),
            'utm_medium' => $this->stringOrNull($utmMedium ?: ($cookie['utm_medium'] ?? null)),
            'utm_campaign' => $this->stringOrNull($utmCampaign),
            'utm_content' => $this->stringOrNull($utmContent ?: ($cookie['utm_content'] ?? null)),
            'utm_term' => null,
        ], fn (?string $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function getState(Request $request): array
    {
        $state = $request->session()->get(self::SESSION_KEY, []);

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function saveState(Request $request, array $state): void
    {
        $request->session()->put(self::SESSION_KEY, $state);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function stateToPersistenceAttributes(array $state, string $formSessionId, int $courseId, ?int $priceVariantId): array
    {
        $reporting = is_array($state['reporting'] ?? null) ? $state['reporting'] : $this->buildReportingFields($state);
        $current = is_array($state['current'] ?? null) ? $state['current'] : [];
        $first = is_array($state['first'] ?? null) ? $state['first'] : [];
        $last = is_array($state['last'] ?? null) ? $state['last'] : [];
        $lastExternal = is_array($state['last_external'] ?? null) ? $state['last_external'] : [];
        $internal = is_array($state['internal'] ?? null) ? $state['internal'] : [];

        return array_merge(
            [
                'form_session_id' => $formSessionId,
                'course_id' => $courseId,
                'price_variant_id' => $priceVariantId,
                'internal_promo_touched' => (bool) ($state['internal_promo_touched'] ?? false),
                'fbclid_present' => (bool) ($state['fbclid_present'] ?? false),
                'gclid_present' => (bool) ($state['gclid_present'] ?? false),
                'tracking_schema_version' => AnalyticsEventContract::SCHEMA_VERSION,
            ],
            $reporting,
            $this->prefixTouch('current', $current),
            $this->prefixTouch('first_touch', $first),
            $this->prefixTouch('last_touch', $last),
            $this->prefixTouch('last_external_touch', $lastExternal),
            [
                'internal_touch_source' => $internal['source'] ?? null,
                'internal_touch_medium' => $internal['medium'] ?? null,
                'internal_touch_context' => $internal['context'] ?? null,
                'internal_touch_path' => $internal['path'] ?? null,
                'internal_touch_at' => isset($internal['at']) ? $internal['at'] : null,
                'internal_promo_id' => $internal['promo_id'] ?? null,
                'internal_promo_name' => $internal['promo_name'] ?? null,
                'internal_promo_placement' => $internal['promo_placement'] ?? null,
                'internal_promo_context' => $internal['promo_context'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $touch
     * @return array<string, mixed>
     */
    private function touchMetadata(string $prefix, ?array $touch): array
    {
        if ($touch === null) {
            return [];
        }

        $map = [
            'source' => "{$prefix}_source",
            'medium' => "{$prefix}_medium",
            'campaign' => "{$prefix}_campaign",
            'content' => "{$prefix}_content",
            'term' => "{$prefix}_term",
            'referrer' => "{$prefix}_referrer",
            'referrer_domain' => "{$prefix}_referrer_domain",
            'landing_url' => $prefix === 'current' ? 'current_url' : "{$prefix}_landing_url",
            'channel' => "{$prefix}_channel",
            'attribution_source' => "{$prefix}_attribution_source",
        ];

        $metadata = [];
        foreach ($map as $from => $to) {
            if (isset($touch[$from]) && $touch[$from] !== null && $touch[$from] !== '') {
                $metadata[$to] = $touch[$from];
            }
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function internalTouchMetadata(array $state): array
    {
        $internal = is_array($state['internal'] ?? null) ? $state['internal'] : [];

        return array_filter([
            'internal_touch_source' => $internal['source'] ?? null,
            'internal_touch_medium' => $internal['medium'] ?? null,
            'internal_touch_context' => $internal['context'] ?? null,
            'internal_touch_path' => $internal['path'] ?? null,
            'internal_promo_placement' => $internal['promo_placement'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $touch
     * @return array<string, mixed>
     */
    private function prefixTouch(string $prefix, array $touch): array
    {
        return $this->touchMetadata($prefix, $touch);
    }

    private function resolveInternalPlacement(Request $request): ?string
    {
        $courseId = $request->route('id');
        if ($courseId === null || ! ctype_digit((string) $courseId)) {
            return null;
        }

        return $this->placement->resolveForCourse($request, (int) $courseId);
    }

    private function resolveInternalContext(Request $request): ?string
    {
        $routeName = optional($request->route())->getName();

        return is_string($routeName) && $routeName !== '' ? Str::limit($routeName, 100, '') : null;
    }

    private function safePath(Request $request): string
    {
        return '/'.ltrim($request->path(), '/');
    }

    private function referrerDomain(?string $referrer): ?string
    {
        if (! is_string($referrer) || trim($referrer) === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower(ltrim($host, 'www.'));
    }

    private function isInternalReferrer(?string $referrerDomain): bool
    {
        if ($referrerDomain === null) {
            return false;
        }

        foreach ($this->internalHosts() as $host) {
            if ($referrerDomain === $host || str_ends_with($referrerDomain, '.'.$host)) {
                return true;
            }
        }

        return in_array($referrerDomain, ['localhost', '127.0.0.1'], true);
    }

    /**
     * @return list<string>
     */
    private function internalHosts(): array
    {
        $hosts = (array) config('analytics.traffic.internal_hosts', ['pnedu.pl', 'adm.pnedu.pl', 'localhost']);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = $appHost;
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $host): string => strtolower(trim((string) $host)),
            $hosts
        ))));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? Str::limit($value, 255, '') : null;
    }
}
