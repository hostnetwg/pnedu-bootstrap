<?php

namespace Tests\Unit;

use Tests\TestCase;

class DashboardTransmisjaPresenceScriptTest extends TestCase
{
    public function test_script_beats_immediately_and_skips_bfcache_leave(): void
    {
        $source = file_get_contents(resource_path('views/dashboard/szkolenia-transmisja.blade.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('function beat()', $source);
        $this->assertStringContainsString('beat();', $source);
        $this->assertStringContainsString("addEventListener('pageshow', resumeLiveSignals)", $source);
        $this->assertStringContainsString('event.persisted', $source);
    }

    public function test_script_keeps_resource_bar_poll_alive_after_tab_return(): void
    {
        $source = file_get_contents(resource_path('views/dashboard/szkolenia-transmisja.blade.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('function resumeLiveSignals()', $source);
        $this->assertStringContainsString('function ensureMeetingStatusPollAlive()', $source);
        $this->assertStringContainsString("addEventListener('focus', resumeLiveSignals)", $source);
        $this->assertStringContainsString('Nie zatrzymujemy pollu przy ukrytej karcie', $source);
        $this->assertStringNotContainsString('stopMeetingStatusPoll();\n            return;', $source);
        $this->assertStringContainsString('res.status === 401 || res.status === 419', $source);
    }
}
