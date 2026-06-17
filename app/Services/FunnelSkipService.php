<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class FunnelSkipService
{
    public function cookieDomain(): ?string
    {
        $domain = config('marketing.funnel_skip_cookie_domain');

        return is_string($domain) && trim($domain) !== '' ? trim($domain) : null;
    }

    public function isConfigured(): bool
    {
        $token = config('marketing.funnel_skip_token');

        return is_string($token) && $token !== '';
    }

    public function cookieName(): string
    {
        return (string) config('marketing.funnel_skip_cookie', 'pne_skip_funnel');
    }

    public function queryParam(): string
    {
        return (string) config('marketing.funnel_skip_query_param', 'pne_skip_funnel');
    }

    public function tokenParam(): string
    {
        return (string) config('marketing.funnel_skip_token_param', 'token');
    }

    public function untilCookieName(): string
    {
        return (string) config('marketing.funnel_skip_until_cookie', 'pne_skip_funnel_until');
    }

    public function analyticsCookieName(): string
    {
        return (string) config('marketing.funnel_skip_analytics_cookie', 'pne_skip_analytics');
    }

    public function analyticsQueryParam(): string
    {
        return (string) config('marketing.funnel_skip_analytics_query_param', 'pne_skip_analytics');
    }

    public function tokenMatches(Request $request): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $provided = $request->query($this->tokenParam());
        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals((string) config('marketing.funnel_skip_token'), $provided);
    }

    /**
     * Czy bieżące żądananie ustawia/wyłącza opt-out (poprawny token + pne_skip_funnel=0|1).
     */
    public function isQueryToggle(Request $request): bool
    {
        if (! $request->has($this->queryParam())) {
            return false;
        }

        $value = $request->query($this->queryParam());

        return in_array($value, ['0', '1'], true) && $this->tokenMatches($request);
    }

    public function isEnablingFromQuery(Request $request): bool
    {
        return $this->isQueryToggle($request) && $request->query($this->queryParam()) === '1';
    }

    public function isAnalyticsQueryToggle(Request $request): bool
    {
        if ($request->has($this->queryParam())) {
            return false;
        }

        if (! $request->has($this->analyticsQueryParam())) {
            return false;
        }

        $value = $request->query($this->analyticsQueryParam());

        return in_array($value, ['0', '1'], true) && $this->tokenMatches($request);
    }

    public function isEnablingAnalyticsFromQuery(Request $request): bool
    {
        return $this->isAnalyticsQueryToggle($request) && $request->query($this->analyticsQueryParam()) === '1';
    }

    /**
     * Nie licz wejść w lejku (cookie lub włączenie opt-out w tym samym żądaniu).
     */
    public function shouldSkipTracking(Request $request): bool
    {
        if ($this->isEnablingFromQuery($request)) {
            return true;
        }

        return $request->cookie($this->cookieName()) === '1';
    }

    /**
     * Wyłącz GA4 i GTM (osobny cookie względem lejka, z zachowaniem kompatybilności).
     */
    public function shouldSkipAnalytics(Request $request): bool
    {
        if ($this->isEnablingAnalyticsFromQuery($request)) {
            return true;
        }

        if ($this->isEnablingFromQuery($request)) {
            $query = $request->query($this->analyticsQueryParam());
            if ($query === '0') {
                return false;
            }
            if ($query === '1') {
                return true;
            }

            // Backward compatibility for old funnel links without explicit analytics flag.
            return true;
        }

        return $request->cookie($this->analyticsCookieName()) === '1';
    }

    public function makeOptOutCookie(): Cookie
    {
        $days = max(1, (int) config('marketing.funnel_skip_cookie_days', 365));
        $minutes = $days * 24 * 60;

        return cookie(
            $this->cookieName(),
            '1',
            $minutes,
            '/',
            $this->cookieDomain(),
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
    }

    public function makeOptOutUntilCookie(): Cookie
    {
        $days = max(1, (int) config('marketing.funnel_skip_cookie_days', 365));
        $minutes = $days * 24 * 60;
        $until = now()->addDays($days)->toIso8601String();

        return cookie(
            $this->untilCookieName(),
            $until,
            $minutes,
            '/',
            $this->cookieDomain(),
            app()->environment('production'),
            false,
            false,
            'Lax'
        );
    }

    public function makeAnalyticsOptOutCookie(): Cookie
    {
        $days = max(1, (int) config('marketing.funnel_skip_cookie_days', 365));
        $minutes = $days * 24 * 60;

        return cookie(
            $this->analyticsCookieName(),
            '1',
            $minutes,
            '/',
            $this->cookieDomain(),
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
    }

    public function forgetOptOutCookie(): Cookie
    {
        return cookie(
            $this->cookieName(),
            '',
            -1,
            '/',
            $this->cookieDomain(),
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
    }

    public function forgetOptOutUntilCookie(): Cookie
    {
        return cookie(
            $this->untilCookieName(),
            '',
            -1,
            '/',
            $this->cookieDomain(),
            app()->environment('production'),
            false,
            false,
            'Lax'
        );
    }

    public function forgetAnalyticsOptOutCookie(): Cookie
    {
        return cookie(
            $this->analyticsCookieName(),
            '',
            -1,
            '/',
            $this->cookieDomain(),
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
    }

    /**
     * Odnawia ważność cookie opt-out przy każdej wizycie — wyłączenie trwa do ręcznego ON.
     *
     * @return list<Cookie>
     */
    public function renewalCookiesForRequest(Request $request): array
    {
        $cookies = [];

        if ($request->cookie($this->cookieName()) === '1') {
            $cookies[] = $this->makeOptOutCookie();
            $cookies[] = $this->makeOptOutUntilCookie();
        }

        if ($request->cookie($this->analyticsCookieName()) === '1') {
            $cookies[] = $this->makeAnalyticsOptOutCookie();
        }

        return $cookies;
    }

    public function optOutBookmarkUrl(bool $enable = true): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $base = rtrim((string) config('app.url'), '/');
        $query = http_build_query([
            $this->queryParam() => $enable ? '1' : '0',
            $this->tokenParam() => (string) config('marketing.funnel_skip_token'),
        ]);

        return $base.'/?'.$query;
    }

    /**
     * Bezpieczny powrót do panelu adm po ustawieniu cookie (tylko znane hosty + ścieżka ustawień).
     */
    public function resolveAdmReturnUrl(Request $request): ?string
    {
        $raw = $request->query('adm_return');
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $parsed = parse_url($raw);
        if (! is_array($parsed) || empty($parsed['host']) || empty($parsed['path'])) {
            return null;
        }

        $host = strtolower((string) $parsed['host']);
        if (! in_array($host, $this->allowedAdmReturnHosts(), true)) {
            return null;
        }

        $path = rtrim((string) $parsed['path'], '/');
        if (! in_array($path, ['/settings/pnedu-zakupy', '/settings/analityka'], true)) {
            return null;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? 'https'));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    private function allowedAdmReturnHosts(): array
    {
        $hosts = ['localhost', 'adm.localhost', 'adm.pnedu.pl'];

        $fromConfig = parse_url((string) config('services.pneadm.public_url', ''), PHP_URL_HOST);
        if (is_string($fromConfig) && $fromConfig !== '') {
            $hosts[] = strtolower($fromConfig);
        }

        return array_values(array_unique($hosts));
    }
}
