<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class SystemVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return parent::toMail($notifiable)
            ->from(
                config('mail.system.from_address'),
                config('mail.system.from_name')
            )
            ->replyTo(
                config('mail.system.reply_to_address'),
                config('mail.system.reply_to_name')
            );
    }
}
