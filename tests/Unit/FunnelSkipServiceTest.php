<?php

namespace Tests\Unit;

use App\Services\CoursePageViewTracker;
use App\Services\FunnelSkipService;
use App\Services\MarketingAttributionService;
use Illuminate\Http\Request;
use Tests\TestCase;

class FunnelSkipServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.funnel_skip_token' => 'unit-test-secret',
            'marketing.funnel_skip_cookie' => 'pne_skip_funnel',
            'marketing.funnel_skip_query_param' => 'pne_skip_funnel',
            'marketing.funnel_skip_token_param' => 'token',
        ]);
    }

    public function test_should_skip_when_opt_out_cookie_present(): void
    {
        $request = Request::create('/courses/1', 'GET');
        $request->cookies->set('pne_skip_funnel', '1');

        $this->assertTrue(app(FunnelSkipService::class)->shouldSkipTracking($request));
    }

    public function test_should_skip_when_enabling_via_query_on_same_request(): void
    {
        $request = Request::create('/?pne_skip_funnel=1&token=unit-test-secret', 'GET');

        $this->assertTrue(app(FunnelSkipService::class)->shouldSkipTracking($request));
    }

    public function test_invalid_token_does_not_skip(): void
    {
        $request = Request::create('/?pne_skip_funnel=1&token=wrong', 'GET');

        $this->assertFalse(app(FunnelSkipService::class)->shouldSkipTracking($request));
    }

    public function test_course_page_view_tracker_respects_opt_out_cookie(): void
    {
        $request = Request::create('/courses/1', 'GET');
        $request->cookies->set('pne_skip_funnel', '1');

        $tracker = new CoursePageViewTracker(
            app(MarketingAttributionService::class),
            app(FunnelSkipService::class),
        );

        $this->assertFalse($tracker->shouldTrack($request));
    }
}
