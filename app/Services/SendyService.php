<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendyService
{
    public const LIST_TIK_NAUCZYCIEL = 'BkxVCp9892qphCpbeP892xmhdQ';

    public const LIST_NAUCZYCIELE = 'K0w2hUq5uwwrkvtlgGyl4Q';

    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
        protected int $timeout = 15,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public static function fromConfig(): ?self
    {
        $url = config('services.sendy.url');
        $key = config('services.sendy.api_key');
        if (empty($url) || empty($key)) {
            return null;
        }

        return new self(
            rtrim((string) $url, '/'),
            (string) $key,
            (int) config('services.sendy.timeout', 15),
        );
    }

    /**
     * Sprawdza przez API Sendy, czy {@see $listId} wskazuje istniejącą listę.
     */
    public function validateListId(string $listId): bool
    {
        $listId = trim($listId);
        if ($listId === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->post($this->baseUrl.'/api/subscribers/active-subscriber-count.php', [
                    'api_key' => $this->apiKey,
                    'list_id' => $listId,
                ]);

            $body = trim(preg_replace('/^\xEF\xBB\xBF/', '', $response->body()));
            $lower = strtolower($body);

            if (str_contains($lower, 'invalid') || str_contains($lower, 'not exist') || str_contains($lower, 'error')) {
                return false;
            }

            $stripped = str_replace(["\r", "\n"], '', $body);

            return $response->successful() && ctype_digit($stripped);
        } catch (\Throwable $e) {
            Log::warning('Sendy validateListId exception', [
                'list_id' => $listId,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Po złożeniu zamówienia na kurs: zapis na listę kursu z polem daty (RRRR-MM-DD) dla segmentów w Sendy.
     * Zamawiający i uczestnik — osobne zapisy tylko gdy różne adresy e-mail.
     */
    public function subscribeOrderFormContacts(Course $course, array $validated): void
    {
        $listId = trim((string) ($course->sendy_suppression_list_id ?? ''));
        if ($listId === '') {
            return;
        }

        if (! $this->validateListId($listId)) {
            Log::warning('Sendy: lista nie istnieje lub nie można jej zweryfikować', [
                'course_id' => $course->id,
                'list_id' => $listId,
            ]);

            return;
        }

        if (empty($course->start_date)) {
            Log::info('Sendy: pominięto zapis — brak start_date kursu', ['course_id' => $course->id]);

            return;
        }

        try {
            $trainingDate = \Carbon\Carbon::parse($course->start_date)->timezone(config('app.timezone'))->format('Y-m-d');
        } catch (\Throwable) {
            Log::warning('Sendy: niepoprawna data rozpoczęcia kursu', [
                'course_id' => $course->id,
                'start_date' => $course->start_date,
            ]);

            return;
        }

        $fieldName = (string) config('services.sendy.training_date_field', 'data');
        $buyerEmail = strtolower(trim($validated['contact_email']));
        $participantEmail = strtolower(trim($validated['participant_email']));

        if ($buyerEmail === '' || ! filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        if ($participantEmail === '' || ! filter_var($participantEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $buyerName = trim((string) ($validated['contact_name'] ?? ''));
        $participantName = trim(
            ($validated['participant_first_name'] ?? '').' '.($validated['participant_last_name'] ?? '')
        );

        $baseOptions = array_merge([
            'gdpr' => 'true',
            'silent' => 'true',
            'boolean' => 'true',
            $fieldName => $trainingDate,
        ], $buyerName !== '' ? ['name' => $buyerName] : []);

        $this->subscribe($buyerEmail, $listId, $baseOptions);

        if ($participantEmail !== $buyerEmail) {
            $pOpts = array_merge([
                'gdpr' => 'true',
                'silent' => 'true',
                'boolean' => 'true',
                $fieldName => $trainingDate,
            ], $participantName !== '' ? ['name' => $participantName] : []);

            $this->subscribe($participantEmail, $listId, $pOpts);
        }
    }

    /**
     * Subscribe email to a single list.
     * Returns true on success, false on failure (logs error).
     *
     * @param  array<string, string>  $options
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
                ->timeout($this->timeout)
                ->post($this->baseUrl.'/subscribe', $payload);

            $body = trim(preg_replace('/^\xEF\xBB\xBF/', '', $response->body())); // trim + ewentualny BOM
            $bodyLower = strtolower($body);

            // Sukces: Sendy zwraca 'true', 'Already subscribed.', czasem '1' lub inny krótki komunikat
            $knownSuccess = $bodyLower === 'true'
                || $bodyLower === '1'
                || str_contains($bodyLower, 'already subscribed');
            // Niektóre instalacje Sendy zwracają inny tekst przy sukcesie – przy 200 i bez znanego błędu uznaj za sukces
            $likelySuccess = $response->successful()
                && strlen($body) < 80
                && ! str_contains($bodyLower, 'invalid')
                && ! str_contains($bodyLower, 'error')
                && ! str_contains($bodyLower, 'bounced')
                && ! str_contains($bodyLower, 'suppressed')
                && ! str_contains($bodyLower, 'missing');

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
