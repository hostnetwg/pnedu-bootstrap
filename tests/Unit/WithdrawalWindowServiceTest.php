<?php

namespace Tests\Unit;

use App\Services\WithdrawalWindowService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class WithdrawalWindowServiceTest extends TestCase
{
    private WithdrawalWindowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Europe/Warsaw']);
        $this->service = app(WithdrawalWindowService::class);
    }

    public function test_immediate_start_is_within_window(): void
    {
        $concluded = CarbonImmutable::parse('2026-09-13 21:00', 'Europe/Warsaw');

        $this->assertTrue($this->service->startsWithinWindow(null, $concluded));
    }

    public function test_start_in_three_days_is_within_window(): void
    {
        $concluded = CarbonImmutable::parse('2026-09-13 21:00', 'Europe/Warsaw');
        $start = $concluded->addDays(3);

        $this->assertTrue($this->service->startsWithinWindow($start, $concluded));
    }

    public function test_start_on_deadline_end_is_within_window(): void
    {
        $concluded = CarbonImmutable::parse('2026-09-01 10:00', 'Europe/Warsaw');
        $start = CarbonImmutable::parse('2026-09-15 23:59:59', 'Europe/Warsaw');

        $this->assertTrue($this->service->startsWithinWindow($start, $concluded));
    }

    public function test_start_after_deadline_is_outside_window(): void
    {
        $concluded = CarbonImmutable::parse('2026-09-01 23:50', 'Europe/Warsaw');
        $start = CarbonImmutable::parse('2026-09-16 00:00:00', 'Europe/Warsaw');

        $this->assertFalse($this->service->startsWithinWindow($start, $concluded));
    }
}
