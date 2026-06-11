<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log wydajności panelu /dashboard/* (Faza 7)
    |--------------------------------------------------------------------------
    |
    | Włącz tymczasowo na produkcji przy diagnozie (DASHBOARD_PERF_LOG=true).
    | Logi trafiają do storage/logs/dashboard-performance.log
    |
    | Niezależnie od flagi, żądania wolniejsze niż progi trafiają jako warning.
    |
    */

    'dashboard_performance_log' => filter_var(
        env('DASHBOARD_PERF_LOG', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'dashboard_performance_slow_ms' => (int) env('DASHBOARD_PERF_SLOW_MS', 500),

    'dashboard_performance_slow_queries' => (int) env('DASHBOARD_PERF_SLOW_QUERIES', 25),

];
