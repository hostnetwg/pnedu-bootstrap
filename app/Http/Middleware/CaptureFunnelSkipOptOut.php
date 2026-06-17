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
     * Włącza/wyłącza cookie opt-out lejka i/lub analityki (?pne_skip_funnel=… / ?pne_skip_analytics=… + token).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->funnelSkip->isQueryToggle($request)) {
            return $this->handleFunnelToggle($request);
        }

        if ($this->funnelSkip->isAnalyticsQueryToggle($request)) {
            return $this->handleAnalyticsToggle($request);
        }

        return $next($request);
    }

    private function handleFunnelToggle(Request $request): Response
    {
        $enableOptOut = $request->query($this->funnelSkip->queryParam()) === '1';
        $admReturn = $this->funnelSkip->resolveAdmReturnUrl($request);
        $target = $admReturn ?? $request->url();

        $response = redirect()->to($target)
            ->withCookie($enableOptOut ? $this->funnelSkip->makeOptOutCookie() : $this->funnelSkip->forgetOptOutCookie())
            ->withCookie($enableOptOut ? $this->funnelSkip->makeOptOutUntilCookie() : $this->funnelSkip->forgetOptOutUntilCookie());

        if ($enableOptOut && $this->resolveDisableAnalyticsFlag($request)) {
            $response = $response->withCookie($this->funnelSkip->makeAnalyticsOptOutCookie());
        }

        return $response->with(
            'info',
            $enableOptOut
                ? 'Lejek marketingowy: Twoje wejścia na pnedu.pl nie będą liczone w statystykach adm.'
                : 'Lejek marketingowy: liczenie Twoich wejść zostało przywrócone.'
        );
    }

    private function handleAnalyticsToggle(Request $request): Response
    {
        $enableOptOut = $request->query($this->funnelSkip->analyticsQueryParam()) === '1';
        $admReturn = $this->funnelSkip->resolveAdmReturnUrl($request);
        $target = $admReturn ?? $request->url();

        return redirect()->to($target)
            ->withCookie($enableOptOut ? $this->funnelSkip->makeAnalyticsOptOutCookie() : $this->funnelSkip->forgetAnalyticsOptOutCookie())
            ->with(
                'info',
                $enableOptOut
                    ? 'Google Analytics / GTM: wyłączone dla tej przeglądarki na pnedu.pl.'
                    : 'Google Analytics / GTM: przywrócone dla tej przeglądarki na pnedu.pl.'
            );
    }

    private function resolveDisableAnalyticsFlag(Request $request): bool
    {
        $raw = $request->query($this->funnelSkip->analyticsQueryParam());

        if ($raw === null) {
            return false;
        }

        return $raw === '1';
    }
}
