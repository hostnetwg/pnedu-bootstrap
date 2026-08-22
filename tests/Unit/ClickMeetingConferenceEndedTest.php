<?php

namespace Tests\Unit;

use App\Services\ClickMeetingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClickMeetingConferenceEndedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.clickmeeting.url' => 'https://api.clickmeeting.com/v1/',
            'services.clickmeeting.token' => 'test-api-key',
        ]);
    }

    public function test_inactive_status_means_ended(): void
    {
        Http::fake([
            'api.clickmeeting.com/v1/conferences/10133895' => Http::response([
                'conference' => [
                    'id' => 10133895,
                    'status' => 'inactive',
                ],
            ], 200),
        ]);

        $result = app(ClickMeetingService::class)->isConferenceEnded('10133895');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['ended']);
        $this->assertSame('inactive', $result['status']);
    }

    public function test_active_status_means_not_ended(): void
    {
        Http::fake([
            'api.clickmeeting.com/v1/conferences/10133895' => Http::response([
                'conference' => [
                    'id' => 10133895,
                    'status' => 'active',
                ],
            ], 200),
        ]);

        $result = app(ClickMeetingService::class)->isConferenceEnded('10133895');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['ended']);
    }

    public function test_result_is_cached_briefly(): void
    {
        Http::fake([
            'api.clickmeeting.com/v1/conferences/10133895' => Http::sequence()
                ->push(['conference' => ['id' => 10133895, 'status' => 'active']], 200)
                ->push(['conference' => ['id' => 10133895, 'status' => 'inactive']], 200),
        ]);

        $service = app(ClickMeetingService::class);
        $first = $service->isConferenceEnded('10133895');
        $second = $service->isConferenceEnded('10133895');

        $this->assertFalse($first['ended']);
        $this->assertFalse($second['ended']);
        Http::assertSentCount(1);
    }
}
