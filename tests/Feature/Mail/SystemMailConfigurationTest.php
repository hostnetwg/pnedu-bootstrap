<?php

namespace Tests\Feature\Mail;

use App\Mail\ContactFormMail;
use App\Mail\PaymentNotificationMail;
use App\Models\Course;
use App\Models\OnlinePaymentOrder;
use App\Models\User;
use App\Notifications\SystemResetPassword;
use App\Notifications\SystemVerifyEmail;
use Tests\TestCase;

class SystemMailConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.system.from_address' => 'info@system.pnedu.pl',
            'mail.system.from_name' => 'Platforma Nowoczesnej Edukacji',
            'mail.system.reply_to_address' => 'kontakt@pnedu.pl',
            'mail.system.reply_to_name' => 'Platforma Nowoczesnej Edukacji',
        ]);
    }

    public function test_payment_notification_uses_system_sender_and_reply_to(): void
    {
        $mail = (new PaymentNotificationMail($this->onlinePaymentOrder()))->build();

        $this->assertSame('info@system.pnedu.pl', $mail->from[0]['address']);
        $this->assertSame('Platforma Nowoczesnej Edukacji', $mail->from[0]['name']);
        $this->assertSame('kontakt@pnedu.pl', $mail->replyTo[0]['address']);
    }

    public function test_contact_form_uses_system_sender_and_submitter_reply_to(): void
    {
        $mail = (new ContactFormMail([
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.test',
            'message' => 'Proszę o kontakt.',
        ]))->build();

        $this->assertSame('info@system.pnedu.pl', $mail->from[0]['address']);
        $this->assertSame('Platforma Nowoczesnej Edukacji', $mail->from[0]['name']);
        $this->assertSame('jan@example.test', $mail->replyTo[0]['address']);
        $this->assertSame('Jan Kowalski', $mail->replyTo[0]['name']);
    }

    public function test_breeze_reset_password_notification_uses_system_sender_and_reply_to(): void
    {
        $message = (new SystemResetPassword('reset-token'))->toMail($this->user());

        $this->assertSame(['info@system.pnedu.pl', 'Platforma Nowoczesnej Edukacji'], $message->from);
        $this->assertContains(['kontakt@pnedu.pl', 'Platforma Nowoczesnej Edukacji'], $message->replyTo);
    }

    public function test_breeze_verify_email_notification_uses_system_sender_and_reply_to(): void
    {
        $message = (new SystemVerifyEmail)->toMail($this->user());

        $this->assertSame(['info@system.pnedu.pl', 'Platforma Nowoczesnej Edukacji'], $message->from);
        $this->assertContains(['kontakt@pnedu.pl', 'Platforma Nowoczesnej Edukacji'], $message->replyTo);
    }

    private function onlinePaymentOrder(): OnlinePaymentOrder
    {
        $order = new OnlinePaymentOrder([
            'ident' => 'PNEDU_TEST_1',
            'status' => OnlinePaymentOrder::STATUS_PAID,
        ]);

        $order->setRelation('course', new Course([
            'title' => 'Testowe szkolenie',
        ]));

        return $order;
    }

    private function user(): User
    {
        $user = new User([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.test',
        ]);

        $user->id = 123;

        return $user;
    }
}
