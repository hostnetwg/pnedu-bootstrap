<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;

class AnalyticsContextService
{
    public function fromRequest(Request $request): array
    {
        return [
            'route_name' => optional($request->route())->getName(),
            'path' => '/'.ltrim($request->path(), '/'),
            'referrer_domain' => $this->referrerDomain($request->headers->get('referer')),
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
            'utm_term' => $request->query('utm_term'),
            'campaign_code' => $request->query('campaign_code'),
            'device_type' => $this->deviceType($request->userAgent()),
            'browser_family' => $this->browserFamily($request->userAgent()),
        ];
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
