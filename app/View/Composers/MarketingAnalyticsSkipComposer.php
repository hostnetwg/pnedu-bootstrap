<?php

namespace App\View\Composers;

use App\Services\FunnelSkipService;
use Illuminate\View\View;

class MarketingAnalyticsSkipComposer
{
    public function __construct(
        private readonly FunnelSkipService $funnelSkip,
    ) {}

    public function compose(View $view): void
    {
        $view->with(
            'skipMarketingAnalytics',
            $this->funnelSkip->shouldSkipAnalytics(request()),
        );
    }
}
