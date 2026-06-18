<?php

namespace App\Http\Middleware;

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
        private readonly OrderEntryPlacementService $placement,
        private readonly MarketingCampaignLinkTracker $campaignLinkTracker,
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

        $this->campaignLinkTracker->trackFromRequest($request);

        $response = $next($request);

        if ($payload !== []) {
            $existing = $this->attribution->readCookiePayload($request);
            $merged = array_merge($existing, $payload);
            $response->headers->setCookie($this->attribution->writeCookiePayload($merged));
        }

        return $response;
    }
}
