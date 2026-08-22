<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use App\Services\FormOrderOnlineAbandonmentService;
use App\Services\FormOrderOnlinePaymentRetryService;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FormOrderOnlinePaymentRetryServiceTest extends TestCase
{
    private FormOrderOnlinePaymentRetryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['order_form.online_retry_signed_url_days' => 7]);
        $this->service = new FormOrderOnlinePaymentRetryService(new FormOrderOnlineAbandonmentService);
    }

    public function test_can_retry_unpaid_online_gateway_order(): void
    {
        $order = FormOrder::make([
            'ident' => '260822-TEST01',
            'product_id' => 1,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $this->assertTrue($this->service->canRetryPayment($order));
    }

    public function test_cannot_retry_paid_online_order(): void
    {
        $order = FormOrder::make([
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_PAID,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $this->assertFalse($this->service->canRetryPayment($order));
    }

    public function test_signed_retry_url_uses_named_route(): void
    {
        URL::forceRootUrl('https://pnedu.pl');

        $order = FormOrder::make([
            'ident' => '260822-RETRY01',
            'product_id' => 42,
            'course_price_variant_id' => null,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $url = $this->service->signedRetryUrl($order);

        $this->assertStringContainsString('/orders/260822-RETRY01/retry-payment', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_signed_convert_to_deferred_url_uses_named_route(): void
    {
        URL::forceRootUrl('https://pnedu.pl');

        $order = FormOrder::make([
            'ident' => '260822-CONV01',
            'product_id' => 42,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_FAILED,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $url = $this->service->signedConvertToDeferredUrl($order);

        $this->assertStringContainsString('/orders/260822-CONV01/convert-to-deferred', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_deferred_order_form_url_includes_prefill_from(): void
    {
        $order = FormOrder::make([
            'ident' => '260822-DEF01',
            'product_id' => 99,
            'course_price_variant_id' => 5,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_FAILED,
            'cancelled_at' => null,
            'invoice_number' => null,
            'status_completed' => 0,
        ]);

        $url = $this->service->deferredOrderFormUrl($order);

        $this->assertStringContainsString('prefill_from=260822-DEF01', $url);
        $this->assertStringContainsString('payment_type=deferred', $url);
        $this->assertStringContainsString('price_variant_id=5', $url);
    }
}
