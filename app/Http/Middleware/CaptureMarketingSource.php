<?php

namespace App\Http\Middleware;

use App\Services\MarketingAttributionService;
use App\Services\MarketingCampaignLinkTracker;
use App\Services\OrderEntryPlacementService;
use App\Services\CoursePageViewTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureMarketingSource
{
    public function __construct(
        private readonly MarketingAttributionService $attribution,
        private readonly OrderEntryPlacementService $placement,
        private readonly MarketingCampaignLinkTracker $campaignLinkTracker,
        private readonly CoursePageViewTracker $coursePageViewTracker,
    ) {}

    /**
     * Persist marketing attribution (UTM + legacy fb) in session and cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $payload = $this->attribution->captureFromRequest($request);

        if ($payload !== []) {
            $this->attribution->persist($request, $payload);
        }

        $this->placement->captureFromRequest($request);

        if (! empty($payload['campaign_code'])) {
            $this->campaignLinkTracker->trackCampaignCode($request, (string) $payload['campaign_code']);
        }

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

        return $response;
    }
}
