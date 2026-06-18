<?php

namespace Tests\Unit;

use App\Http\Middleware\RefreshFunnelSkipOptOutCookies;
use App\Services\FunnelSkipService;
use Illuminate\Http\Request;
use Tests\TestCase;

class RefreshFunnelSkipOptOutCookiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.funnel_skip_cookie' => 'pne_skip_funnel',
            'marketing.funnel_skip_until_cookie' => 'pne_skip_funnel_until',
            'marketing.funnel_skip_analytics_cookie' => 'pne_skip_analytics',
        ]);
    }

    public function test_middleware_renews_active_opt_out_cookies_on_response(): void
    {
        $request = Request::create('/login', 'GET');
        $request->cookies->set('pne_skip_funnel', '1');
        $request->cookies->set('pne_skip_analytics', '1');

        $middleware = app(RefreshFunnelSkipOptOutCookies::class);
        $response = $middleware->handle($request, fn () => response('ok'));

        $names = collect($response->headers->getCookies())->map->getName()->all();

        $this->assertContains('pne_skip_funnel', $names);
        $this->assertContains('pne_skip_funnel_until', $names);
        $this->assertContains('pne_skip_analytics', $names);
    }

    public function test_renewal_skipped_when_opt_out_cookies_absent(): void
    {
        $request = Request::create('/login', 'GET');

        $cookies = app(FunnelSkipService::class)->renewalCookiesForRequest($request);

        $this->assertSame([], $cookies);
    }

    public function test_renewal_skipped_when_response_already_sets_opt_out_cookie(): void
    {
        $request = Request::create('/login', 'GET');
        $request->cookies->set('pne_skip_funnel', '1');

        $middleware = app(RefreshFunnelSkipOptOutCookies::class);
        $response = $middleware->handle($request, function () {
            return response('ok')->withCookie(
                app(FunnelSkipService::class)->forgetOptOutCookie()
            );
        });

        $names = collect($response->headers->getCookies())->map->getName()->all();

        $this->assertContains('pne_skip_funnel', $names);
        $this->assertCount(1, $names);
    }
}
