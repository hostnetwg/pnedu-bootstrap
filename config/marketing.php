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

    /*
    |--------------------------------------------------------------------------
    | Opt-out lejka (dev / zespół) — cookie po ?pne_skip_funnel=1&token=…
    |--------------------------------------------------------------------------
    */
    'funnel_skip_cookie' => 'pne_skip_funnel',
    'funnel_skip_until_cookie' => 'pne_skip_funnel_until',
    'funnel_skip_analytics_cookie' => 'pne_skip_analytics',
    'funnel_skip_token' => env('MARKETING_FUNNEL_SKIP_TOKEN'),
    'funnel_skip_cookie_days' => (int) env('MARKETING_FUNNEL_SKIP_COOKIE_DAYS', 365), // rolling TTL — odnawiane przy wizycie; OFF trwa do ręcznego ON
    'funnel_skip_cookie_domain' => env(
        'MARKETING_FUNNEL_SKIP_COOKIE_DOMAIN',
        env('APP_ENV') === 'production' ? '.pnedu.pl' : null
    ),
    'funnel_skip_query_param' => 'pne_skip_funnel',
    'funnel_skip_analytics_query_param' => 'pne_skip_analytics',
    'funnel_skip_token_param' => 'token',

];
