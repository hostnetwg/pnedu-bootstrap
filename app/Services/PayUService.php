<?php

namespace App\Services;

use App\Models\Course;
use App\Models\OnlinePaymentOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PayUService
{
    protected string $baseUrl;

    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.payu.base_url', 'https://secure.snd.payu.com');
    }

    /**
     * Pobierz token OAuth (client_credentials).
     */
    public function getAccessToken(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $clientId = config('services.payu.client_id');
        $clientSecret = config('services.payu.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            Log::error('PayU: brak client_id lub client_secret w konfiguracji. Sprawdź .env: PAYU_CLIENT_ID, PAYU_CLIENT_SECRET');
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->post("{$this->baseUrl}/pl/standard/user/oauth/authorize", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
        } catch (\Exception $e) {
            Log::error('PayU OAuth request exception', [
                'message' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ]);
            return null;
        }

        if (!$response->successful()) {
            Log::error('PayU OAuth error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'base_url' => $this->baseUrl,
            ]);
            return null;
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'] ?? null;

        if (!$this->accessToken) {
            Log::error('PayU OAuth: brak access_token w odpowiedzi', ['response' => $data]);
        }

        return $this->accessToken;
    }

    /**
     * Utwórz zamówienie w PayU i zwróć redirectUri.
     *
     * @return array{success: bool, redirect_uri?: string, order_id?: string, error?: string}
     */
    public function createOrder(OnlinePaymentOrder $order, string $notifyUrl, string $continueUrl): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Nie udało się uzyskać tokenu PayU'];
        }

        $posId = config('services.payu.pos_id');
        if (empty($posId)) {
            return ['success' => false, 'error' => 'Brak POS ID PayU w konfiguracji'];
        }

        $course = $order->course;
        if (!$course instanceof Course) {
            $course = Course::on('pneadm')->find($order->course_id);
        }

        $priceInfo = $course?->getCurrentPrice();
        $amountGross = $priceInfo['price'] ?? $order->total_amount;
        $amountGrosze = (int) round($amountGross * 100); // PayU wymaga groszy

        if ($amountGrosze <= 0) {
            Log::error('PayU: zamówienie z zerową kwotą', ['course_id' => $order->course_id, 'amount' => $amountGross]);
            return ['success' => false, 'error' => 'PayU nie akceptuje zamówień za 0 PLN. Szkolenie nie ma ustawionej ceny – skontaktuj się z organizatorem lub sprawdź warianty cenowe kursu.'];
        }

        $buyer = [
            'email' => $order->email,
            'phone' => $order->phone,
            'firstName' => $order->first_name,
            'lastName' => $order->last_name,
            'language' => 'pl',
        ];

        $products = [
            [
                'name' => $course?->title ?? 'Szkolenie online',
                'unitPrice' => (string) $amountGrosze,
                'quantity' => '1',
            ],
        ];

        $payload = [
            'notifyUrl' => $notifyUrl,
            'continueUrl' => $continueUrl,
            'customerIp' => $order->ip_address ?? request()->ip() ?? '127.0.0.1',
            'merchantPosId' => $posId,
            'description' => 'Szkolenie: ' . ($course?->title ?? 'Online'),
            'currencyCode' => 'PLN',
            'totalAmount' => (string) $amountGrosze,
            'extOrderId' => $order->ident,
            'buyer' => $buyer,
            'products' => $products,
        ];

        $response = Http::withToken($token)
            ->withOptions(['allow_redirects' => false])
            ->post("{$this->baseUrl}/api/v2_1/orders", $payload);

        // PayU może zwrócić 201 (Created) lub 302 (Found) – bez allow_redirects otrzymujemy JSON
        $ok = in_array($response->status(), [201, 302], true);

        if (!$ok) {
            Log::error('PayU create order error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'extOrderId' => $order->ident,
            ]);
            return [
                'success' => false,
                'error' => 'PayU odmówił utworzenia zamówienia: ' . ($response->json('status.statusDesc') ?? $response->body()),
            ];
        }

        $data = $response->json() ?? [];
        $redirectUri = $data['redirectUri']
            ?? $data['status']['redirectUri']
            ?? $response->header('Location');
        $payuOrderId = $data['orderId'] ?? $data['status']['orderId'] ?? null;

        if (empty($redirectUri)) {
            Log::error('PayU: brak redirectUri w odpowiedzi', [
                'status' => $response->status(),
                'body' => $response->body(),
                'extOrderId' => $order->ident,
            ]);
            return ['success' => false, 'error' => 'Brak redirectUri w odpowiedzi PayU'];
        }

        $order->update([
            'payu_order_id' => $payuOrderId,
            'status' => OnlinePaymentOrder::STATUS_CREATED,
        ]);

        return [
            'success' => true,
            'redirect_uri' => $redirectUri,
            'order_id' => $payuOrderId,
        ];
    }

    /**
     * Pobierz status zamówienia z PayU.
     */
    public function getOrderStatus(string $payuOrderId): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/api/v2_1/orders/{$payuOrderId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }
}
