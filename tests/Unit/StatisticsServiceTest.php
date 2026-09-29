<?php

namespace Tests\Unit;

use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cached_statistics_are_returned_without_hitting_the_database(): void
    {
        Cache::put(StatisticsService::CACHE_KEY, [
            'trained_teachers' => 12345,
            'courses_this_year' => 210,
            'average_rating' => 4.86,
            'nps' => 72.5,
            'last_updated' => '2026-09-28T10:00:00+00:00',
        ]);

        $statistics = app(StatisticsService::class)->getStatistics();

        $this->assertSame(12345, $statistics['trained_teachers']);
        $this->assertSame('12 345', $statistics['trained_teachers_display']);
        $this->assertSame(210, $statistics['courses_this_year']);
        $this->assertSame('210', $statistics['courses_this_year_display']);
        $this->assertSame(4.86, $statistics['average_rating']);
        $this->assertSame('4.9', $statistics['average_rating_display']);
        $this->assertSame(72.5, $statistics['nps']);
        $this->assertSame('72.5', $statistics['nps_display']);
        $this->assertInstanceOf(Carbon::class, $statistics['last_updated']);
        $this->assertSame(12345, Cache::get(StatisticsService::LAST_GOOD_KEY)['trained_teachers']);
    }

    public function test_failed_calculation_keeps_last_good_values(): void
    {
        Cache::put(StatisticsService::LAST_GOOD_KEY, [
            'trained_teachers' => 15000,
            'courses_this_year' => 180,
            'average_rating' => 4.8,
            'nps' => 70.0,
            'last_updated' => '2026-09-01T08:00:00+00:00',
        ]);

        $service = Mockery::mock(StatisticsService::class)->makePartial();
        $service->shouldReceive('calculateStatistics')->once()->andReturn([
            'trained_teachers' => null,
            'courses_this_year' => null,
            'average_rating' => null,
            'nps' => null,
        ]);

        $statistics = $service->refreshStatistics();

        $this->assertSame(15000, $statistics['trained_teachers']);
        $this->assertSame('15 000', $statistics['trained_teachers_display']);
        $this->assertSame(180, $statistics['courses_this_year']);
        $this->assertEquals(4.8, $statistics['average_rating']);
        $this->assertEquals(70.0, $statistics['nps']);
        $this->assertSame('2026-09-01T08:00:00+00:00', $statistics['last_updated']->toIso8601String());
    }

    public function test_zero_counts_and_empty_ratings_are_not_published_without_a_previous_value(): void
    {
        $service = Mockery::mock(StatisticsService::class)->makePartial();
        $service->shouldReceive('calculateStatistics')->once()->andReturn([
            'trained_teachers' => 0,
            'courses_this_year' => 0,
            'average_rating' => 0,
            'nps' => null,
        ]);

        $statistics = $service->refreshStatistics();

        $this->assertNull($statistics['trained_teachers']);
        $this->assertNull($statistics['trained_teachers_display']);
        $this->assertNull($statistics['courses_this_year']);
        $this->assertNull($statistics['average_rating']);
        $this->assertNull($statistics['nps']);
        $this->assertNull($statistics['last_updated']);
    }

    public function test_fresh_values_replace_last_good_snapshot(): void
    {
        Cache::put(StatisticsService::LAST_GOOD_KEY, [
            'trained_teachers' => 1000,
            'courses_this_year' => 10,
            'average_rating' => 4.0,
            'nps' => 10.0,
            'last_updated' => '2026-01-01T00:00:00+00:00',
        ]);

        $service = Mockery::mock(StatisticsService::class)->makePartial();
        $service->shouldReceive('calculateStatistics')->once()->andReturn([
            'trained_teachers' => 16000,
            'courses_this_year' => 190,
            'average_rating' => 4.91,
            'nps' => 0.0,
        ]);

        $statistics = $service->refreshStatistics();

        $this->assertSame(16000, $statistics['trained_teachers']);
        $this->assertSame('16 000', $statistics['trained_teachers_display']);
        $this->assertSame('4.9', $statistics['average_rating_display']);
        $this->assertSame(0.0, $statistics['nps']);
        $this->assertSame('0', $statistics['nps_display']);
        $this->assertSame(16000, Cache::get(StatisticsService::LAST_GOOD_KEY)['trained_teachers']);
    }
}
