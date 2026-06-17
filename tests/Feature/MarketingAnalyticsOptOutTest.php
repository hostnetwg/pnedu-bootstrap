<?php

namespace Tests\Feature;

use App\Services\FunnelSkipService;
use App\View\Composers\MarketingAnalyticsSkipComposer;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class MarketingAnalyticsOptOutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.funnel_skip_token' => 'feature-test-secret',
            'marketing.funnel_skip_cookie' => 'pne_skip_funnel',
            'marketing.funnel_skip_analytics_cookie' => 'pne_skip_analytics',
            'services.google_analytics.id' => 'G-TESTANALYTICS',
            'services.google_tag_manager.id' => 'GTM-TESTTAG',
        ]);
    }

    public function test_analytics_head_renders_gtm_and_ga_without_opt_out_in_production(): void
    {
        $html = $this->renderAnalyticsHeadInProduction();

        $this->assertStringContainsString('GTM-TESTTAG', $html);
        $this->assertStringContainsString('G-TESTANALYTICS', $html);
        $this->assertStringContainsString('gtag/js', $html);
    }

    public function test_analytics_head_omits_gtm_and_ga_with_analytics_opt_out_cookie(): void
    {
        $html = $this->renderAnalyticsHeadInProduction(['pne_skip_analytics' => '1']);

        $this->assertStringNotContainsString('GTM-TESTTAG', $html);
        $this->assertStringNotContainsString('G-TESTANALYTICS', $html);
        $this->assertStringNotContainsString('gtag/js', $html);
        $this->assertStringNotContainsString('googletagmanager.com/gtm.js', $html);
    }

    public function test_gtm_noscript_omitted_with_analytics_opt_out_cookie(): void
    {
        $html = $this->renderGtmBodyInProduction(['pne_skip_analytics' => '1']);

        $this->assertStringNotContainsString('googletagmanager.com/ns.html', $html);
    }

    public function test_view_composer_sets_skip_flag_from_analytics_opt_out_cookie(): void
    {
        $request = request()->duplicate(cookies: ['pne_skip_analytics' => '1']);
        $this->app->instance('request', $request);

        $view = View::make('layouts.analytics-head');
        (new MarketingAnalyticsSkipComposer(app(FunnelSkipService::class)))->compose($view);

        $this->assertTrue($view->getData()['skipMarketingAnalytics']);
    }

    public function test_funnel_opt_out_cookie_alone_does_not_disable_analytics(): void
    {
        $html = $this->renderAnalyticsHeadInProduction(['pne_skip_funnel' => '1']);

        $this->assertStringContainsString('GTM-TESTTAG', $html);
        $this->assertStringContainsString('G-TESTANALYTICS', $html);
    }

    private function renderAnalyticsHeadInProduction(array $cookies = []): string
    {
        $this->app['env'] = 'production';

        $request = request()->duplicate(cookies: $cookies);
        $this->app->instance('request', $request);

        return View::make('layouts.analytics-head')
            ->with('skipMarketingAnalytics', app(FunnelSkipService::class)->shouldSkipAnalytics($request))
            ->render();
    }

    private function renderGtmBodyInProduction(array $cookies = []): string
    {
        $this->app['env'] = 'production';

        $request = request()->duplicate(cookies: $cookies);
        $this->app->instance('request', $request);

        return View::make('layouts.google-tag-manager-body')
            ->with('skipMarketingAnalytics', app(FunnelSkipService::class)->shouldSkipAnalytics($request))
            ->render();
    }
}
