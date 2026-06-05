<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dla tras rejestracji zaświadczenia preferuj Redis zamiast sesji w DB (mniejszy load przy spike).
 * Ustaw CERT_REG_SESSION_DRIVER=redis w .env (domyślnie redis gdy zmienna pusta).
 */
class ConfigureCertificateRegistrationSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->is('certificate-registration', 'certificate-registration/*')) {
            $driver = config('services.certificate_registration.session_driver');

            if (is_string($driver) && $driver !== '' && $driver !== 'inherit') {
                config(['session.driver' => $driver]);
            }
        }

        return $next($request);
    }
}
