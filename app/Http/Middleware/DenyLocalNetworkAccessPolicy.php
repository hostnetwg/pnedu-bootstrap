<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wyłącza Local Network Access w Chromium (Chrome/Opera/Edge), żeby publiczna
 * strona nie pokazywała promptu „dostęp do innych aplikacji i usług na tym urządzeniu”.
 *
 * Tagi GTM/GA czasem sondą localhost — pnedu.pl nie potrzebuje takiej komunikacji.
 */
class DenyLocalNetworkAccessPolicy
{
    public const HEADER_VALUE = 'local-network-access=(), local-network=(), loopback-network=()';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Permissions-Policy', self::HEADER_VALUE);

        return $response;
    }
}
