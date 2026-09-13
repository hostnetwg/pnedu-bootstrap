<?php

namespace App\Notifications;

use App\Models\FormOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnlineCourseProductAccessGranted extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $courseTitle,
        private readonly ?string $passwordResetToken = null,
        private readonly ?FormOrder $order = null,
        private readonly ?string $participantFirstName = null,
        private readonly ?string $participantLastName = null,
        private readonly ?string $participantEmail = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dashboardUrl = route('dashboard.online-courses.index');
        $mail = (new MailMessage)
            ->subject('Dostęp do kursu online: '.$this->courseTitle)
            ->greeting('Dzień dobry!')
            ->line('Twój dostęp do kursu „'.$this->courseTitle.'” jest już aktywny.');

        if ($this->passwordResetToken !== null) {
            $setupUrl = route('password.set', [
                'token' => $this->passwordResetToken,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            $mail
                ->line('Utworzyliśmy dla Ciebie konto na pnedu.pl. Ustaw hasło, aby rozpocząć naukę.')
                ->action('Ustaw hasło i przejdź do kursu', $setupUrl);
        } else {
            $mail
                ->line('Kurs znajdziesz po zalogowaniu w sekcji „Kursy online”.')
                ->action('Przejdź do moich kursów', $dashboardUrl);
        }

        if ($this->shouldIncludeArt14()) {
            $received = collect([
                $this->participantFirstName || $this->participantLastName
                    ? 'imię i nazwisko'
                    : null,
                $this->participantEmail ? 'adres e-mail' : null,
                'wybrany kurs',
            ])->filter()->implode(', ');

            $mail->line(
                'Podmiotem zgłaszającym jest '.$this->orderingPartyName().'. '
                .'Otrzymaliśmy od niego '.$received
                .' w celu zapewnienia Ci dostępu do kursu „'.$this->courseTitle.'”. '
                .'Szczegółowe informacje o przetwarzaniu Twoich danych znajdziesz w informacji dla uczestników zgłoszonych przez inny podmiot: '
                .route('rodo.art14')
            );
        } elseif ($this->order) {
            $mail->line('Jeśli zamówienie składała szkoła lub inna osoba, dostęp jest przypisany do adresu e-mail, na który otrzymujesz tę wiadomość.');
        } else {
            $mail->line('Dostęp jest przypisany do adresu e-mail, na który otrzymujesz tę wiadomość.');
        }

        return $mail;
    }

    private function shouldIncludeArt14(): bool
    {
        if ($this->order === null || $this->participantEmail === null) {
            return false;
        }

        return strtolower(trim((string) $this->order->orderer_email))
            !== strtolower(trim($this->participantEmail));
    }

    private function orderingPartyName(): string
    {
        $name = trim((string) ($this->order?->buyer_name ?: $this->order?->orderer_name));

        return $name !== '' ? $name : 'zamawiający';
    }
}
