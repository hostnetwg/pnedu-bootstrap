<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AnalyticsSessionService
{
    private const ATTRIBUTE_KEY = 'analytics_session_id';

    private const PENDING_COOKIE_ATTRIBUTE = 'analytics_session_cookie_pending';

    public function id(Request $request): ?string
    {
        try {
            if (! config('analytics.enabled', true)) {
                return null;
            }

            $existing = $request->attributes->get(self::ATTRIBUTE_KEY);
            if (is_string($existing) && Str::isUuid($existing)) {
                return $existing;
            }

            $fromCookie = $request->cookie($this->cookieName());
            if (is_string($fromCookie) && Str::isUuid($fromCookie)) {
                $request->attributes->set(self::ATTRIBUTE_KEY, $fromCookie);

                return $fromCookie;
            }

            $newId = (string) Str::uuid();
            $request->attributes->set(self::ATTRIBUTE_KEY, $newId);
            $request->attributes->set(self::PENDING_COOKIE_ATTRIBUTE, true);

            return $newId;
        } catch (Throwable) {
            return null;
        }
    }

    public function appendCookie(Response $response, Request $request): void
    {
        try {
            if ($request->attributes->get(self::PENDING_COOKIE_ATTRIBUTE) !== true) {
                return;
            }

            $sessionId = $request->attributes->get(self::ATTRIBUTE_KEY);
            if (! is_string($sessionId) || ! Str::isUuid($sessionId)) {
                return;
            }

            $response->headers->setCookie($this->makeCookie($sessionId));
        } catch (Throwable) {
            // Cookie errors must never affect the request.
        }
    }

    public function cookieName(): string
    {
        return (string) config('analytics.session.cookie', 'pne_analytics_sid');
    }

    private function makeCookie(string $sessionId): Cookie
    {
        $minutes = max(1, (int) config('analytics.session.days', 30)) * 24 * 60;

        return cookie(
            $this->cookieName(),
            $sessionId,
            $minutes,
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
    }
}
