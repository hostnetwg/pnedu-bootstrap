<?php

namespace App\Mail;

use App\Models\FormOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\FormOrder
     */
    public $order;

    /**
     * The course instance.
     *
     * @var \App\Models\Course
     */
    public $course;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Course  $course
     * @return void
     */
    public function __construct(FormOrder $order, $course)
    {
        $this->order = $order->loadMissing('primaryParticipant');
        $this->course = $course;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Generuj PDF
        $pdf = Pdf::loadView('orders.pdf', [
            'order' => $this->order,
            'course' => $this->course,
        ]);

        $fileName = 'zamowienie-'.$this->order->ident.'.pdf';

        // Formatuj temat e-maila: "Twoje zamówienie #6312 - SZKOLENIE: Nazwa... (2026-03-19)"
        $courseTitle = str_replace('&nbsp;', ' ', strip_tags($this->order->product_name));
        $courseDate = $this->course && $this->course->start_date
            ? \Carbon\Carbon::parse($this->course->start_date)->format('Y-m-d')
            : '';
        $subject = 'Twoje zamówienie #'.$this->order->id.' - SZKOLENIE: '.$courseTitle.($courseDate ? ' ('.$courseDate.')' : '');

        return $this
            ->from(
                config('mail.system.from_address'),
                config('mail.system.from_name')
            )
            ->replyTo(
                config('mail.system.reply_to_address'),
                config('mail.system.reply_to_name')
            )
            ->subject($subject)
            ->view('emails.order-notification')
            ->attachData($pdf->output(), $fileName, [
                'mime' => 'application/pdf',
            ])
            ->with([
                'order' => $this->order,
                'course' => $this->course,
                'brandPublicUrl' => config('mail.brand.public_url'),
                'brandPublicLabel' => config('mail.brand.public_label'),
            ]);
    }
}
