<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsConsentService;
use App\Services\Analytics\BackendAnalyticsTracker;
use App\Services\Analytics\OrderFormAttributionService;
use App\Services\CoursePageViewTracker;
use App\Services\MarketingAttributionService;
use App\Services\MarketingCampaignLinkTracker;
use App\Services\OrderEntryPlacementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureMarketingSource
{
    public function __construct(
        private readonly MarketingAttributionService $attribution,
        private readonly AnalyticsConsentService $consent,
        private readonly OrderEntryPlacementService $placement,
        private readonly MarketingCampaignLinkTracker $campaignLinkTracker,
        private readonly CoursePageViewTracker $coursePageViewTracker,
        private readonly BackendAnalyticsTracker $analyticsTracker,
        private readonly OrderFormAttributionService $formAttribution,
    ) {}

    /**
     * Persist marketing attribution (UTM + legacy fb) in session and cookie.
     * Wejście z linku kampanii (kolumna Wejś.) liczy się także bez zgody na Google Analytics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->campaignLinkTracker->trackFromRequest($request);

        if (! $this->consent->hasAnalyticsConsent($request)) {
            return $next($request);
        }

        $payload = $this->attribution->captureFromRequest($request);

        if ($payload !== []) {
            $this->attribution->persist($request, $payload);
            $this->analyticsTracker->trackUtmCaptured($request, $payload);
        }

        $this->placement->captureFromRequest($request);
        $this->formAttribution->captureFromRequest($request);

        $response = $next($request);

        if ($payload !== []) {
            $existing = $this->attribution->readCookiePayload($request);
            $merged = array_merge($existing, $payload);
            $response->headers->setCookie($this->attribution->writeCookiePayload($merged));
        }

        $funnelSessionCookie = $this->coursePageViewTracker->funnelSessionCookie($request);
        if ($funnelSessionCookie !== null) {
            $response->headers->setCookie($funnelSessionCookie);
        }

        $this->analyticsTracker->appendResponseCookies($response, $request);

        return $response;
    }
}
