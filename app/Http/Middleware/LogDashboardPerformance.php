<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Metryki czasu odpowiedzi i zapytań SQL dla tras /dashboard/* (Faza 7).
 */
class LogDashboardPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isDashboardRequest($request)) {
            return $next($request);
        }

        $queryCount = 0;
        $queryTimeMs = 0.0;

        DB::listen(function ($query) use (&$queryCount, &$queryTimeMs): void {
            $queryCount++;
            $queryTimeMs += (float) $query->time;
        });

        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (microtime(true) - $startedAt) * 1000;
        $slowMs = (int) config('observability.dashboard_performance_slow_ms', 500);
        $slowQueries = (int) config('observability.dashboard_performance_slow_queries', 25);
        $isSlow = $durationMs >= $slowMs || $queryCount >= $slowQueries;
        $verbose = (bool) config('observability.dashboard_performance_log', false);

        if (! $verbose && ! $isSlow) {
            return $response;
        }

        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 1),
            'db_queries' => $queryCount,
            'db_time_ms' => round($queryTimeMs, 1),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
        ];

        $level = $isSlow ? 'warning' : 'info';

        Log::channel('dashboard_perf')->log($level, 'dashboard.request', $context);

        return $response;
    }

    private function isDashboardRequest(Request $request): bool
    {
        return $request->is('dashboard', 'dashboard/*');
    }
}
