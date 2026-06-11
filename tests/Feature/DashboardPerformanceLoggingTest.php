<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DashboardPerformanceLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_slow_dashboard_request_is_logged_as_warning_even_when_verbose_disabled(): void
    {
        Log::spy();

        config([
            'observability.dashboard_performance_log' => false,
            'observability.dashboard_performance_slow_ms' => 0,
            'observability.dashboard_performance_slow_queries' => 9999,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.szkolenia'));

        Log::shouldHaveReceived('channel')
            ->with('dashboard_perf')
            ->atLeast()
            ->once();
    }

    public function test_fast_dashboard_request_is_not_logged_when_verbose_disabled(): void
    {
        Log::spy();

        config([
            'observability.dashboard_performance_log' => false,
            'observability.dashboard_performance_slow_ms' => 60000,
            'observability.dashboard_performance_slow_queries' => 9999,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.szkolenia'));

        Log::shouldNotHaveReceived('channel');
    }

    public function test_dashboard_request_is_logged_when_verbose_enabled(): void
    {
        Log::spy();

        config([
            'observability.dashboard_performance_log' => true,
            'observability.dashboard_performance_slow_ms' => 60000,
            'observability.dashboard_performance_slow_queries' => 9999,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.szkolenia'));

        Log::shouldHaveReceived('channel')
            ->with('dashboard_perf')
            ->atLeast()
            ->once();
    }
}
