<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnlinePaymentRecoveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FormOrder $order,
        public Course $course,
        public OnlinePaymentOrder $onlinePaymentOrder,
        public string $retryPaymentUrl,
        public string $deferredOrderFormUrl,
        public string $pendingPageUrl,
    ) {
        $this->order = $order->loadMissing([
            'primaryParticipant',
            'participants' => fn ($query) => $query->orderBy('id'),
        ]);
    }

    public function build(): self
    {
        $courseTitle = str_replace('&nbsp;', ' ', strip_tags($this->order->product_name));
        $courseDate = $this->course && $this->course->start_date
            ? \Carbon\Carbon::parse($this->course->start_date)->format('Y-m-d')
            : '';
        $subject = 'Przypomnienie o płatności — zamówienie #'.$this->order->id.' — '.$courseTitle.($courseDate ? ' ('.$courseDate.')' : '');

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
            ->view('emails.online-payment-recovery')
            ->with([
                'order' => $this->order,
                'course' => $this->course,
                'onlinePaymentOrder' => $this->onlinePaymentOrder,
                'retryPaymentUrl' => $this->retryPaymentUrl,
                'deferredOrderFormUrl' => $this->deferredOrderFormUrl,
                'pendingPageUrl' => $this->pendingPageUrl,
                'brandPublicUrl' => config('mail.brand.public_url'),
                'brandPublicLabel' => config('mail.brand.public_label'),
            ]);
    }
}
