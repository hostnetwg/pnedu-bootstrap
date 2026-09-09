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

    /**
     * Własny lejek operacyjny (wejście na kurs/formularz, GUS, „Aktywni teraz”)
     * nie wymaga zgody na Google Analytics / GTM.
     */
    public function allowsFirstPartyOperationalTracking(?Request $request = null): bool
    {
        if (! config('consent.first_party_operational_without_analytics_consent', true)) {
            return $this->hasAnalyticsConsent($request);
        }

        return true;
    }

    public function cookieName(): string
    {
        return (string) config('consent.cookie.name', 'pne_cookie_consent');
    }
}
