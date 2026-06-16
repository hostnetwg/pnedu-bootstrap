<?php

namespace App\Http\Middleware;

use App\Services\FunnelSkipService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureFunnelSkipOptOut
{
    public function __construct(
        private readonly FunnelSkipService $funnelSkip,
    ) {}

    /**
     * Włącza/wyłącza cookie opt-out lejka (?pne_skip_funnel=1|0&token=…).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->funnelSkip->isQueryToggle($request)) {
            return $next($request);
        }

        $enable = $request->query($this->funnelSkip->queryParam()) === '1';
        $admReturn = $this->funnelSkip->resolveAdmReturnUrl($request);
        $target = $admReturn ?? $request->url();

        $response = redirect()->to($target)
            ->withCookie($enable ? $this->funnelSkip->makeOptOutCookie() : $this->funnelSkip->forgetOptOutCookie())
            ->withCookie($enable ? $this->funnelSkip->makeOptOutUntilCookie() : $this->funnelSkip->forgetOptOutUntilCookie())
            ->with(
                'info',
                $enable
                    ? 'Lejek marketingowy: Twoje wejścia na pnedu.pl nie będą liczone w statystykach adm.'
                    : 'Lejek marketingowy: liczenie Twoich wejść zostało przywrócone.'
            );

        return $response;
    }
}
