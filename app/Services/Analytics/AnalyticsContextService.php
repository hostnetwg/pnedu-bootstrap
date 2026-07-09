<?php

namespace App\Services\Analytics;

use App\Services\MarketingAttributionService;
use Illuminate\Http\Request;

class AnalyticsContextService
{
    public function __construct(
        private readonly MarketingAttributionService $marketingAttribution,
    ) {}

    public function fromRequest(Request $request): array
    {
        $utm = $this->resolvedUtm($request);

        return [
            'route_name' => optional($request->route())->getName(),
            'path' => '/'.ltrim($request->path(), '/'),
            'referrer_domain' => $this->referrerDomain($request->headers->get('referer')),
            'utm_source' => $utm['utm_source'] ?? null,
            'utm_medium' => $utm['utm_medium'] ?? null,
            'utm_campaign' => $utm['utm_campaign'] ?? null,
            'utm_content' => $utm['utm_content'] ?? null,
            'utm_term' => $utm['utm_term'] ?? null,
            'campaign_code' => $utm['utm_campaign'] ?? $request->query('campaign_code'),
            'device_type' => $this->deviceType($request->userAgent()),
            'browser_family' => $this->browserFamily($request->userAgent()),
        ];
    }

    /**
     * @return array{utm_source?: ?string, utm_medium?: ?string, utm_campaign?: ?string, utm_content?: ?string, utm_term?: ?string}
     */
    private function resolvedUtm(Request $request): array
    {
        $fromQuery = array_filter([
            'utm_source' => $this->queryString($request, 'utm_source'),
            'utm_medium' => $this->queryString($request, 'utm_medium'),
            'utm_campaign' => $this->queryString($request, 'utm_campaign'),
            'utm_content' => $this->queryString($request, 'utm_content'),
            'utm_term' => $this->queryString($request, 'utm_term'),
        ], fn (?string $value): bool => $value !== null);

        if ($fromQuery !== []) {
            return $fromQuery;
        }

        $session = $request->hasSession()
            ? $request->session()->get(MarketingAttributionService::SESSION_KEY, [])
            : [];
        $cookie = $this->marketingAttribution->readCookiePayload($request);
        $campaign = $this->marketingAttribution->resolveCampaignCode($request);

        return array_filter([
            'utm_source' => $this->stringOrNull(is_array($session) ? ($session['utm_source'] ?? null) : null)
                ?: $this->stringOrNull($cookie['utm_source'] ?? null),
            'utm_medium' => $this->stringOrNull(is_array($session) ? ($session['utm_medium'] ?? null) : null)
                ?: $this->stringOrNull($cookie['utm_medium'] ?? null),
            'utm_campaign' => $this->stringOrNull($campaign),
            'utm_content' => $this->stringOrNull(is_array($session) ? ($session['utm_content'] ?? null) : null)
                ?: $this->stringOrNull($cookie['utm_content'] ?? null),
            'utm_term' => null,
        ], fn (?string $value): bool => $value !== null);
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function referrerDomain(?string $referrer): ?string
    {
        if (! $referrer) {
            return null;
        }

        return parse_url($referrer, PHP_URL_HOST) ?: null;
    }

    private function deviceType(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browserFamily(?string $userAgent): ?string
    {
        $ua = strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'edg/') => 'edge',
            str_contains($ua, 'chrome/') => 'chrome',
            str_contains($ua, 'firefox/') => 'firefox',
            str_contains($ua, 'safari/') => 'safari',
            default => null,
        };
    }
}
