<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureMarketingSource
{
    /**
     * Persist marketing source code (fb/fb_source) in session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->query('fb', $request->query('fb_source'));

        if (is_string($raw)) {
            $raw = trim($raw);
        }

        if ($raw !== null && $raw !== '') {
            $request->session()->put('marketing.fb_source', mb_substr((string) $raw, 0, 255));
        }

        return $next($request);
    }
}

