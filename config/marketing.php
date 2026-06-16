<?php

return [

    'attribution_days' => (int) env('MARKETING_ATTRIBUTION_DAYS', 7),

    'cookie_name' => 'pne_marketing',

    'funnel_session_cookie' => 'pne_funnel_sid',

    /*
    |--------------------------------------------------------------------------
    | Miejsca konwersji (entry=…) — osobno od kampanii w fb_source
    |--------------------------------------------------------------------------
    */
    'conversion_placements' => [
        'dashboard_sidebar' => 'Panel klienta → Aktualna oferta',
    ],

    'entry_query_param' => 'entry',

];
