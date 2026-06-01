<?php

namespace Tests\Feature\Mail;

use App\Mail\OrderNotificationMail;
use App\Models\Course;
use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use Carbon\Carbon;
use Tests\TestCase;

class OrderNotificationMailTest extends TestCase
{
    public function test_order_notification_mail_uses_system_sender_reply_to_and_pnedu_branding(): void
    {
        config([
            'mail.system.from_address' => 'info@system.pnedu.pl',
            'mail.system.from_name' => 'Platforma Nowoczesnej Edukacji',
            'mail.system.reply_to_address' => 'kontakt@pnedu.pl',
            'mail.system.reply_to_name' => 'Platforma Nowoczesnej Edukacji',
            'mail.brand.public_url' => 'https://pnedu.pl',
            'mail.brand.public_label' => 'www.pnedu.pl',
        ]);

        $mail = (new OrderNotificationMail($this->order(), $this->course()))->build();

        $this->assertSame('info@system.pnedu.pl', $mail->from[0]['address']);
        $this->assertSame('Platforma Nowoczesnej Edukacji', $mail->from[0]['name']);
        $this->assertSame('kontakt@pnedu.pl', $mail->replyTo[0]['address']);
        $this->assertStringContainsString('Twoje zamówienie #123', $mail->subject);

        $html = (new OrderNotificationMail($this->order(), $this->course()))->render();

        $this->assertStringNotContainsString('nowoczesna-edukacja.pl', $html);
        $this->assertStringContainsString('kontakt@pnedu.pl', $html);
        $this->assertStringContainsString('www.pnedu.pl', $html);
        $this->assertStringContainsString('https://pnedu.pl', $html);
    }

    private function order(): FormOrder
    {
        $order = new FormOrder([
            'ident' => '260531-TEST01',
            'order_date' => Carbon::parse('2026-05-31 12:00:00'),
            'product_name' => 'Testowe szkolenie',
            'product_price' => 199,
            'orderer_name' => 'Jan Kowalski',
            'orderer_address' => 'Testowa 1',
            'orderer_postal_code' => '00-001',
            'orderer_city' => 'Warszawa',
            'orderer_phone' => '501654274',
            'orderer_email' => 'jan@example.test',
            'buyer_name' => 'Szkoła Testowa',
            'buyer_address' => 'Szkolna 2',
            'buyer_postal_code' => '00-002',
            'buyer_city' => 'Warszawa',
            'buyer_nip' => '1234567890',
            'invoice_payment_delay' => 14,
            'ip_address' => '127.0.0.1',
        ]);

        $order->id = 123;
        $order->setRelation('primaryParticipant', new FormOrderParticipant([
            'participant_firstname' => 'Anna',
            'participant_lastname' => 'Nowak',
            'participant_email' => 'anna@example.test',
            'is_primary' => true,
        ]));

        return $order;
    }

    private function course(): Course
    {
        $course = new Course([
            'title' => 'Testowe szkolenie',
            'start_date' => Carbon::parse('2026-06-15 10:00:00'),
        ]);

        $course->id = 456;

        return $course;
    }
}
