<?php

namespace App\Services;

use App\Mail\OnlinePaymentStartedMail;
use App\Models\Course;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use App\Models\PaymentDisplayOption;
use App\Support\DeveloperOnlinePaymentTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Ponowienie płatności online dla zamówienia formularza (form_orders) — Etap 2.
 */
class FormOrderOnlinePaymentRetryService
{
    public function __construct(
        private readonly FormOrderOnlineAbandonmentService $abandonmentService,
    ) {}

    public function retrySignedUrlDays(): int
    {
        return max(1, (int) config('order_form.online_retry_signed_url_days', 7));
    }

    public function canRetryPayment(FormOrder $formOrder): bool
    {
        return $this->abandonmentService->isUnpaidOnlineGatewayOrder($formOrder);
    }

    public function signedRetryUrl(FormOrder $formOrder): string
    {
        return URL::temporarySignedRoute(
            'orders.retry-payment',
            now()->addDays($this->retrySignedUrlDays()),
            ['ident' => $formOrder->ident]
        );
    }

    public function deferredOrderFormUrl(FormOrder $formOrder): string
    {
        $params = ['id' => $formOrder->product_id];

        if ($formOrder->course_price_variant_id) {
            $params['price_variant_id'] = $formOrder->course_price_variant_id;
        }

        $params['prefill_from'] = $formOrder->ident;
        $params['payment_type'] = 'deferred';

        return route('payment.order-form', $params);
    }

    public function createRetryPaymentAttempt(FormOrder $formOrder, ?string $ipAddress = null): OnlinePaymentOrder
    {
        $formOrder->loadMissing([
            'onlinePaymentOrders' => fn ($query) => $query->orderByDesc('id'),
            'primaryParticipant',
            'participants',
        ]);

        $latestAttempt = $formOrder->onlinePaymentOrders->first();
        $primaryParticipant = $formOrder->primaryParticipant ?? $formOrder->participants->first();

        $buyerType = $latestAttempt?->buyer_type
            ?? (trim((string) ($formOrder->buyer_nip ?? '')) !== '' ? 'organisation' : 'person');

        $paymentGateway = $latestAttempt?->payment_gateway ?? 'payu';
        $totalAmount = $this->resolveCheckoutAmount((float) $formOrder->product_price);

        if ($totalAmount <= 0) {
            throw new \RuntimeException('Kwota płatności musi być większa od zera.');
        }

        $firstName = $latestAttempt?->first_name
            ?? trim((string) ($primaryParticipant?->participant_firstname ?? ''));
        $lastName = $latestAttempt?->last_name
            ?? trim((string) ($primaryParticipant?->participant_lastname ?? ''));
        $email = $latestAttempt?->email
            ?? trim((string) ($primaryParticipant?->participant_email ?? $formOrder->orderer_email ?? ''));
        $phone = $latestAttempt?->phone ?? trim((string) ($formOrder->orderer_phone ?? ''));

        $onlineOrder = OnlinePaymentOrder::create([
            'form_order_id' => $formOrder->id,
            'ident' => OnlinePaymentOrder::generateIdent(),
            'course_id' => $formOrder->product_id,
            'payment_gateway' => $paymentGateway,
            'status' => OnlinePaymentOrder::STATUS_PENDING,
            'total_amount' => $totalAmount,
            'currency' => 'PLN',
            'buyer_type' => $buyerType === 'organisation' ? 'organisation' : 'person',
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'order_comment' => $formOrder->invoice_notes,
            'address_data' => $latestAttempt?->address_data,
            'form_data' => $latestAttempt?->form_data ?? [],
            'ip_address' => $ipAddress,
        ]);

        if ($formOrder->payment_status !== FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT) {
            $formOrder->update(['payment_status' => FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT]);
        }

        return $onlineOrder;
    }

    public function redirectToGateway(OnlinePaymentOrder $onlineOrder): RedirectResponse
    {
        if ($onlineOrder->payment_gateway === 'paynow') {
            $paynowService = $this->makePayNowService();
            $result = $paynowService->createOrder(
                $onlineOrder,
                route('payment.paynow.notify'),
                route('payment.paynow.return')
            );

            if (! ($result['success'] ?? false)) {
                return redirect()
                    ->route('payment.pending', $onlineOrder->ident)
                    ->with('error', $result['error'] ?? 'Nie udało się połączyć z PayNow. Spróbuj ponownie.');
            }

            return redirect()->away($result['redirect_url']);
        }

        $payuService = $this->makePayUService();
        $result = $payuService->createOrder(
            $onlineOrder,
            route('payment.payu.notify'),
            route('payment.payu.return')
        );

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('payment.pending', $onlineOrder->ident)
                ->with('error', $result['error'] ?? 'Nie udało się połączyć z PayU. Spróbuj ponownie.');
        }

        session(['payu_order_ident' => $onlineOrder->ident]);
        session(['payu_order_email' => $onlineOrder->email]);

        return redirect()->away($result['redirect_uri']);
    }

    public function sendPaymentStartedMail(
        FormOrder $formOrder,
        Course $course,
        OnlinePaymentOrder $onlineOrder
    ): void {
        $emailsToSend = [];

        $ordererEmail = trim((string) ($formOrder->orderer_email ?? ''));
        if ($ordererEmail !== '') {
            $emailsToSend[] = strtolower($ordererEmail);
        }

        $formOrder->loadMissing(['participants' => fn ($query) => $query->orderBy('id')]);

        foreach ($formOrder->participants as $participant) {
            $participantEmail = strtolower(trim((string) ($participant->participant_email ?? '')));
            if ($participantEmail === '' || in_array($participantEmail, $emailsToSend, true)) {
                continue;
            }
            $emailsToSend[] = $participantEmail;
        }

        if ($emailsToSend === []) {
            return;
        }

        $retryUrl = $this->signedRetryUrl($formOrder);
        $deferredUrl = $this->deferredOrderFormUrl($formOrder);
        $pendingUrl = route('payment.pending', $onlineOrder->ident);

        foreach ($emailsToSend as $email) {
            try {
                Mail::to($email)->send(new OnlinePaymentStartedMail(
                    $formOrder,
                    $course,
                    $onlineOrder,
                    $retryUrl,
                    $deferredUrl,
                    $pendingUrl
                ));
            } catch (\Throwable $exception) {
                Log::error('Błąd wysyłki e-maila po starcie płatności online: '.$exception->getMessage(), [
                    'form_order_id' => $formOrder->id,
                    'form_order_ident' => $formOrder->ident,
                    'online_payment_order_id' => $onlineOrder->id,
                    'email' => $email,
                ]);
            }
        }
    }

    public function resolveCheckoutAmount(float $normalAmount): float
    {
        return DeveloperOnlinePaymentTest::resolveCheckoutAmount(
            $normalAmount,
            PaymentDisplayOption::getForCoursePage(),
            auth()->user()
        );
    }

    private function makePayUService(): \App\Services\PayUService
    {
        return new \App\Services\PayUService(
            DeveloperOnlinePaymentTest::sandboxGatewayOverride(
                PaymentDisplayOption::getForCoursePage(),
                auth()->user()
            )
        );
    }

    private function makePayNowService(): \App\Services\PayNowService
    {
        return new \App\Services\PayNowService(
            DeveloperOnlinePaymentTest::sandboxGatewayOverride(
                PaymentDisplayOption::getForCoursePage(),
                auth()->user()
            )
        );
    }
}
