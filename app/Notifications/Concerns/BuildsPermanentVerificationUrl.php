<?php

namespace App\Notifications\Concerns;

use Illuminate\Support\Facades\URL;

/**
 * Link weryfikacyjny bez daty wygaśnięcia — kliknięcie w wiadomość z skrzynki ma działać także po wielu dniach.
 */
trait BuildsPermanentVerificationUrl
{
    protected function verificationUrl($notifiable): string
    {
        return URL::route('verification.verify', [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
    }
}
