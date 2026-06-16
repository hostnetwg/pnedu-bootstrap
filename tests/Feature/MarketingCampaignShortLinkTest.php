<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingCampaignShortLinkTest extends TestCase
{
    public function test_unknown_campaign_short_link_returns_not_found(): void
    {
        $this->get('/l/__missing_campaign_code__')->assertNotFound();
    }
}
