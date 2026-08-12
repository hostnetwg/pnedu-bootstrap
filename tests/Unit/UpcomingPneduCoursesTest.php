<?php

namespace Tests\Unit;

use App\Support\UpcomingPneduCourses;
use Tests\TestCase;

class UpcomingPneduCoursesTest extends TestCase
{
    public function test_sidebar_cache_key_is_unlimited_by_default(): void
    {
        $this->assertSame('dashboard.upcoming-offer.sidebar.v2.all', UpcomingPneduCourses::cacheKey());
        $this->assertSame('dashboard.upcoming-offer.sidebar.v2.all', UpcomingPneduCourses::cacheKey(null));
    }

    public function test_homepage_cache_key_includes_limit(): void
    {
        $this->assertSame(
            'dashboard.upcoming-offer.sidebar.v2.9',
            UpcomingPneduCourses::cacheKey(UpcomingPneduCourses::HOMEPAGE_LIMIT)
        );
    }
}
