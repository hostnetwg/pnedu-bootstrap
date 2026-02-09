<?php

namespace App\Http\Controllers;

use App\Models\OnlinePaymentOrder;
use App\Services\PayUService;
use App\Services\PayNowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * PayU notify – webhook wywoływany przez PayU przy zmianie statusu płatności.
     */
    public function payuNotify(Request $request): \Illuminate\Http\Response
    {
        Log::info('PayU notify received', ['body' => $request->all()]);

        // PayU REST API wysyła JSON z order
        $orderId = $request->input('order.orderId');
        $extOrderId = $request->input('order.extOrderId');
        $status = $request->input('order.status');

        if (empty($extOrderId)) {
            Log::warning('PayU notify: brak extOrderId');
            return response('', 200); // PayU oczekuje 200
        }

        $order = OnlinePaymentOrder::where('ident', $extOrderId)->first();
        if (!$order) {
            Log::warning('PayU notify: nie znaleziono zamówienia', ['extOrderId' => $extOrderId]);
            return response('', 200);
        }

        // Statusy PayU: PENDING, NEW, COMPLETED, CANCELED, REJECTED itd.
        $payuStatus = strtoupper($status ?? '');
        if ($payuStatus === 'COMPLETED') {
            $order->update(['status' => OnlinePaymentOrder::STATUS_PAID]);
            Log::info('PayU: płatność potwierdzona', ['ident' => $extOrderId]);
            // TODO: rejestracja uczestnika, wysłanie maila, faktura
        } elseif (in_array($payuStatus, ['CANCELED', 'REJECTED', 'EXPIRED'])) {
            $order->update(['status' => OnlinePaymentOrder::STATUS_CANCELLED]);
        }

        return response('', 200);
    }

    /**
     * PayU return – przekierowanie użytkownika po zakończeniu płatności.
     * PayU może przekazać extOrderId w query (?extOrderId=xxx) lub w body (notify format).
     */
    public function payuReturn(Request $request)
    {
        $extOrderId = $request->query('extOrderId')
            ?? $request->query('orderId')
            ?? $request->input('order.extOrderId')
            ?? $request->input('extOrderId');

        if (empty($extOrderId)) {
            return redirect()->route('home')
                ->with('error', 'Brak identyfikatora zamówienia. Sprawdź e-mail z potwierdzeniem płatności.');
        }

        $order = OnlinePaymentOrder::where('ident', $extOrderId)->with('course')->first();
        if (!$order) {
            return redirect()->route('home')
                ->with('error', 'Nie znaleziono zamówienia.');
        }

        if ($order->isPaid()) {
            return redirect()->route('payment.success', $order->ident)
                ->with('success', 'Płatność została zrealizowana pomyślnie.');
        }

        return redirect()->route('payment.pending', $order->ident)
            ->with('info', 'Płatność jest w trakcie realizacji. Otrzymasz potwierdzenie na adres e-mail.');
    }

    /**
     * Strona sukcesu płatności.
     */
    public function success(string $ident)
    {
        $order = OnlinePaymentOrder::where('ident', $ident)->with('course')->firstOrFail();
        return view('payment.success', compact('order'));
    }

    /**
     * Strona oczekiwania na potwierdzenie płatności.
     */
    public function pending(string $ident)
    {
        $order = OnlinePaymentOrder::where('ident', $ident)->with('course')->firstOrFail();
        return view('payment.pending', compact('order'));
    }

    /**
     * PayNow notify – webhook wywoływany przez PayNow przy zmianie statusu płatności.
     */
    public function paynowNotify(Request $request): \Illuminate\Http\Response
    {
        Log::info('PayNow notify received', ['body' => $request->all()]);

        // PayNow wysyła JSON z paymentId, externalId, status, modifiedAt
        $paymentId = $request->input('paymentId');
        $externalId = $request->input('externalId');
        $status = $request->input('status');
        $signature = $request->header('Signature');

        if (empty($externalId)) {
            Log::warning('PayNow notify: brak externalId');
            return response('', 200); // PayNow oczekuje 200
        }

        // Weryfikuj podpis webhooka
        $paynowService = new PayNowService();
        $payload = $request->all();
        
        if ($signature && !$paynowService->verifyWebhookSignature($signature, $payload)) {
            Log::warning('PayNow notify: nieprawidłowy podpis', [
                'externalId' => $externalId,
                'paymentId' => $paymentId,
            ]);
            return response('', 200); // Zwróć 200, ale nie przetwarzaj
        }

        $order = OnlinePaymentOrder::where('ident', $externalId)->first();
        if (!$order) {
            Log::warning('PayNow notify: nie znaleziono zamówienia', ['externalId' => $externalId]);
            return response('', 200);
        }

        // Statusy PayNow: NEW, PENDING, CONFIRMED, ERROR, REJECTED, EXPIRED, CANCELLED
        $paynowStatus = strtoupper($status ?? '');
        if ($paynowStatus === 'CONFIRMED') {
            $order->update(['status' => OnlinePaymentOrder::STATUS_PAID]);
            Log::info('PayNow: płatność potwierdzona', ['ident' => $externalId, 'paymentId' => $paymentId]);
            // TODO: rejestracja uczestnika, wysłanie maila, faktura
        } elseif (in_array($paynowStatus, ['CANCELLED', 'REJECTED', 'EXPIRED', 'ERROR'])) {
            $order->update(['status' => OnlinePaymentOrder::STATUS_CANCELLED]);
            Log::info('PayNow: płatność anulowana', ['ident' => $externalId, 'status' => $paynowStatus]);
        } elseif ($paynowStatus === 'PENDING') {
            $order->update(['status' => OnlinePaymentOrder::STATUS_PENDING]);
        }

        return response('', 200);
    }

    /**
     * PayNow return – przekierowanie użytkownika po zakończeniu płatności.
     */
    public function paynowReturn(Request $request)
    {
        // PayNow może przekazać externalId w query lub w body
        $externalId = $request->query('externalId')
            ?? $request->query('paymentId')
            ?? $request->input('externalId');

        if (empty($externalId)) {
            return redirect()->route('home')
                ->with('error', 'Brak identyfikatora zamówienia. Sprawdź e-mail z potwierdzeniem płatności.');
        }

        $order = OnlinePaymentOrder::where('ident', $externalId)->with('course')->first();
        if (!$order) {
            // Może być paymentId zamiast externalId - spróbuj znaleźć po payu_order_id (gdzie przechowujemy paymentId)
            $order = OnlinePaymentOrder::where('payu_order_id', $externalId)->with('course')->first();
        }

        if (!$order) {
            return redirect()->route('home')
                ->with('error', 'Nie znaleziono zamówienia.');
        }

        if ($order->isPaid()) {
            return redirect()->route('payment.success', $order->ident)
                ->with('success', 'Płatność została zrealizowana pomyślnie.');
        }

        return redirect()->route('payment.pending', $order->ident)
            ->with('info', 'Płatność jest w trakcie realizacji. Otrzymasz potwierdzenie na adres e-mail.');
    }
}
