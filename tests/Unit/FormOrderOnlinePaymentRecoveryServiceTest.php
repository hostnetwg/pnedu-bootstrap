<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use App\Services\FormOrderOnlineAbandonmentService;
use App\Services\FormOrderOnlinePaymentRecoveryService;
use App\Services\FormOrderOnlinePaymentRetryService;
use Carbon\Carbon;
use Tests\TestCase;

class FormOrderOnlinePaymentRecoveryServiceTest extends TestCase
{
    private FormOrderOnlinePaymentRecoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'order_form.online_abandonment_minutes' => 60,
            'order_form.online_recovery_enabled' => true,
        ]);
        $this->service = new FormOrderOnlinePaymentRecoveryService(
            new FormOrderOnlineAbandonmentService,
            new FormOrderOnlinePaymentRetryService(new FormOrderOnlineAbandonmentService)
        );
    }

    public function test_cancelled_online_is_eligible_for_automatic_recovery_immediately(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', 'UTC'));
        $order = $this->onlineOrder(FormOrder::PAYMENT_STATUS_CANCELLED, now('UTC')->subMinutes(5));

        $this->assertTrue($this->service->eligibleForAutomaticRecovery($order));
        Carbon::setTestNow();
    }

    public function test_recent_awaiting_online_is_not_eligible_for_automatic_recovery(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', 'UTC'));
        $order = $this->onlineOrder(
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            Carbon::parse('2026-08-22 11:30:00', 'UTC')
        );

        $this->assertFalse($this->service->eligibleForAutomaticRecovery($order));
        Carbon::setTestNow();
    }

    public function test_already_sent_blocks_automatic_recovery(): void
    {
        $order = $this->onlineOrder(FormOrder::PAYMENT_STATUS_FAILED, now('UTC')->subMinutes(5));
        $order->online_payment_recovery_sent_at = now('UTC')->subHour();

        $this->assertFalse($this->service->eligibleForAutomaticRecovery($order));
    }

    public function test_paid_order_is_not_eligible_for_manual_recovery(): void
    {
        $order = $this->onlineOrder(FormOrder::PAYMENT_STATUS_PAID, now('UTC')->subHour());

        $this->assertFalse($this->service->eligibleForManualRecovery($order));
    }

    private function onlineOrder(string $paymentStatus, Carbon $orderDate): FormOrder
    {
        return FormOrder::make([
            'ident' => '260822-REC01',
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => $paymentStatus,
            'order_date' => $orderDate,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
            'online_payment_recovery_sent_at' => null,
        ]);
    }
}
