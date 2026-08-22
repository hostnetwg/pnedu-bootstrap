<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use App\Services\FormOrderOnlineAbandonmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FormOrderOnlineAbandonmentServiceTest extends TestCase
{
    private FormOrderOnlineAbandonmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['order_form.online_abandonment_minutes' => 60]);
        $this->service = new FormOrderOnlineAbandonmentService;
    }

    public function test_cancelled_online_is_abandoned_immediately(): void
    {
        $order = $this->onlineOrder(FormOrder::PAYMENT_STATUS_CANCELLED, now('UTC')->subMinutes(5));

        $this->assertTrue($this->service->isAbandonedUnpaidOnline($order));
    }

    public function test_failed_online_is_abandoned_immediately(): void
    {
        $order = $this->onlineOrder(FormOrder::PAYMENT_STATUS_FAILED, now('UTC')->subMinutes(5));

        $this->assertTrue($this->service->isAbandonedUnpaidOnline($order));
    }

    public function test_awaiting_online_within_threshold_is_not_abandoned(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', 'UTC'));
        $order = $this->onlineOrder(
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            Carbon::parse('2026-08-22 11:30:00', 'UTC')
        );

        $this->assertFalse($this->service->isAbandonedUnpaidOnline($order));
        Carbon::setTestNow();
    }

    public function test_awaiting_online_after_threshold_is_abandoned(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', 'UTC'));
        $order = $this->onlineOrder(
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            Carbon::parse('2026-08-22 10:30:00', 'UTC')
        );

        $this->assertTrue($this->service->isAbandonedUnpaidOnline($order));
        Carbon::setTestNow();
    }

    public function test_deferred_order_is_never_abandoned_unpaid_online(): void
    {
        $order = FormOrder::make([
            'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
            'payment_status' => FormOrder::PAYMENT_STATUS_SUBMITTED,
            'order_date' => now('UTC')->subDays(2),
        ]);

        $this->assertFalse($this->service->isAbandonedUnpaidOnline($order));
    }

    public function test_paid_online_is_not_abandoned(): void
    {
        $order = $this->onlineOrder(FormOrder::PAYMENT_STATUS_PAID, now('UTC')->subDays(2));

        $this->assertFalse($this->service->isAbandonedUnpaidOnline($order));
    }

    private function onlineOrder(string $paymentStatus, Carbon $orderDate): FormOrder
    {
        $order = FormOrder::make([
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => $paymentStatus,
            'order_date' => $orderDate,
            'status_completed' => false,
            'invoice_number' => null,
            'cancelled_at' => null,
        ]);
        $order->setRelation('onlinePaymentOrders', new Collection);

        return $order;
    }
}
