<?php

namespace App\Http\Controllers;

use App\Services\MarketingCampaignLinkResolver;
use App\Services\MarketingCampaignLinkTracker;
use Symfony\Component\HttpFoundation\Response;

class MarketingCampaignShortLinkController extends Controller
{
    public function __invoke(
        string $campaign_code,
        MarketingCampaignLinkResolver $resolver,
        MarketingCampaignLinkTracker $linkTracker,
    ): Response {
        $target = $resolver->resolveRedirectPath($campaign_code);
        if ($target === null) {
            abort(404);
        }

        $linkTracker->trackCampaignCode(request(), $campaign_code);

        return redirect($target, 302);
    }
}
