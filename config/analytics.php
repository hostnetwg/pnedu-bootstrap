<?php

return [
    'enabled' => (bool) env('ANALYTICS_ENABLED', true),

    'connection' => env('ANALYTICS_DB_CONNECTION', 'analytics'),

    'default_mode' => env('ANALYTICS_DEFAULT_MODE', 'standard'),

    'sample_rate' => (int) env('ANALYTICS_SAMPLE_RATE', 100),

    'session' => [
        'cookie' => env('ANALYTICS_SESSION_COOKIE', 'pne_analytics_sid'),
        'days' => (int) env('ANALYTICS_SESSION_DAYS', 30),
    ],

    'order_form_session' => [
        'cookie_prefix' => env('ANALYTICS_ORDER_FORM_SESSION_COOKIE_PREFIX', 'pne_order_form_sid'),
        'hours' => (int) env('ANALYTICS_ORDER_FORM_SESSION_HOURS', 24),
    ],

    // Etap B1 — publiczny endpoint JS (POST /analytics/client-events).
    'client_events' => [
        'max_events_per_batch' => (int) env('ANALYTICS_CLIENT_EVENTS_MAX_BATCH', 20),
        'max_payload_bytes' => (int) env('ANALYTICS_CLIENT_EVENTS_MAX_PAYLOAD_BYTES', 10240),
        'rate_limit_per_minute' => (int) env('ANALYTICS_CLIENT_EVENTS_RATE_LIMIT', 60),
    ],

    'queue' => [
        'connection' => env('ANALYTICS_QUEUE_CONNECTION', 'redis'),
        'name' => env('ANALYTICS_QUEUE', 'analytics'),
        'tries' => (int) env('ANALYTICS_QUEUE_TRIES', 2),
        'timeout' => (int) env('ANALYTICS_QUEUE_TIMEOUT', 30),
    ],

    'retention_days' => [
        'raw_events' => (int) env('ANALYTICS_RETENTION_RAW_EVENTS_DAYS', 180),
        'order_form_sessions' => (int) env('ANALYTICS_RETENTION_ORDER_FORM_SESSIONS_DAYS', 365),
        'ai_safe_exports' => (int) env('ANALYTICS_RETENTION_AI_SAFE_EXPORTS_DAYS', 365),
    ],
];
