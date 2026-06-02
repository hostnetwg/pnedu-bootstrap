<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerificationReminder extends VerifyEmail
{
    public function __construct(
        protected int $daysSinceRegistration,
    ) {}

    public function toMail($notifiable): MailMessage
    {
        $copy = match ($this->daysSinceRegistration) {
            3 => [
                'subject' => 'Przypomnienie: potwierdź adres e-mail',
                'intro' => 'Kilka dni temu założyłeś/aś konto na Platformie Nowoczesnej Edukacji. Aby korzystać z panelu użytkownika, kliknij link weryfikacyjny poniżej.',
            ],
            83 => [
                'subject' => 'Za 7 dni usuniemy niezweryfikowane konto',
                'intro' => 'Twój adres e-mail nadal nie został potwierdzony. Za tydzień niezweryfikowane konto zostanie usunięte — chyba że masz zapis na płatne szkolenie powiązane z tym adresem e-mail.',
            ],
            89 => [
                'subject' => 'Ostatnie przypomnienie: potwierdź e-mail do jutra',
                'intro' => 'Jutro niezweryfikowane konto zostanie usunięte. Kliknij link poniżej, aby zachować dostęp do panelu użytkownika.',
            ],
            default => [
                'subject' => 'Potwierdź adres e-mail',
                'intro' => 'Potwierdź swój adres e-mail, aby korzystać z panelu użytkownika.',
            ],
        };

        return (new MailMessage)
            ->subject($copy['subject'])
            ->line($copy['intro'])
            ->action('Potwierdź adres e-mail', $this->verificationUrl($notifiable))
            ->line('Jeśli podałeś/aś błędny adres e-mail, zaloguj się i popraw go w profilu użytkownika.')
            ->line('Jeśli to nie Ty zakładałeś/aś konto, zignoruj tę wiadomość.')
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
