<?php

namespace Tests\Feature;

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
        Cache::put(UpcomingPneduCourses::cacheKey(), collect(['stale']), now()->addMinutes(10));

        $response = $this->postJson('/api/internal/cache/upcoming-courses', [], [
            'Authorization' => 'Bearer test-internal-token',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertNull(Cache::get(UpcomingPneduCourses::cacheKey()));
    }

    public function test_rejects_invalid_token(): void
    {
        Cache::put(UpcomingPneduCourses::cacheKey(), collect(['stale']), now()->addMinutes(10));

        $this->postJson('/api/internal/cache/upcoming-courses', [], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();

        $this->assertNotNull(Cache::get(UpcomingPneduCourses::cacheKey()));
    }
}
