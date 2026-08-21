<?php

namespace Tests\Unit;

use App\Services\LiveTransmissionPresenceService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LiveTransmissionPresenceServiceTest extends TestCase
{
    private LiveTransmissionPresenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = app(LiveTransmissionPresenceService::class);
    }

    public function test_same_session_can_reacquire(): void
    {
        $a = $this->service->acquire(10, 'sess-a', 90);
        $b = $this->service->acquire(10, 'sess-a', 90);

        $this->assertTrue($a['ok']);
        $this->assertTrue($b['ok']);
    }

    public function test_other_session_is_blocked(): void
    {
        $this->service->acquire(10, 'sess-a', 90);
        $other = $this->service->acquire(10, 'sess-b', 90);

        $this->assertFalse($other['ok']);
        $this->assertStringContainsString('innym urządzeniu', (string) ($other['error'] ?? ''));
        $this->assertStringContainsString('nieużywane', (string) ($other['error'] ?? ''));
    }

    public function test_release_allows_other_session(): void
    {
        $this->service->acquire(10, 'sess-a', 90);
        $this->service->release(10, 'sess-a');
        $other = $this->service->acquire(10, 'sess-b', 90);

        $this->assertTrue($other['ok']);
    }

    public function test_heartbeat_refreshes_only_for_owner(): void
    {
        $this->service->acquire(10, 'sess-a', 90);

        $this->assertTrue($this->service->heartbeat(10, 'sess-a', 90));
        $this->assertFalse($this->service->heartbeat(10, 'sess-b', 90));
    }
}
