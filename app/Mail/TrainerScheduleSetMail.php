<?php

namespace App\Mail;

use App\Models\ClosedTrainingCase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainerScheduleSetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClosedTrainingCase $case,
        public string $trainerName,
        public string $whenLabel,
        public string $caseUrl,
        public string $note = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Trener podał termin szkolenia zamkniętego',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.trainer-schedule-set',
        );
    }
}
