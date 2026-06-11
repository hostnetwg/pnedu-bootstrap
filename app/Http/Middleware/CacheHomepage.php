<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Krótki cache HTML strony głównej dla gości — odciąża origin i wspiera CDN (Faza 6).
 */
class CacheHomepage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $request->routeIs('home') || Auth::check()) {
            return $response;
        }

        if ($response->isRedirect() || ! $response->isSuccessful()) {
            return $response;
        }

        $maxAge = (int) config('seo.homepage.page_cache_max_age', 60);
        if ($maxAge <= 0) {
            return $response;
        }

        $stale = (int) config('seo.homepage.page_cache_stale_while_revalidate', 120);

        return $response->header(
            'Cache-Control',
            sprintf('public, max-age=%d, stale-while-revalidate=%d', $maxAge, max(0, $stale))
        );
    }
}
