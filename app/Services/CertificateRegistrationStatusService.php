<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertificateRegistrationStatusService
{
    private const CACHE_PREFIX = 'cert_reg:status:';

    /**
     * Status rejestracji zaświadczenia z API pneadm (z opcjonalnym cache).
     *
     * @return array<string, mixed>|null
     */
    public function getStatus(string $token): ?array
    {
        $ttl = (int) config('services.certificate_registration.status_cache_ttl', 60);
        $cacheKey = self::CACHE_PREFIX.$token;

        if ($ttl > 0 && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return is_array($cached) ? $cached : null;
        }

        $data = $this->fetchFromApi($token);

        if ($data !== null && $ttl > 0) {
            Cache::put($cacheKey, $data, $ttl);
        }

        return $data;
    }

    public function forget(string $token): void
    {
        Cache::forget(self::CACHE_PREFIX.$token);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchFromApi(string $token): ?array
    {
        $apiUrl = rtrim((string) config('services.pneadm.api_url', ''), '/');
        $apiToken = (string) config('services.pneadm.api_token', '');

        if ($apiUrl === '' || $apiToken === '') {
            return null;
        }

        $timeout = (int) config('services.pneadm.timeout', 30);

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiToken)
                ->get($apiUrl.'/api/certificate-registration/status/'.$token);

            $data = $response->json() ?? [];
            $data['_http_successful'] = $response->successful();

            return $data;
        } catch (\Throwable $e) {
            Log::error('CertificateRegistration: API error', [
                'token' => $token,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
