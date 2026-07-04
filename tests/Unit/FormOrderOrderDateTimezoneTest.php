<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormOrderOrderDateTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Europe/Warsaw']);
        config(['database.connections.pneadm.timezone' => '+00:00']);
    }

    public function test_order_date_stores_utc_and_displays_warsaw_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04 21:11:00', 'Europe/Warsaw'));

        $order = FormOrder::create([
            'ident' => 'TEST-TZ-001',
            'order_date' => now('UTC'),
            'product_name' => 'Test TZ',
            'orderer_email' => 'tz@test.local',
        ]);

        $this->assertSame('2026-07-04 19:11:00', $order->getRawOriginal('order_date'));
        $this->assertSame('04.07.2026 21:11', $order->fresh()->formatOrderDateLocal());

        Carbon::setTestNow();
    }
}
