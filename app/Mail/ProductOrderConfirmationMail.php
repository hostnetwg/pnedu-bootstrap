<?php

namespace App\Mail;

use App\Models\FormOrder;
use App\Services\LegalDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductOrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly FormOrder $order) {}

    public function build(): self
    {
        $this->order->loadMissing(['orderItems.recipients', 'participants']);
        $legalDocuments = app(LegalDocumentService::class);
        $termsVersion = $this->order->terms_version ?: (string) config('legal.terms.current_version');
        $specification = Pdf::loadView('legal.product-order-specification', [
            'order' => $this->order,
            'item' => $this->order->orderItems->first(),
        ]);

        return $this
            ->from(
                config('mail.system.from_address'),
                config('mail.system.from_name')
            )
            ->replyTo(
                config('mail.system.reply_to_address'),
                config('mail.system.reply_to_name')
            )
            ->subject('Potwierdzenie zamówienia '.$this->order->ident)
            ->view('emails.product-order-confirmation')
            ->attachData(
                $specification->output(),
                'specyfikacja-zamowienia-'.$this->order->ident.'.pdf',
                ['mime' => 'application/pdf']
            )
            ->attachData(
                $legalDocuments->termsPdf($termsVersion),
                "regulamin-pnedu-{$termsVersion}.pdf",
                ['mime' => 'application/pdf']
            )
            ->attachData(
                $legalDocuments->withdrawalFormPdf(),
                'wzor-odstapienia-od-umowy-pnedu.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
