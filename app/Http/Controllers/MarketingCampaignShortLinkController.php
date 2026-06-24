<?php

namespace App\Http\Controllers;

use App\Services\Analytics\BackendAnalyticsTracker;
use App\Services\CoursePageViewTracker;
use App\Services\MarketingCampaignLinkResolver;
use App\Services\MarketingCampaignLinkTracker;
use Symfony\Component\HttpFoundation\Response;

class MarketingCampaignShortLinkController extends Controller
{
    public function __invoke(
        string $campaign_code,
        MarketingCampaignLinkResolver $resolver,
        MarketingCampaignLinkTracker $linkTracker,
        CoursePageViewTracker $coursePageViewTracker,
        BackendAnalyticsTracker $analyticsTracker,
    ): Response {
        $redirectContext = $resolver->resolveRedirectContext($campaign_code);
        $target = $redirectContext['redirect_path'] ?? null;
        if ($target === null) {
            abort(404);
        }

        $analyticsTracker->trackCampaignShortLinkVisit(request(), $redirectContext);
        $analyticsTracker->trackCampaignRedirectResolved(request(), $redirectContext);

        $linkTracker->trackCampaignCode(request(), $campaign_code);

        $response = redirect($target, 302);

        $funnelSessionCookie = $coursePageViewTracker->funnelSessionCookie(request());
        if ($funnelSessionCookie !== null) {
            $response->headers->setCookie($funnelSessionCookie);
        }

        $analyticsTracker->appendResponseCookies($response, request());

        return $response;
    }
}
