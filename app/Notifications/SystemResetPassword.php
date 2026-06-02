<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SystemResetPassword extends ResetPassword
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
