<?php

namespace App\Http\Controllers;

use App\Models\OnlinePaymentOrder;
use App\Models\WebhookLog;
use App\Models\Participant;
use App\Models\CoursePriceVariant;
use App\Services\PayUService;
use App\Services\PayNowService;
use App\Mail\PaymentNotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

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
                Log::info('PayU: płatność potwierdzona - rozpoczynam przetwarzanie', [
                    'ident' => $extOrderId,
                    'order_id' => $order->id,
                    'mapped_status' => $mappedStatus,
                    'current_status' => $order->status,
                ]);
                // Odśwież model, aby mieć aktualny status
                $order->refresh();
                Log::info('PayU: status zamówienia po refresh', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'is_paid' => $order->isPaid(),
                ]);
                $this->registerParticipant($order);
                $this->sendPaymentNotificationEmail($order);
            } else {
                Log::info('PayU: status nie jest COMPLETED', [
                    'ident' => $extOrderId,
                    'payu_status' => $payuStatus,
                    'mapped_status' => $mappedStatus,
                ]);
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
        // Loguj wszystkie dane przychodzące z PayU dla debugowania
        Log::info('PayU return received', [
            'query_params' => $request->query(),
            'post_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        // Spróbuj znaleźć extOrderId w różnych miejscach
        // PayU może przekazywać dane w query string, POST body lub JSON (jak w notify)
        $extOrderId = $request->query('extOrderId')
            ?? $request->query('orderId')
            ?? $request->input('order.extOrderId')
            ?? $request->input('extOrderId')
            ?? $request->input('orderId')
            ?? $request->json('order.extOrderId')
            ?? $request->json('extOrderId');

        // Jeśli mamy orderId (PayU order ID), spróbuj znaleźć zamówienie po payu_order_id
        $payuOrderId = $request->query('orderId') 
            ?? $request->input('orderId')
            ?? $request->json('order.orderId')
            ?? $request->json('orderId');
        
        $order = null;
        
        // 1. Spróbuj znaleźć po extOrderId z URL/body
        if (!empty($extOrderId)) {
            $order = OnlinePaymentOrder::where('ident', $extOrderId)->with('course')->first();
        }
        
        // 2. Jeśli nie znaleziono, spróbuj po payu_order_id
        if (!$order && !empty($payuOrderId)) {
            $order = OnlinePaymentOrder::where('payu_order_id', $payuOrderId)->with('course')->first();
            
            if ($order) {
                Log::info('PayU return: znaleziono zamówienie po payu_order_id', [
                    'payu_order_id' => $payuOrderId,
                    'order_ident' => $order->ident,
                ]);
            }
        }

        // 3. Fallback: sprawdź sesję (zapisaliśmy ident przed przekierowaniem)
        if (!$order) {
            $sessionOrderIdent = session('payu_order_ident');
            if ($sessionOrderIdent) {
                $order = OnlinePaymentOrder::where('ident', $sessionOrderIdent)->with('course')->first();
                
                if ($order) {
                    Log::info('PayU return: znaleziono zamówienie z sesji', [
                        'order_ident' => $order->ident,
                    ]);
                    // Usuń z sesji po użyciu
                    session()->forget('payu_order_ident');
                    session()->forget('payu_order_email');
                }
            }
        }

        // 4. Fallback: spróbuj znaleźć ostatnie zamówienie użytkownika po email z sesji
        if (!$order) {
            $sessionEmail = session('payu_order_email');
            if ($sessionEmail) {
                $order = OnlinePaymentOrder::where('email', $sessionEmail)
                    ->where('payment_gateway', 'payu')
                    ->orderBy('created_at', 'desc')
                    ->with('course')
                    ->first();
                
                if ($order) {
                    Log::info('PayU return: znaleziono ostatnie zamówienie po email z sesji', [
                        'email' => $sessionEmail,
                        'order_ident' => $order->ident,
                    ]);
                    session()->forget('payu_order_email');
                }
            }
        }

        // 5. Fallback: spróbuj znaleźć ostatnie zamówienie PayU z tego IP (ostatnie 5 minut)
        if (!$order) {
            $userIp = $request->ip();
            $order = OnlinePaymentOrder::where('payment_gateway', 'payu')
                ->where('ip_address', $userIp)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderBy('created_at', 'desc')
                ->with('course')
                ->first();
            
            if ($order) {
                Log::info('PayU return: znaleziono ostatnie zamówienie po IP', [
                    'ip' => $userIp,
                    'order_ident' => $order->ident,
                ]);
            }
        }

        // Jeśli nadal nie znaleziono zamówienia, ale mamy payuOrderId, spróbuj użyć API PayU
        if (!$order && !empty($payuOrderId)) {
            try {
                $payuService = new PayUService();
                $orderStatus = $payuService->getOrderStatus($payuOrderId);
                
                if ($orderStatus && isset($orderStatus['orders'][0]['extOrderId'])) {
                    $extOrderIdFromApi = $orderStatus['orders'][0]['extOrderId'];
                    $order = OnlinePaymentOrder::where('ident', $extOrderIdFromApi)->with('course')->first();
                    
                    if ($order) {
                        Log::info('PayU return: znaleziono zamówienie przez API PayU', [
                            'payu_order_id' => $payuOrderId,
                            'extOrderId_from_api' => $extOrderIdFromApi,
                            'order_ident' => $order->ident,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('PayU return: błąd podczas pobierania statusu z API', [
                    'payu_order_id' => $payuOrderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Jeśli nadal nie znaleziono, ale mamy payuOrderId, spróbuj użyć API PayU
        if (!$order && !empty($payuOrderId)) {
            try {
                $payuService = new PayUService();
                $orderStatus = $payuService->getOrderStatus($payuOrderId);
                
                if ($orderStatus && isset($orderStatus['orders'][0]['extOrderId'])) {
                    $extOrderIdFromApi = $orderStatus['orders'][0]['extOrderId'];
                    $order = OnlinePaymentOrder::where('ident', $extOrderIdFromApi)->with('course')->first();
                    
                    if ($order) {
                        Log::info('PayU return: znaleziono zamówienie przez API PayU', [
                            'payu_order_id' => $payuOrderId,
                            'extOrderId_from_api' => $extOrderIdFromApi,
                            'order_ident' => $order->ident,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('PayU return: błąd podczas pobierania statusu z API', [
                    'payu_order_id' => $payuOrderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$order) {
            Log::warning('PayU return: nie znaleziono zamówienia', [
                'extOrderId' => $extOrderId,
                'payuOrderId' => $payuOrderId,
                'session_order_ident' => session('payu_order_ident'),
                'session_email' => session('payu_order_email'),
                'user_ip' => $request->ip(),
                'query_params' => $request->query(),
                'post_data' => $request->all(),
            ]);
            return redirect()->route('home')
                ->with('error', 'Nie znaleziono zamówienia. Sprawdź e-mail z potwierdzeniem płatności lub skontaktuj się z obsługą.');
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
                Log::info('PayNow: płatność potwierdzona - rozpoczynam przetwarzanie', [
                    'ident' => $externalId,
                    'paymentId' => $paymentId,
                    'order_id' => $order->id,
                    'mapped_status' => $mappedStatus,
                    'current_status' => $order->status,
                ]);
                // Odśwież model, aby mieć aktualny status
                $order->refresh();
                Log::info('PayNow: status zamówienia po refresh', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'is_paid' => $order->isPaid(),
                ]);
                $this->registerParticipant($order);
                $this->sendPaymentNotificationEmail($order);
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

    /**
     * Zarejestruj uczestnika w tabeli participants po potwierdzeniu płatności.
     * 
     * @param OnlinePaymentOrder $order
     * @return Participant|null
     */
    protected function registerParticipant(OnlinePaymentOrder $order): ?Participant
    {
        try {
            // Załaduj kurs z relacją
            $order->load('course');
            
            if (!$order->course) {
                Log::error('PaymentController: brak kursu dla zamówienia', [
                    'order_id' => $order->id,
                    'course_id' => $order->course_id,
                ]);
                return null;
            }

            // Sprawdź czy uczestnik już istnieje (po email i course_id)
            $existingParticipant = Participant::where('course_id', $order->course_id)
                ->where('email', $order->email)
                ->first();

            if ($existingParticipant) {
                Log::info('PaymentController: uczestnik już istnieje', [
                    'participant_id' => $existingParticipant->id,
                    'course_id' => $order->course_id,
                    'email' => $order->email,
                ]);
                return $existingParticipant;
            }

            // Oblicz kolejność (order) - następny numer po ostatnim uczestniku tego kursu
            $maxOrder = Participant::where('course_id', $order->course_id)
                ->max('order') ?? 0;
            $nextOrder = $maxOrder + 1;

            // Oblicz datę wygaśnięcia dostępu na podstawie wariantu cenowego
            $accessExpiresAt = $this->calculateAccessExpirationDate($order);

            // Utwórz uczestnika
            $participant = Participant::create([
                'course_id' => $order->course_id,
                'order' => $nextOrder,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'email' => $order->email,
                'birth_date' => null, // Dane urodzenia nie są dostępne w zamówieniu
                'birth_place' => null,
                'access_expires_at' => $accessExpiresAt,
            ]);

            Log::info('PaymentController: uczestnik zarejestrowany', [
                'participant_id' => $participant->id,
                'course_id' => $order->course_id,
                'course_title' => $order->course->title,
                'email' => $order->email,
                'order_ident' => $order->ident,
                'access_expires_at' => $accessExpiresAt ? $accessExpiresAt->format('Y-m-d H:i:s') : 'bezterminowy',
            ]);

            return $participant;

        } catch (\Exception $e) {
            Log::error('PaymentController: błąd podczas rejestracji uczestnika', [
                'order_id' => $order->id,
                'course_id' => $order->course_id,
                'email' => $order->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Oblicz datę wygaśnięcia dostępu na podstawie wariantu cenowego kursu.
     * 
     * @param OnlinePaymentOrder $order
     * @return \Carbon\Carbon|null
     */
    protected function calculateAccessExpirationDate(OnlinePaymentOrder $order): ?Carbon
    {
        try {
            // Załaduj kurs z wariantami cenowymi
            $course = $order->course;
            if (!$course) {
                return null;
            }

            // Spróbuj znaleźć wariant cenowy z form_data (jeśli został zapisany)
            $priceVariantId = null;
            if ($order->form_data && is_array($order->form_data)) {
                $priceVariantId = $order->form_data['price_variant_id'] ?? null;
            }

            // Znajdź wariant cenowy
            $priceVariant = null;
            if ($priceVariantId) {
                $priceVariant = CoursePriceVariant::where('id', $priceVariantId)
                    ->where('course_id', $course->id)
                    ->where('is_active', true)
                    ->first();
            }

            // Jeśli nie znaleziono wariantu z form_data, użyj najtańszego aktywnego wariantu
            if (!$priceVariant) {
                $priceVariant = CoursePriceVariant::where('course_id', $course->id)
                    ->where('is_active', true)
                    ->orderBy('price', 'asc')
                    ->first();
            }

            // Jeśli nie ma wariantu cenowego, zwróć null
            if (!$priceVariant) {
                Log::warning('PaymentController: brak wariantu cenowego dla kursu', [
                    'course_id' => $course->id,
                    'order_id' => $order->id,
                ]);
                return null;
            }

            $now = Carbon::now();
            $accessType = $priceVariant->access_type;

            // Oblicz datę wygaśnięcia na podstawie typu dostępu
            switch ($accessType) {
                case '1': // Bezterminowy, z natychmiastowym dostępem
                    return null; // Bezterminowy dostęp

                case '2': // Bezterminowy, od określonej daty
                    // Bezterminowy, więc zwracamy null
                    return null;

                case '3': // Przez określony czas, z natychmiastowym dostępem
                    if ($priceVariant->access_duration_value && $priceVariant->access_duration_unit) {
                        $expiresAt = $now->copy();
                        switch ($priceVariant->access_duration_unit) {
                            case 'hours':
                                $expiresAt->addHours($priceVariant->access_duration_value);
                                break;
                            case 'days':
                                $expiresAt->addDays($priceVariant->access_duration_value);
                                break;
                            case 'months':
                                $expiresAt->addMonths($priceVariant->access_duration_value);
                                break;
                            case 'years':
                                $expiresAt->addYears($priceVariant->access_duration_value);
                                break;
                        }
                        return $expiresAt;
                    }
                    return null;

                case '4': // Od określonej daty, z ustaloną datą końca
                    if ($priceVariant->access_end_datetime) {
                        return Carbon::parse($priceVariant->access_end_datetime);
                    }
                    return null;

                case '5': // Przez określony czas, od określonej daty
                    $startDate = $priceVariant->access_start_datetime 
                        ? Carbon::parse($priceVariant->access_start_datetime)
                        : $now;
                    
                    if ($priceVariant->access_duration_value && $priceVariant->access_duration_unit) {
                        $expiresAt = $startDate->copy();
                        switch ($priceVariant->access_duration_unit) {
                            case 'hours':
                                $expiresAt->addHours($priceVariant->access_duration_value);
                                break;
                            case 'days':
                                $expiresAt->addDays($priceVariant->access_duration_value);
                                break;
                            case 'months':
                                $expiresAt->addMonths($priceVariant->access_duration_value);
                                break;
                            case 'years':
                                $expiresAt->addYears($priceVariant->access_duration_value);
                                break;
                        }
                        return $expiresAt;
                    }
                    return null;

                default:
                    Log::warning('PaymentController: nieznany typ dostępu', [
                        'access_type' => $accessType,
                        'variant_id' => $priceVariant->id,
                        'course_id' => $course->id,
                    ]);
                    return null;
            }

        } catch (\Exception $e) {
            Log::error('PaymentController: błąd podczas obliczania daty wygaśnięcia', [
                'order_id' => $order->id,
                'course_id' => $order->course_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Wyślij e-mail do super administratora o nowej płatności online.
     * 
     * @param OnlinePaymentOrder $order
     * @return void
     */
    protected function sendPaymentNotificationEmail(OnlinePaymentOrder $order): void
    {
        try {
            // Załaduj kurs z relacją
            $order->load('course');
            
            // Sprawdź czy zamówienie jest opłacone
            if (!$order->isPaid()) {
                Log::warning('PaymentController: próba wysłania e-maila dla nieopłaconego zamówienia', [
                    'order_id' => $order->id,
                    'order_ident' => $order->ident,
                    'status' => $order->status,
                ]);
                return;
            }
            
            // Adres e-mail super administratora
            $adminEmail = 'waldemar.grabowski@hostnet.pl';
            
            Log::info('PaymentController: rozpoczynam wysyłkę e-maila o płatności', [
                'order_id' => $order->id,
                'order_ident' => $order->ident,
                'admin_email' => $adminEmail,
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'note' => config('mail.mailers.smtp.host') === 'mailpit' ? 'E-mail trafi do Mailpit (http://localhost:8025)' : 'E-mail będzie wysłany przez SMTP',
            ]);
            
            // Wyślij e-mail
            Mail::to($adminEmail)->send(new PaymentNotificationMail($order));
            
            Log::info('PaymentController: e-mail o płatności wysłany do administratora', [
                'order_id' => $order->id,
                'order_ident' => $order->ident,
                'admin_email' => $adminEmail,
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('PaymentController: błąd podczas wysyłki e-maila o płatności', [
                'order_id' => $order->id,
                'order_ident' => $order->ident,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
