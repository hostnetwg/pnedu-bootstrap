<?php

namespace App\Mail;

use App\Models\FormOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

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
     * @param  \App\Models\FormOrder  $order
     * @param  \App\Models\Course  $course
     * @return void
     */
    public function __construct(FormOrder $order, $course)
    {
        $this->order = $order;
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
            'course' => $this->course
        ]);

        $fileName = 'zamowienie-' . $this->order->ident . '.pdf';

        // Formatuj temat e-maila
        $courseTitle = str_replace('&nbsp;', ' ', strip_tags($this->order->product_name));
        $subject = 'Twoje zamówienie #' . $this->order->id . ' - SZKOLENIE: ' . $courseTitle;

        return $this
            ->subject($subject)
            ->view('emails.order-notification')
            ->attachData($pdf->output(), $fileName, [
                'mime' => 'application/pdf',
            ])
            ->with([
                'order' => $this->order,
                'course' => $this->course
            ]);
    }
}
