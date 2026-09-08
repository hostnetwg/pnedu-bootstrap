<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;

class AnalyticsConsentService
{
    public const ANALYTICS = 'analytics';

    public const NECESSARY = 'necessary';

    public function hasAnalyticsConsent(?Request $request = null): bool
    {
        if (! config('consent.required', true)) {
            return true;
        }

        $request ??= request();

        return hash_equals(self::ANALYTICS, (string) $request->cookie($this->cookieName(), ''));
    }

    public function cookieName(): string
    {
        return (string) config('consent.cookie.name', 'pne_cookie_consent');
    }
}
