<?php

return [
    'required' => (bool) env('ANALYTICS_CONSENT_REQUIRED', true),

    'cookie' => [
        'name' => 'pne_cookie_consent',
        'days' => (int) env('ANALYTICS_CONSENT_DAYS', 180),
    ],
];
