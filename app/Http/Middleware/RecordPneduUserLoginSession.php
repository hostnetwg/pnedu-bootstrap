<?php

namespace App\Http\Middleware;

use App\Services\UserLoginTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejestruje jedno „wejście” na konto na sesję Laravel (formularz logowania lub auto-logowanie z „zapamiętaj mnie”).
 * Kolejne podstrony w tej samej sesji nie zwiększają licznika.
 */
class RecordPneduUserLoginSession
{
    private const SESSION_FLAG = 'pnedu_login_session_recorded';

    public function __construct(
        private readonly UserLoginTrackingService $loginTracking,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::guard('web')->user();

        if ($user === null || $request->session()->get(self::SESSION_FLAG) === true) {
            return $response;
        }

        if (! $user instanceof \App\Models\User) {
            return $response;
        }

        $this->loginTracking->recordAuthenticatedSession($user);
        $request->session()->put(self::SESSION_FLAG, true);

        return $response;
    }
}
