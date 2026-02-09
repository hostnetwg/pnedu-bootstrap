<?php

namespace App\Services;

use App\Models\Course;
use App\Models\OnlinePaymentOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PayNowService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $signatureKey;

    public function __construct()
    {
        $this->baseUrl = config('services.paynow.base_url', 'https://api.sandbox.paynow.pl');
        $this->apiKey = trim(config('services.paynow.api_key', ''));
        $this->signatureKey = trim(config('services.paynow.signature_key', ''));

        if (empty($this->apiKey) || empty($this->signatureKey)) {
            Log::warning('PayNow: brak konfiguracji API Key lub Signature Key. Sprawdź .env: PAYNOW_API_KEY, PAYNOW_SIGNATURE_KEY');
        } else {
            // Logowanie diagnostyczne (tylko pierwsze i ostatnie znaki dla bezpieczeństwa)
            $apiKeyPreview = substr($this->apiKey, 0, 8) . '...' . substr($this->apiKey, -4);
            $sigKeyPreview = substr($this->signatureKey, 0, 8) . '...' . substr($this->signatureKey, -4);
            Log::debug('PayNow: konfiguracja załadowana', [
                'base_url' => $this->baseUrl,
                'api_key_preview' => $apiKeyPreview,
                'signature_key_preview' => $sigKeyPreview,
            ]);
        }
    }

    /**
     * Oblicz podpis HMAC-SHA256 dla żądania PayNow.
     * Implementacja zgodna z oficjalnym SDK PayNow.
     *
     * @param string $idempotencyKey
     * @param string|array $body Body jako JSON string lub tablica (będzie skonwertowana)
     * @param array $parameters
     * @return string
     */
    public function calculateSignature(string $idempotencyKey, $body = '', array $parameters = []): string
    {
        // Konstruuj paczkę danych zgodnie z dokumentacją PayNow i oficjalnym SDK
        // Headers muszą być w kolejności alfabetycznej: Api-Key, Idempotency-Key
        $headers = [
            'Api-Key' => $this->apiKey,
            'Idempotency-Key' => $idempotencyKey,
        ];
        
        // Body jako string JSON - jeśli przekazano tablicę, skonwertuj na JSON string
        // WAŻNE: musi być dokładnie taki sam JSON jak ten wysyłany w żądaniu HTTP
        if (is_array($body)) {
            $bodyString = !empty($body) ? json_encode($body, JSON_UNESCAPED_SLASHES) : '';
        } else {
            $bodyString = (string) $body;
        }
        
        // Parameters - WAŻNE: jeśli puste, musi być pusty obiekt stdClass(), nie pusta tablica []
        // Zgodnie z oficjalnym SDK PayNow: $parsedParameters ?: new \stdClass()
        $parsedParameters = [];
        foreach ($parameters as $key => $value) {
            $parsedParameters[$key] = is_array($value) ? $value : [$value];
        }
        $parametersObject = !empty($parsedParameters) ? $parsedParameters : new \stdClass();
        
        // Konstruuj paczkę danych - ważna kolejność: headers, parameters, body
        $signatureBody = [
            'headers' => $headers,
            'parameters' => $parametersObject,
            'body' => $bodyString,
        ];

        // Konwertuj na JSON - TYLKO JSON_UNESCAPED_SLASHES (bez JSON_UNESCAPED_UNICODE)
        // Zgodnie z oficjalnym SDK: json_encode($signatureBody, JSON_UNESCAPED_SLASHES)
        $jsonString = json_encode($signatureBody, JSON_UNESCAPED_SLASHES);

        // Logowanie diagnostyczne (tylko dla debugowania)
        Log::debug('PayNow signature calculation', [
            'signature_body' => $signatureBody,
            'json_string' => $jsonString,
            'json_length' => strlen($jsonString),
            'body_string' => $bodyString,
        ]);

        // Oblicz HMAC-SHA256 i zakoduj Base64
        $signature = base64_encode(hash_hmac('sha256', $jsonString, $this->signatureKey, true));

        return $signature;
    }

    /**
     * Weryfikuj podpis webhooka PayNow.
     *
     * @param string $signature Podpis z nagłówka Signature
     * @param array $payload Dane webhooka
     * @return bool
     */
    public function verifyWebhookSignature(string $signature, array $payload): bool
    {
        // Dla webhooków PayNow podpis jest obliczany z JSON payload
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expectedSignature = base64_encode(hash_hmac('sha256', $jsonPayload, $this->signatureKey, true));

        // Porównaj podpisy używając constant-time comparison
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Utwórz płatność w PayNow i zwróć redirectUrl.
     *
     * @return array{success: bool, redirect_url?: string, payment_id?: string, error?: string}
     */
    public function createOrder(OnlinePaymentOrder $order, string $notifyUrl, string $continueUrl): array
    {
        if (empty($this->apiKey) || empty($this->signatureKey)) {
            return ['success' => false, 'error' => 'Brak konfiguracji PayNow. Sprawdź .env: PAYNOW_API_KEY, PAYNOW_SIGNATURE_KEY'];
        }

        $course = $order->course;
        if (!$course instanceof Course) {
            $course = Course::on('pneadm')->find($order->course_id);
        }

        $priceInfo = $course?->getCurrentPrice();
        $amountGross = $priceInfo['price'] ?? $order->total_amount;
        $amountGrosze = (int) round($amountGross * 100); // PayNow wymaga groszy

        if ($amountGrosze < 100) {
            Log::error('PayNow: zamówienie z kwotą poniżej minimum', ['course_id' => $order->course_id, 'amount' => $amountGross]);
            return ['success' => false, 'error' => 'PayNow nie akceptuje zamówień poniżej 1.00 PLN.'];
        }

        if ($amountGrosze > 100000000) {
            Log::error('PayNow: zamówienie z kwotą powyżej maksimum', ['course_id' => $order->course_id, 'amount' => $amountGross]);
            return ['success' => false, 'error' => 'PayNow nie akceptuje zamówień powyżej 1,000,000.00 PLN.'];
        }

        // Generuj unikalny Idempotency-Key
        $idempotencyKey = $order->ident . '-' . time();

        // Przygotuj dane kupującego
        $phone = $order->phone ?? '';
        // Usuń wszystkie znaki niebędące cyframi
        $phoneDigits = preg_replace('/\D/', '', $phone);
        
        // Jeśli numer zaczyna się od 48 (Polska), usuń to
        if (strpos($phoneDigits, '48') === 0 && strlen($phoneDigits) > 9) {
            $phoneDigits = substr($phoneDigits, 2);
        }
        
        // Jeśli numer ma 9 cyfr, dodaj prefix +48
        if (strlen($phoneDigits) === 9) {
            $phonePrefix = '+48';
            $phoneNumber = (int) $phoneDigits;
        } else {
            // Domyślnie +48 i pierwsze 9 cyfr
            $phonePrefix = '+48';
            $phoneNumber = (int) substr($phoneDigits, 0, 9);
        }

        $buyer = [
            'email' => $order->email,
            'firstName' => $order->first_name ?? '',
            'lastName' => $order->last_name ?? '',
            'phone' => [
                'prefix' => $phonePrefix,
                'number' => $phoneNumber,
            ],
            'locale' => 'pl-PL',
        ];

        // Dodaj adres jeśli dostępny
        $addressData = $order->address_data ?? [];
        if (!empty($addressData)) {
            $billingAddress = [];
            $shippingAddress = [];

            if (isset($addressData['street'])) {
                $billingAddress['street'] = $addressData['street'];
            }
            if (isset($addressData['building_no'])) {
                $billingAddress['houseNumber'] = $addressData['building_no'];
            }
            if (isset($addressData['flat_no'])) {
                $billingAddress['apartmentNumber'] = $addressData['flat_no'];
            }
            if (isset($addressData['postcode'])) {
                $billingAddress['zipcode'] = $addressData['postcode'];
            }
            if (isset($addressData['city'])) {
                $billingAddress['city'] = $addressData['city'];
            }
            if (isset($addressData['country'])) {
                $billingAddress['country'] = $this->getCountryCode($addressData['country']);
            }

            if (!empty($billingAddress)) {
                $buyer['address'] = [
                    'billing' => $billingAddress,
                    'shipping' => $billingAddress, // Domyślnie taki sam jak billing
                ];
            }
        }

        // Przygotuj pozycje zamówienia
        $orderItems = [
            [
                'name' => $course?->title ?? 'Szkolenie online',
                'category' => 'Szkolenia i kursy',
                'quantity' => 1,
                'price' => $amountGrosze,
            ],
        ];

        // Przygotuj body żądania jako tablicę
        $bodyArray = [
            'amount' => $amountGrosze,
            'currency' => 'PLN',
            'externalId' => $order->ident,
            'description' => 'Szkolenie: ' . ($course?->title ?? 'Online'),
            'continueUrl' => $continueUrl,
            'buyer' => $buyer,
            'orderItems' => $orderItems,
        ];

        // WAŻNE: Konwertuj body na JSON string PRZED obliczeniem podpisu
        // Musi być dokładnie taki sam JSON jak ten wysyłany w żądaniu HTTP
        $bodyJsonString = json_encode($bodyArray, JSON_UNESCAPED_SLASHES);

        // Oblicz podpis używając JSON string (nie tablicy!)
        $signature = $this->calculateSignature($idempotencyKey, $bodyJsonString);

        // Logowanie diagnostyczne (tylko pierwsze i ostatnie znaki klucza dla bezpieczeństwa)
        $apiKeyPreview = !empty($this->apiKey) ? substr($this->apiKey, 0, 8) . '...' . substr($this->apiKey, -4) : 'BRAK';
        Log::info('PayNow: próba utworzenia płatności', [
            'base_url' => $this->baseUrl,
            'api_key_preview' => $apiKeyPreview,
            'api_key_length' => strlen($this->apiKey),
            'signature_key_length' => strlen($this->signatureKey),
            'external_id' => $order->ident,
            'amount' => $amountGrosze,
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            // Wysyłaj body jako tablicę - Laravel Http automatycznie skonwertuje na JSON
            // Używamy bodyArray, nie bodyJsonString, bo Laravel Http używa własnego formatowania
            $response = Http::timeout(30)
                ->withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Idempotency-Key' => $idempotencyKey,
                    'Signature' => $signature,
                    'Accept' => '*/*',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Laravel/' . app()->version(),
                ])
                ->withBody($bodyJsonString, 'application/json')
                ->post("{$this->baseUrl}/v3/payments");
        } catch (Exception $e) {
            Log::error('PayNow create order exception', [
                'message' => $e->getMessage(),
                'extOrderId' => $order->ident,
            ]);
            return ['success' => false, 'error' => 'Błąd połączenia z PayNow: ' . $e->getMessage()];
        }

        if ($response->status() !== 201) {
            $responseBody = $response->body();
            $responseJson = $response->json();
            
            Log::error('PayNow create order error', [
                'status' => $response->status(),
                'body' => $responseBody,
                'extOrderId' => $order->ident,
                'api_key_preview' => !empty($this->apiKey) ? substr($this->apiKey, 0, 8) . '...' . substr($this->apiKey, -4) : 'BRAK',
                'base_url' => $this->baseUrl,
            ]);

            $errorMessage = 'PayNow odmówił utworzenia zamówienia';
            $errors = $responseJson['errors'] ?? [];
            if (!empty($errors) && isset($errors[0]['message'])) {
                $errorMessage .= ': ' . $errors[0]['message'];
            } elseif (!empty($errors) && isset($errors[0]['errorType'])) {
                $errorMessage .= ': ' . $errors[0]['errorType'];
                if (isset($errors[0]['message'])) {
                    $errorMessage .= ' - ' . $errors[0]['message'];
                }
            } else {
                $errorMessage .= ' (status: ' . $response->status() . ')';
            }

            return ['success' => false, 'error' => $errorMessage];
        }

        $data = $response->json() ?? [];
        $redirectUrl = $data['redirectUrl'] ?? null;
        $paymentId = $data['paymentId'] ?? null;

        if (empty($redirectUrl)) {
            Log::error('PayNow: brak redirectUrl w odpowiedzi', [
                'status' => $response->status(),
                'body' => $response->body(),
                'extOrderId' => $order->ident,
            ]);
            return ['success' => false, 'error' => 'Brak redirectUrl w odpowiedzi PayNow'];
        }

        // Zapisz payment_id jako payu_order_id (używamy tego samego pola w bazie)
        $order->update([
            'payu_order_id' => $paymentId, // Używamy tego samego pola dla PayNow paymentId
            'status' => OnlinePaymentOrder::STATUS_CREATED,
        ]);

        return [
            'success' => true,
            'redirect_url' => $redirectUrl,
            'payment_id' => $paymentId,
        ];
    }

    /**
     * Pobierz status płatności z PayNow.
     */
    public function getPaymentStatus(string $paymentId): ?array
    {
        if (empty($this->apiKey) || empty($this->signatureKey)) {
            return null;
        }

        $idempotencyKey = 'status-' . $paymentId . '-' . time();
        $signature = $this->calculateSignature($idempotencyKey, [], []);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Idempotency-Key' => $idempotencyKey,
                    'Signature' => $signature,
                    'Accept' => '*/*',
                ])
                ->get("{$this->baseUrl}/v3/payments/{$paymentId}");
        } catch (Exception $e) {
            Log::error('PayNow get payment status exception', [
                'message' => $e->getMessage(),
                'paymentId' => $paymentId,
            ]);
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Konwertuj nazwę kraju na kod ISO 3166-1 alpha-2.
     */
    protected function getCountryCode(string $countryName): string
    {
        $countryMap = [
            'Polska' => 'PL',
            'Poland' => 'PL',
            'PL' => 'PL',
        ];

        $countryNameNormalized = ucfirst(strtolower(trim($countryName)));
        
        return $countryMap[$countryNameNormalized] ?? 'PL';
    }
}
