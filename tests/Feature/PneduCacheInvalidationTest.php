<?php

namespace Tests\Feature;

use App\Models\SurveySetting;
use App\Support\UpcomingPneduCourses;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PneduCacheInvalidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_api.token' => 'test-internal-token']);
    }

    public function test_forgets_upcoming_courses_cache_with_valid_token(): void
    {
        Cache::put(UpcomingPneduCourses::cacheKey(null), collect(['stale-sidebar']), now()->addMinutes(10));
        Cache::put(UpcomingPneduCourses::cacheKey(UpcomingPneduCourses::HOMEPAGE_LIMIT), collect(['stale-home']), now()->addMinutes(10));

        $response = $this->postJson('/api/internal/cache/upcoming-courses', [], [
            'Authorization' => 'Bearer test-internal-token',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertNull(Cache::get(UpcomingPneduCourses::cacheKey(null)));
        $this->assertNull(Cache::get(UpcomingPneduCourses::cacheKey(UpcomingPneduCourses::HOMEPAGE_LIMIT)));
    }

    public function test_forgets_survey_settings_cache_with_valid_token(): void
    {
        Cache::put(SurveySetting::SETTINGS_CACHE_KEY, new SurveySetting(['allow_multiple_responses' => true]), 120);

        $response = $this->postJson('/api/internal/cache/survey-settings', [], [
            'Authorization' => 'Bearer test-internal-token',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertFalse(Cache::has(SurveySetting::SETTINGS_CACHE_KEY));
    }

    public function test_rejects_invalid_token(): void
    {
        Cache::put(UpcomingPneduCourses::cacheKey(null), collect(['stale']), now()->addMinutes(10));

        $this->postJson('/api/internal/cache/upcoming-courses', [], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();

        $this->assertNotNull(Cache::get(UpcomingPneduCourses::cacheKey(null)));
    }
}
