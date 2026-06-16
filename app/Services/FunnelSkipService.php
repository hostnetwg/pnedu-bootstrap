<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class FunnelSkipService
{
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

    public function makeOptOutCookie(): Cookie
    {
        $days = max(1, (int) config('marketing.funnel_skip_cookie_days', 365));
        $minutes = $days * 24 * 60;

        return cookie(
            $this->cookieName(),
            '1',
            $minutes,
            '/',
            null,
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
            null,
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
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
}
