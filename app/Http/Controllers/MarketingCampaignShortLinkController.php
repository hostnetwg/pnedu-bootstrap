<?php

namespace App\Http\Controllers;

use App\Services\MarketingCampaignLinkResolver;
use Symfony\Component\HttpFoundation\Response;

class MarketingCampaignShortLinkController extends Controller
{
    public function __invoke(string $campaign_code, MarketingCampaignLinkResolver $resolver): Response
    {
        $target = $resolver->resolveRedirectPath($campaign_code);
        if ($target === null) {
            abort(404);
        }

        return redirect($target, 302);
    }
}
