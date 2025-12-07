<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CertificateApiClient
{
    private string $apiUrl;
    private string $apiToken;
    private int $timeout;

    public function __construct()
    {
        // Użyj ?? aby zapewnić, że zawsze mamy string (nie null)
        $this->apiUrl = (string) (config('services.pneadm.api_url') ?? '');
        $this->apiToken = (string) (config('services.pneadm.api_token') ?? '');
        $this->timeout = (int) (config('services.pneadm.timeout', 30) ?? 30);

        if (empty($this->apiUrl)) {
            throw new Exception('PNEADM_API_URL is not configured');
        }

        if (empty($this->apiToken)) {
            throw new Exception('PNEADM_API_TOKEN is not configured');
        }
    }

    /**
     * Generuje PDF certyfikatu przez API
     *
     * @param int $participantId ID uczestnika
     * @param array $options Opcje (connection, save_to_storage, cache)
     * @return string Zawartość PDF (binary)
     * @throws Exception
     */
    public function generatePdf(int $participantId, array $options = []): string
    {
        $url = rtrim($this->apiUrl, '/') . '/api/certificates/generate';

        $payload = [
            'participant_id' => $participantId,
        ];

        if (isset($options['connection'])) {
            $payload['connection'] = $options['connection'];
        }

        if (isset($options['save_to_storage'])) {
            $payload['save_to_storage'] = $options['save_to_storage'];
        }

        if (isset($options['cache'])) {
            $payload['cache'] = $options['cache'];
        }

        try {
            Log::info('CertificateApiClient: Requesting PDF generation', [
                'url' => $url,
                'participant_id' => $participantId,
                'options' => $options,
            ]);

            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->body();
            }

            $errorMessage = $response->json()['message'] ?? 'Unknown error';
            $statusCode = $response->status();

            Log::error('CertificateApiClient: API request failed', [
                'url' => $url,
                'participant_id' => $participantId,
                'status_code' => $statusCode,
                'error' => $errorMessage,
                'response' => $response->body(),
            ]);

            throw new Exception("Certificate API error ({$statusCode}): {$errorMessage}");

        } catch (Exception $e) {
            Log::error('CertificateApiClient: Exception during API call', [
                'url' => $url,
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Pobiera dane certyfikatu przez API
     *
     * @param int $participantId ID uczestnika
     * @param string|null $connection Nazwa połączenia bazy danych
     * @return array Dane certyfikatu
     * @throws Exception
     */
    public function getCertificateData(int $participantId, ?string $connection = null): array
    {
        $url = rtrim($this->apiUrl, '/') . '/api/certificates/data';

        $payload = [
            'participant_id' => $participantId,
        ];

        if ($connection) {
            $payload['connection'] = $connection;
        }

        try {
            Log::info('CertificateApiClient: Requesting certificate data', [
                'url' => $url,
                'participant_id' => $participantId,
                'connection' => $connection,
            ]);

            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            $errorMessage = $response->json()['message'] ?? 'Unknown error';
            $statusCode = $response->status();

            Log::error('CertificateApiClient: API request failed', [
                'url' => $url,
                'participant_id' => $participantId,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);

            throw new Exception("Certificate API error ({$statusCode}): {$errorMessage}");

        } catch (Exception $e) {
            Log::error('CertificateApiClient: Exception during API call', [
                'url' => $url,
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Health check endpoint
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        $url = rtrim($this->apiUrl, '/') . '/api/certificates/health';

        try {
            $response = Http::timeout(5)
                ->withToken($this->apiToken)
                ->get($url);

            return $response->successful();
        } catch (Exception $e) {
            Log::warning('CertificateApiClient: Health check failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

