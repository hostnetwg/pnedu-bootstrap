<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\OnlinePaymentOrder;
use App\Services\LegalDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class StandaloneOnlineOrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnlinePaymentOrder $order,
        public Course $course,
    ) {}

    public function build(): self
    {
        $documents = app(LegalDocumentService::class);
        $termsVersion = $this->order->terms_version ?: (string) config('legal.terms.current_version');

        return $this
            ->from(config('mail.system.from_address'), config('mail.system.from_name'))
            ->replyTo(config('mail.system.reply_to_address'), config('mail.system.reply_to_name'))
            ->subject('Potwierdzenie zamówienia — '.$this->course->plainTitle())
            ->view('emails.standalone-online-order-confirmation')
            ->attachData(
                $documents->termsPdf($termsVersion),
                "regulamin-pnedu-{$termsVersion}.pdf",
                ['mime' => 'application/pdf']
            )
            ->attachData(
                $documents->withdrawalFormPdf(),
                'wzor-odstapienia-od-umowy-pnedu.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
