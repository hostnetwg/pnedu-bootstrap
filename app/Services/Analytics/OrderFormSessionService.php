<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderFormSessionService
{
    public function id(Request $request, int $courseId, ?string $preferredSessionId = null): ?string
    {
        try {
            if (! config('analytics.enabled', true) || $courseId <= 0) {
                return null;
            }

            $attributeKey = $this->attributeKey($courseId);
            $existing = $request->attributes->get($attributeKey);
            if (is_string($existing) && Str::isUuid($existing)) {
                return $existing;
            }

            $fromCookie = $request->cookie($this->cookieName($courseId));
            if (is_string($fromCookie) && Str::isUuid($fromCookie)) {
                $request->attributes->set($attributeKey, $fromCookie);

                return $fromCookie;
            }

            if (is_string($preferredSessionId) && Str::isUuid($preferredSessionId)) {
                $request->attributes->set($attributeKey, $preferredSessionId);
                $request->attributes->set($this->pendingCookieAttribute($courseId), true);

                return $preferredSessionId;
            }

            $newId = (string) Str::uuid();
            $request->attributes->set($attributeKey, $newId);
            $request->attributes->set($this->pendingCookieAttribute($courseId), true);

            return $newId;
        } catch (Throwable) {
            return null;
        }
    }

    public function appendCookie(Response $response, Request $request, int $courseId): void
    {
        try {
            if ($request->attributes->get($this->pendingCookieAttribute($courseId)) !== true) {
                return;
            }

            $sessionId = $request->attributes->get($this->attributeKey($courseId));
            if (! is_string($sessionId) || ! Str::isUuid($sessionId)) {
                return;
            }

            $response->headers->setCookie($this->makeCookie($courseId, $sessionId));
        } catch (Throwable) {
            // Cookie errors must never affect the request.
        }
    }

    public function cookieName(int $courseId): string
    {
        $prefix = (string) config('analytics.order_form_session.cookie_prefix', 'pne_order_form_sid');

        return $prefix.'_'.$courseId;
    }

    private function makeCookie(int $courseId, string $sessionId): Cookie
    {
        $minutes = max(1, (int) config('analytics.order_form_session.hours', 24)) * 60;

        return cookie(
            $this->cookieName($courseId),
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

    private function attributeKey(int $courseId): string
    {
        return 'order_form_session_id_'.$courseId;
    }

    private function pendingCookieAttribute(int $courseId): string
    {
        return 'order_form_session_cookie_pending_'.$courseId;
    }
}
