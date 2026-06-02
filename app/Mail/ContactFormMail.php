<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The form data.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new message instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
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
            ->subject('Nowa wiadomość z formularza kontaktowego')
            ->view('emails.contact')
            ->with('data', $this->data);
    }
}