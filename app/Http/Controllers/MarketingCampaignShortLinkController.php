<?php

namespace App\Http\Controllers;

use App\Services\MarketingCampaignLinkResolver;
use App\Services\MarketingCampaignLinkTracker;
use App\Services\CoursePageViewTracker;
use Symfony\Component\HttpFoundation\Response;

class MarketingCampaignShortLinkController extends Controller
{
    public function __invoke(
        string $campaign_code,
        MarketingCampaignLinkResolver $resolver,
        MarketingCampaignLinkTracker $linkTracker,
        CoursePageViewTracker $coursePageViewTracker,
    ): Response {
        $target = $resolver->resolveRedirectPath($campaign_code);
        if ($target === null) {
            abort(404);
        }

        $linkTracker->trackCampaignCode(request(), $campaign_code);

        $response = redirect($target, 302);

        $funnelSessionCookie = $coursePageViewTracker->funnelSessionCookie(request());
        if ($funnelSessionCookie !== null) {
            $response->headers->setCookie($funnelSessionCookie);
        }

        return $response;
    }
}
