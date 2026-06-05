<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Krótki cache HTML formularza GET — odciąża origin przy setkach wejść na ten sam link.
 * Na produkcji uzupełnij regułą Cloudflare: Cache HTML dla /certificate-registration/*
 */
class CacheCertificateRegistrationPage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $request->routeIs('certificate-registration.show')) {
            return $response;
        }

        if ($response->isRedirect() || ! $response->isSuccessful()) {
            return $response;
        }

        $maxAge = (int) config('services.certificate_registration.page_cache_max_age', 30);
        if ($maxAge <= 0) {
            return $response;
        }

        $stale = (int) config('services.certificate_registration.page_cache_stale_while_revalidate', 60);

        return $response->header(
            'Cache-Control',
            sprintf('public, max-age=%d, stale-while-revalidate=%d', $maxAge, max(0, $stale))
        );
    }
}
