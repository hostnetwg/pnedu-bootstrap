<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            \App\Http\Middleware\ConfigureCertificateRegistrationSession::class,
            \App\Http\Middleware\CaptureFunnelSkipOptOut::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CaptureMarketingSource::class,
            \App\Http\Middleware\RecordPneduUserLoginSession::class,
            \App\Http\Middleware\LogDashboardPerformance::class,
            \App\Http\Middleware\CacheHomepage::class,
        ]);

        // Za tunelami (ngrok, Cloudflare Tunnel) Laravel musi ufać nagłówkom X-Forwarded-*,
        // inaczej generuje URL-e jako http:// mimo HTTPS — Chrome blokuje wysyłkę formularzy.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: [
            'pne_skip_funnel',
        ]);

        $middleware->validateCsrfTokens(except: [
            'logout',                    // GET w menu + POST ze starych zakładek — zawsze kończy sesję i idzie na /
            'payment/payu/notify',
            'payment/paynow/notify',
            'certificate-registration/*', // formularz publiczny po linku z tokenem – token w URL jest wystarczającą ochroną
            'certificate/*',             // podgląd/pobieranie zaświadczeń i formularz daty/miejsca urodzenia – token w URL jest wystarczającą ochroną (unika 419 przy wygasłej sesji)
            'webhooks/ses/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpException $e, Request $request): ?Response {
            if ($e->getStatusCode() === 403 && $request->is('verify-email/*')) {
                $message = Auth::check()
                    ? 'Nie udało się potwierdzić adresu e-mail tym linkiem. Wyślij nowy link weryfikacyjny lub zaloguj się na właściwe konto.'
                    : 'Aby potwierdzić adres e-mail, zaloguj się na konto powiązane z adresem z wiadomości, a następnie kliknij link ponownie.';

                return Auth::check()
                    ? redirect()->route('verification.notice')->with('error', $message)
                    : redirect()->route('login')->with('error', $message);
            }

            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'Sesja wygasła — spróbuj ponownie.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            $safeInput = $request->except([
                '_token',
                'password',
                'password_confirmation',
                'current_password',
            ]);

            if ($request->is('login', 'register', 'forgot-password', 'reset-password*')) {
                return redirect()
                    ->to($request->url())
                    ->withInput($safeInput)
                    ->with('error', $message);
            }

            if (Auth::check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', $message);
            }

            if ($request->headers->get('referer')) {
                return redirect()
                    ->back()
                    ->withInput($safeInput)
                    ->with('error', $message);
            }

            return redirect()
                ->route('login')
                ->with('error', $message);
        });
    })->create();
