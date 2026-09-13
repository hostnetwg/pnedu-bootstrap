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
        $this->order->loadMissing(['course', 'formOrder.orderItems']);
        $productTitle = strip_tags($this->order->displayProductName());
        $subject = 'Nowa płatność online #'.$this->order->ident.' - '.$productTitle;

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
            ->view('emails.payment-notification')
            ->with([
                'order' => $this->order,
                'course' => $this->order->course,
                'productTitle' => $productTitle,
            ]);
    }
}
