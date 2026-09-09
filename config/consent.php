<?php

return [
    'required' => (bool) env('ANALYTICS_CONSENT_REQUIRED', true),

    /*
     * Własny lejek (Aktywni teraz, wejście na formularz, wznowienie sesji) działa
     * bez zgody na Google. Ustaw false, aby wrócić do blokady sprzed podziału.
     */
    'first_party_operational_without_analytics_consent' => (bool) env(
        'FIRST_PARTY_OPERATIONAL_WITHOUT_ANALYTICS_CONSENT',
        true
    ),

    'cookie' => [
        'name' => 'pne_cookie_consent',
        'days' => (int) env('ANALYTICS_CONSENT_DAYS', 180),
    ],
];
