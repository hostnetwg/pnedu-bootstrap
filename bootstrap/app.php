<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'payment/payu/notify',
            'payment/paynow/notify',
            'certificate-registration/*', // formularz publiczny po linku z tokenem – token w URL jest wystarczającą ochroną
            'certificate/*',             // podgląd/pobieranie zaświadczeń i formularz daty/miejsca urodzenia – token w URL jest wystarczającą ochroną (unika 419 przy wygasłej sesji)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
