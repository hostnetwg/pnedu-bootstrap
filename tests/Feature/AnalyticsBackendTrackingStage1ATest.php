<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Course;
use App\Models\CoursePageStatsDaily;
use App\Models\MarketingCampaignStatsDaily;
use App\Services\Analytics\AnalyticsService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AnalyticsBackendTrackingStage1ATest extends TestCase
{
    private ?string $campaignCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        if (! $this->requiredTablesAvailable()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm w środowisku testowym.');
        }

        $this->campaignCode = 'a'.Str::lower(Str::random(10));
        $this->insertTestCampaign($this->campaignCode, $this->firstCourseId());
    }

    protected function tearDown(): void
    {
        if ($this->campaignCode !== null) {
            MarketingCampaignStatsDaily::query()
                ->where('campaign_code', $this->campaignCode)
                ->delete();

            DB::connection('pneadm')->table('marketing_campaigns')
                ->where('campaign_code', $this->campaignCode)
                ->delete();
        }

        parent::tearDown();
    }

    public function test_short_link_dispatches_visit_and_redirect_events_on_analytics_queue(): void
    {
        Queue::fake();

        $this->withHeader('Referer', 'https://facebook.com/post?fbclid=abc')
            ->get('/l/'.$this->campaignCode)
            ->assertRedirect();

        $this->assertAnalyticsEventQueued('campaign_short_link_visit');
        $this->assertAnalyticsEventQueued('campaign_redirect_resolved');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_utm_capture_dispatches_utm_captured_event_without_raw_query_string(): void
    {
        Queue::fake();

        $courseId = $this->firstCourseId();

        $this->get("/courses/{$courseId}?utm_campaign={$this->campaignCode}&utm_source=newsletter&utm_medium=email&utm_content=full_offer&utm_term=dyrektor")
            ->assertOk();

        $this->assertAnalyticsEventQueued('utm_captured');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_course_description_view_dispatches_event_and_keeps_old_aggregate_behavior(): void
    {
        Queue::fake();
        Cache::flush();

        $courseId = $this->firstCourseId();
        $before = $this->coursePageStat($courseId, 'views_course_show');

        $this->get("/courses/{$courseId}?utm_campaign={$this->campaignCode}&utm_source=newsletter&utm_medium=email")
            ->assertOk();

        $this->assertAnalyticsEventQueued('course_description_viewed');
        $this->assertSame($before + 1, $this->coursePageStat($courseId, 'views_course_show'));
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_order_form_view_dispatches_event_with_order_form_session_id(): void
    {
        Queue::fake();
        Cache::flush();

        [$courseId, $query] = $this->courseIdAndOrderFormQuery();

        $this->get("/courses/{$courseId}/order-form{$query}")
            ->assertOk();

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            return ($job->payload['event_name'] ?? null) === 'order_form_viewed'
                && ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && isset($job->payload['analytics_session_id'])
                && Str::isUuid($job->payload['analytics_session_id'])
                && isset($job->payload['order_form_session_id'])
                && Str::isUuid($job->payload['order_form_session_id']);
        });

        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_analytics_disabled_does_not_dispatch_events_and_request_still_works(): void
    {
        Queue::fake();
        config()->set('analytics.enabled', false);

        $this->get('/l/'.$this->campaignCode)
            ->assertRedirect();

        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
    }

    public function test_analytics_service_failure_does_not_break_course_page_request(): void
    {
        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis unavailable with secret@example.com'));
        $this->app->instance(AnalyticsService::class, $service);

        $this->get('/courses/'.$this->firstCourseId())
            ->assertOk();
    }

    private function assertAnalyticsEventQueued(string $eventName): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($eventName): bool {
            return ($job->payload['event_name'] ?? null) === $eventName
                && ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && isset($job->payload['analytics_session_id'])
                && Str::isUuid($job->payload['analytics_session_id']);
        });
    }

    private function assertEveryAnalyticsPayloadIsSafe(): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

            $this->assertArrayNotHasKey('url', $job->payload);
            $this->assertArrayNotHasKey('referrer', $job->payload);
            $this->assertStringNotContainsString('secret@example.com', $encoded);
            $this->assertStringNotContainsString('123456789', $encoded);
            $this->assertStringNotContainsString('fbclid=abc', $encoded);

            return true;
        });
    }

    private function insertTestCampaign(string $campaignCode, int $courseId): void
    {
        DB::connection('pneadm')->table('marketing_campaigns')->insert([
            'campaign_code' => $campaignCode,
            'name' => 'Analytics Stage 1A test campaign',
            'source_type_id' => $this->firstSourceTypeId(),
            'course_id' => $courseId,
            'landing_target' => 'course_show',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function requiredTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('marketing_campaigns')
                && Schema::connection('pneadm')->hasTable('marketing_campaign_stats_daily')
                && Schema::connection('pneadm')->hasTable('marketing_source_types')
                && Schema::connection('pneadm')->hasTable('course_page_stats_daily')
                && Schema::connection('pneadm')->hasTable('courses');
        } catch (\Throwable) {
            return false;
        }
    }

    private function firstSourceTypeId(): int
    {
        return (int) DB::connection('pneadm')->table('marketing_source_types')->orderBy('id')->value('id');
    }

    private function firstCourseId(): int
    {
        return (int) DB::connection('pneadm')->table('courses')->orderBy('id')->value('id');
    }

    private function coursePageStat(int $courseId, string $column): int
    {
        return (int) CoursePageStatsDaily::query()
            ->where('course_id', $courseId)
            ->where('stat_date', now()->toDateString())
            ->value($column);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function courseIdAndOrderFormQuery(): array
    {
        $course = Course::with('priceVariants')->findOrFail($this->firstCourseId());
        $activeVariants = $course->priceVariants
            ->filter(fn ($variant) => (bool) $variant->is_active)
            ->filter(fn ($variant) => $variant->isAvailableForCourseEndState($course->hasEnded()))
            ->values();

        if ($activeVariants->count() > 1) {
            return [$course->id, '?price_variant_id='.$activeVariants->first()->id];
        }

        return [$course->id, ''];
    }
}
