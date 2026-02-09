<?php

namespace App\Http\Controllers;

use App\Models\OnlinePaymentOrder;
use App\Models\WebhookLog;
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
        $payload = $request->all();

        // Loguj webhook do bazy danych
        $webhookLog = WebhookLog::create([
            'online_payment_order_id' => 0, // Zaktualizujemy po znalezieniu zamówienia
            'payment_gateway' => 'payu',
            'gateway_payment_id' => $orderId,
            'external_id' => $extOrderId,
            'status' => $status,
            'payload' => $payload,
            'ip_address' => $request->ip(),
        ]);

        if (empty($extOrderId)) {
            Log::warning('PayU notify: brak extOrderId');
            $webhookLog->update(['error_message' => 'Brak extOrderId']);
            return response('', 200); // PayU oczekuje 200
        }

        $order = OnlinePaymentOrder::where('ident', $extOrderId)->first();
        if (!$order) {
            Log::warning('PayU notify: nie znaleziono zamówienia', ['extOrderId' => $extOrderId]);
            $webhookLog->update(['error_message' => 'Nie znaleziono zamówienia']);
            return response('', 200);
        }

        // Zaktualizuj webhook log z ID zamówienia
        $webhookLog->update(['online_payment_order_id' => $order->id]);

        // Statusy PayU: PENDING, NEW, COMPLETED, CANCELED, REJECTED itd.
        $payuStatus = strtoupper($status ?? '');
        $mappedStatus = WebhookLog::mapStatus('payu', $payuStatus);
        
        if ($mappedStatus) {
            $order->update(['status' => $mappedStatus]);
            $webhookLog->update([
                'status_mapped' => $mappedStatus,
                'signature_valid' => true, // PayU nie wymaga weryfikacji podpisu w REST API
            ]);
            
            if ($payuStatus === 'COMPLETED') {
                Log::info('PayU: płatność potwierdzona', ['ident' => $extOrderId]);
                // TODO: rejestracja uczestnika, wysłanie maila, faktura
            }
        } else {
            $webhookLog->update(['error_message' => 'Nieznany status: ' . $payuStatus]);
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
        $payload = $request->all();

        // Loguj webhook do bazy danych
        $webhookLog = WebhookLog::create([
            'online_payment_order_id' => 0, // Zaktualizujemy po znalezieniu zamówienia
            'payment_gateway' => 'paynow',
            'gateway_payment_id' => $paymentId,
            'external_id' => $externalId,
            'status' => $status,
            'payload' => $payload,
            'signature' => $signature,
            'ip_address' => $request->ip(),
        ]);

        if (empty($externalId)) {
            Log::warning('PayNow notify: brak externalId');
            $webhookLog->update(['error_message' => 'Brak externalId']);
            return response('', 200); // PayNow oczekuje 200
        }

        // Weryfikuj podpis webhooka
        $paynowService = new PayNowService();
        $signatureValid = false;
        
        if ($signature) {
            $signatureValid = $paynowService->verifyWebhookSignature($signature, $payload);
            $webhookLog->update(['signature_valid' => $signatureValid]);
            
            if (!$signatureValid) {
                Log::warning('PayNow notify: nieprawidłowy podpis', [
                    'externalId' => $externalId,
                    'paymentId' => $paymentId,
                ]);
                $webhookLog->update(['error_message' => 'Nieprawidłowy podpis webhooka']);
                return response('', 200); // Zwróć 200, ale nie przetwarzaj
            }
        }

        $order = OnlinePaymentOrder::where('ident', $externalId)->first();
        if (!$order) {
            Log::warning('PayNow notify: nie znaleziono zamówienia', ['externalId' => $externalId]);
            $webhookLog->update(['error_message' => 'Nie znaleziono zamówienia']);
            return response('', 200);
        }

        // Zaktualizuj webhook log z ID zamówienia
        $webhookLog->update(['online_payment_order_id' => $order->id]);

        // Statusy PayNow: NEW, PENDING, CONFIRMED, ERROR, REJECTED, EXPIRED, CANCELLED
        $paynowStatus = strtoupper($status ?? '');
        $mappedStatus = WebhookLog::mapStatus('paynow', $paynowStatus);
        
        if ($mappedStatus) {
            $order->update(['status' => $mappedStatus]);
            $webhookLog->update(['status_mapped' => $mappedStatus]);
            
            if ($paynowStatus === 'CONFIRMED') {
                Log::info('PayNow: płatność potwierdzona', ['ident' => $externalId, 'paymentId' => $paymentId]);
                // TODO: rejestracja uczestnika, wysłanie maila, faktura
            } elseif (in_array($paynowStatus, ['CANCELLED', 'REJECTED', 'EXPIRED', 'ERROR'])) {
                Log::info('PayNow: płatność anulowana', ['ident' => $externalId, 'status' => $paynowStatus]);
            }
        } else {
            $webhookLog->update(['error_message' => 'Nieznany status: ' . $paynowStatus]);
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
