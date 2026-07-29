<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainingOfferInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function build()
    {
        return $this
            ->from(
                config('mail.system.from_address'),
                config('mail.system.from_name')
            )
            ->replyTo(
                $this->data['email'],
                $this->data['name']
            )
            ->subject('Zapytanie o szkolenie rady pedagogicznej: '.$this->data['offer_title'])
            ->view('emails.training-offer-inquiry')
            ->with('data', $this->data);
    }
}
