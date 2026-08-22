<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use App\Services\FormOrderOnlineAbandonmentService;
use App\Services\FormOrderOnlinePaymentRetryService;
use App\Services\FormOrderOnlineToDeferredConversionService;
use Tests\TestCase;

class FormOrderOnlineToDeferredConversionServiceTest extends TestCase
{
    private FormOrderOnlineToDeferredConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'order_form.online_to_deferred_default_payment_terms' => 14,
            'order_form.online_to_deferred_max_payment_terms' => 30,
        ]);
        $this->service = new FormOrderOnlineToDeferredConversionService(
            new FormOrderOnlineAbandonmentService,
            new FormOrderOnlinePaymentRetryService(new FormOrderOnlineAbandonmentService)
        );
    }

    public function test_can_convert_unpaid_online_order(): void
    {
        $order = FormOrder::make([
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_FAILED,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $this->assertTrue($this->service->canConvert($order));
    }

    public function test_cannot_convert_paid_order(): void
    {
        $order = FormOrder::make([
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_PAID,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $this->assertFalse($this->service->canConvert($order));
    }

    public function test_default_and_max_payment_terms(): void
    {
        $this->assertSame(14, $this->service->defaultPaymentTermsDays());
        $this->assertSame(30, $this->service->maxPaymentTermsDays());
    }
}
