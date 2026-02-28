<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendyService
{
    public const LIST_TIK_NAUCZYCIEL = 'BkxVCp9892qphCpbeP892xmhdQ';

    public const LIST_NAUCZYCIELE = 'K0w2hUq5uwwrkvtlgGyl4Q';

    public function __construct(
        protected string $baseUrl,
        protected string $apiKey
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Subscribe email to a single list.
     * Returns true on success, false on failure (logs error).
     */
    public function subscribe(string $email, string $listId, array $options = []): bool
    {
        $payload = array_merge([
            'api_key' => $this->apiKey,
            'email' => $email,
            'list' => $listId,
            'boolean' => 'true',
            'gdpr' => 'true',
        ], $options);

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($this->baseUrl . '/subscribe', $payload);

            $body = trim(preg_replace('/^\xEF\xBB\xBF/', '', $response->body())); // trim + ewentualny BOM
            $bodyLower = strtolower($body);

            // Sukces: Sendy zwraca 'true', 'Already subscribed.', czasem '1' lub inny krótki komunikat
            $knownSuccess = $bodyLower === 'true'
                || $bodyLower === '1'
                || str_contains($bodyLower, 'already subscribed');
            // Niektóre instalacje Sendy zwracają inny tekst przy sukcesie – przy 200 i bez znanego błędu uznaj za sukces
            $likelySuccess = $response->successful()
                && strlen($body) < 80
                && !str_contains($bodyLower, 'invalid')
                && !str_contains($bodyLower, 'error')
                && !str_contains($bodyLower, 'bounced')
                && !str_contains($bodyLower, 'suppressed')
                && !str_contains($bodyLower, 'missing');

            $isSuccess = $response->successful() && ($knownSuccess || $likelySuccess);

            if ($isSuccess) {
                return true;
            }

            Log::warning('Sendy subscribe failed', [
                'email' => $email,
                'list_id' => $listId,
                'status' => $response->status(),
                'body' => $body,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Sendy subscribe exception', [
                'email' => $email,
                'list_id' => $listId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Subscribe to TIK list and optionally to NAUCZYCIELE list.
     * Returns ['tik' => bool, 'nauczyciele' => bool|null].
     */
    public function subscribeCourseRegistration(string $email, bool $newsletterConsent): array
    {
        $result = ['tik' => false, 'nauczyciele' => null];

        $result['tik'] = $this->subscribe($email, self::LIST_TIK_NAUCZYCIEL);

        if ($newsletterConsent === true) {
            $result['nauczyciele'] = $this->subscribe($email, self::LIST_NAUCZYCIELE);
        }

        return $result;
    }
}
