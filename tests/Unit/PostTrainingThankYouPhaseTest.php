<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Support\PostTrainingThankYouPhase;
use Carbon\Carbon;
use Tests\TestCase;

class PostTrainingThankYouPhaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-21 18:00:00', 'Europe/Warsaw'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_closing_threshold_is_twenty_percent_capped_at_forty_five_minutes(): void
    {
        $start = Carbon::parse('2026-08-21 17:00:00', 'Europe/Warsaw');
        $endTwoHours = Carbon::parse('2026-08-21 19:00:00', 'Europe/Warsaw');
        $endFourHours = Carbon::parse('2026-08-21 21:00:00', 'Europe/Warsaw');

        $this->assertSame(24, PostTrainingThankYouPhase::closingThresholdMinutes($start, $endTwoHours));
        $this->assertSame(45, PostTrainingThankYouPhase::closingThresholdMinutes($start, $endFourHours));
    }

    public function test_effective_end_falls_back_to_start_plus_one_hour(): void
    {
        $course = Course::make([
            'start_date' => Carbon::parse('2026-08-21 17:00:00', 'Europe/Warsaw'),
        ]);

        $start = Carbon::parse($course->start_date)->timezone('Europe/Warsaw');
        $effectiveEnd = PostTrainingThankYouPhase::effectiveEnd($course, $start);

        $this->assertSame('2026-08-21 18:00:00', $effectiveEnd->format('Y-m-d H:i:s'));
    }

    public function test_is_closing_phase_in_last_twenty_percent_of_two_hour_course(): void
    {
        $start = Carbon::parse('2026-08-21 17:00:00', 'Europe/Warsaw');
        $end = Carbon::parse('2026-08-21 19:00:00', 'Europe/Warsaw');

        $duringEarly = Carbon::parse('2026-08-21 18:30:00', 'Europe/Warsaw');
        $duringClosing = Carbon::parse('2026-08-21 18:40:00', 'Europe/Warsaw');

        $this->assertFalse(PostTrainingThankYouPhase::isClosingPhase($duringEarly, $start, $end, false));
        $this->assertTrue(PostTrainingThankYouPhase::isClosingPhase($duringClosing, $start, $end, false));
    }

    public function test_cm_ended_before_start_does_not_open_closing_phase(): void
    {
        $start = Carbon::parse('2026-08-21 19:00:00', 'Europe/Warsaw');
        $end = Carbon::parse('2026-08-21 21:00:00', 'Europe/Warsaw');
        $beforeStart = Carbon::parse('2026-08-21 18:30:00', 'Europe/Warsaw');

        $this->assertFalse(PostTrainingThankYouPhase::isClosingPhase($beforeStart, $start, $end, true));
    }

    public function test_cm_ended_after_start_opens_closing_phase_early(): void
    {
        $start = Carbon::parse('2026-08-21 17:00:00', 'Europe/Warsaw');
        $end = Carbon::parse('2026-08-21 19:00:00', 'Europe/Warsaw');
        $during = Carbon::parse('2026-08-21 17:15:00', 'Europe/Warsaw');

        $this->assertTrue(PostTrainingThankYouPhase::isClosingPhase($during, $start, $end, true));
    }
}
