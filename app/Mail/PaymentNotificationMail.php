<?php

namespace App\Mail;

use App\Models\OnlinePaymentOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\OnlinePaymentOrder
     */
    public $order;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\OnlinePaymentOrder  $order
     * @return void
     */
    public function __construct(OnlinePaymentOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $courseTitle = $this->order->course ? strip_tags($this->order->course->title) : 'Nieznane szkolenie';
        $subject = 'Nowa płatność online #' . $this->order->ident . ' - ' . $courseTitle;

        return $this
            ->subject($subject)
            ->view('emails.payment-notification')
            ->with([
                'order' => $this->order,
                'course' => $this->order->course,
            ]);
    }
}
