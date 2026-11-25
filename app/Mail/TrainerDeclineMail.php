<?php

namespace App\Mail;

use App\Models\ClosedTrainingCase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainerDeclineMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClosedTrainingCase $case,
        public string $trainerName,
        public string $reason,
        public string $caseUrl,
        public bool $withdrawn = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->withdrawn
                ? 'Trener wycofał rezygnację ze szkolenia zamkniętego'
                : 'Trener rezygnuje ze szkolenia zamkniętego',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.trainer-decline',
        );
    }
}
