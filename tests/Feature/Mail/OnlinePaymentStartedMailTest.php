<?php

namespace Tests\Feature\Mail;

use App\Mail\OnlinePaymentStartedMail;
use App\Models\Course;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use Carbon\Carbon;
use Tests\TestCase;

class OnlinePaymentStartedMailTest extends TestCase
{
    public function test_online_payment_started_mail_contains_retry_and_deferred_links(): void
    {
        config([
            'mail.system.from_address' => 'info@system.pnedu.pl',
            'mail.system.from_name' => 'Platforma Nowoczesnej Edukacji',
            'mail.system.reply_to_address' => 'kontakt@pnedu.pl',
            'mail.brand.public_url' => 'https://pnedu.pl',
            'mail.brand.public_label' => 'www.pnedu.pl',
        ]);

        $mail = (new OnlinePaymentStartedMail(
            $this->formOrder(),
            $this->course(),
            $this->onlinePaymentOrder(),
            'https://pnedu.pl/orders/TEST/retry-payment?signature=abc',
            'https://pnedu.pl/courses/1/order-form?prefill_from=TEST&payment_type=deferred',
            'https://pnedu.pl/payment/pending/OPO1'
        ))->build();

        $this->assertStringContainsString('Płatność online', $mail->subject);

        $html = (new OnlinePaymentStartedMail(
            $this->formOrder(),
            $this->course(),
            $this->onlinePaymentOrder(),
            'https://pnedu.pl/orders/TEST/retry-payment?signature=abc',
            'https://pnedu.pl/courses/1/order-form?prefill_from=TEST&payment_type=deferred',
            'https://pnedu.pl/payment/pending/OPO1'
        ))->render();

        $this->assertStringContainsString('Zapłać ponownie', $html);
        $this->assertStringContainsString('retry-payment?signature=abc', $html);
        $this->assertStringContainsString('prefill_from=TEST', $html);
        $this->assertStringContainsString('payment/pending/OPO1', $html);
        $this->assertStringContainsString('kontakt@pnedu.pl', $html);
    }

    private function formOrder(): FormOrder
    {
        $order = new FormOrder([
            'ident' => '260531-TEST01',
            'order_date' => Carbon::parse('2026-05-31 12:00:00'),
            'product_id' => 1,
            'product_name' => 'Test szkolenie',
            'product_price' => 199,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            'orderer_email' => 'buyer@example.test',
        ]);
        $order->id = 123;

        return $order;
    }

    private function course(): Course
    {
        $course = new Course([
            'title' => 'Test szkolenie',
            'start_date' => Carbon::parse('2026-06-01 10:00:00'),
        ]);
        $course->id = 1;

        return $course;
    }

    private function onlinePaymentOrder(): OnlinePaymentOrder
    {
        $order = new OnlinePaymentOrder([
            'ident' => 'OPO1',
            'total_amount' => 199,
            'currency' => 'PLN',
        ]);
        $order->id = 1;

        return $order;
    }
}
