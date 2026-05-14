<?php

namespace App\Http\Controllers;

use App\Services\SendyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertificateRegistrationController extends Controller
{
    private function getApiUrl(): string
    {
        return rtrim((string) config('services.pneadm.api_url', ''), '/');
    }

    private function getApiToken(): string
    {
        return (string) config('services.pneadm.api_token', '');
    }

    /**
     * Konwersja daty urodzenia z formularza (dd.mm.rrrr lub yyyy-mm-dd) do formatu Y-m-d.
     *
     * @return array{ok: bool, iso: ?string, message: ?string}
     */
    private function parseBirthDateInput(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return ['ok' => true, 'iso' => null, 'message' => null];
        }

        $raw = trim($raw);

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                $dt = Carbon::parse($raw)->startOfDay();
            } elseif (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $raw)) {
                $dt = Carbon::createFromFormat('!d.m.Y', $raw)->startOfDay();
            } else {
                return ['ok' => false, 'iso' => null, 'message' => 'Podaj datę urodzenia w formacie dd.mm.rrrr, np. 03.05.1984.'];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'iso' => null, 'message' => 'Podana data urodzenia jest nieprawidłowa (sprawdź dzień i miesiąc).'];
        }

        if ($dt->isFuture()) {
            return ['ok' => false, 'iso' => null, 'message' => 'Data urodzenia nie może być z przyszłości.'];
        }

        return ['ok' => true, 'iso' => $dt->format('Y-m-d'), 'message' => null];
    }

    /**
     * GET status z API pneadm lub null przy błędzie konfiguracji / wyjątku.
     *
     * @return array<string, mixed>|null
     */
    private function fetchRegistrationStatus(string $token): ?array
    {
        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if ($apiUrl === '' || $apiToken === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)
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

    /**
     * GET /certificate-registration/{token}
     * Wyświetla formularz rejestracji lub widok "rejestracja nieaktywna".
     */
    public function show(string $token)
    {
        $data = $this->fetchRegistrationStatus($token);

        if ($data === null) {
            Log::warning('CertificateRegistration: PNEADM API not configured');

            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        $courseTitle = $data['course_title'] ?? null;
        $courseStartDisplay = $data['course_start_display'] ?? null;
        $instructorName = $data['instructor_name'] ?? null;
        $instructorPhoto = $data['instructor_photo'] ?? null;

        if (! $data['_http_successful']) {
            return view('certificate-registration.inactive', [
                'message' => $data['message'] ?? 'Link jest nieprawidłowy lub rejestracja nie jest aktywna.',
                'courseTitle' => $courseTitle,
                'courseStartDisplay' => $courseStartDisplay,
                'instructorName' => $instructorName,
                'instructorPhoto' => $instructorPhoto,
            ]);
        }

        if (empty($data['active'])) {
            return view('certificate-registration.inactive', [
                'message' => $data['message'] ?? 'Rejestracja nie jest aktywna.',
                'courseTitle' => $courseTitle,
                'courseStartDisplay' => $courseStartDisplay,
                'instructorName' => $instructorName,
                'instructorPhoto' => $instructorPhoto,
            ]);
        }

        return view('certificate-registration.form', [
            'token' => $token,
            'courseTitle' => $courseTitle ?? 'Szkolenie',
            'courseStartDisplay' => $courseStartDisplay,
            'instructorName' => $instructorName,
            'instructorPhoto' => $instructorPhoto,
            'collectBirthData' => ! empty($data['certificate_registration_collect_birth_data']),
            'birthDataRequired' => ! empty($data['certificate_registration_birth_data_required']),
        ]);
    }

    /**
     * POST /certificate-registration/{token}
     * Wysyła dane do API pneadm i przekierowuje z komunikatem.
     */
    public function submit(Request $request, string $token)
    {
        $status = $this->fetchRegistrationStatus($token);

        if ($status === null) {
            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        if (! $status['_http_successful'] || empty($status['active'])) {
            return redirect()->route('home')->with('error', $status['message'] ?? 'Rejestracja nie jest aktywna.');
        }

        $collectBirthData = ! empty($status['certificate_registration_collect_birth_data']);
        $birthDataRequired = ! empty($status['certificate_registration_birth_data_required']);

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'rodo_consent' => 'required|accepted',
            'newsletter_consent' => 'sometimes|boolean',
        ];
        $messages = [
            'first_name.required' => 'Podaj imię.',
            'last_name.required' => 'Podaj nazwisko.',
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
            'rodo_consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'birth_date.required' => 'Podaj datę urodzenia.',
            'birth_date.date' => 'Podaj prawidłową datę urodzenia (format dd.mm.rrrr, np. 03.05.1984).',
            'birth_place.required' => 'Podaj miejsce urodzenia.',
        ];

        if ($collectBirthData) {
            if ($birthDataRequired) {
                $rules['birth_date'] = 'required|date';
                $rules['birth_place'] = 'required|string|max:255';
            } else {
                $rules['birth_date'] = 'nullable|date';
                $rules['birth_place'] = 'nullable|string|max:255';
            }
        }

        if ($collectBirthData) {
            $parsed = $this->parseBirthDateInput($request->input('birth_date'));
            if (! $parsed['ok']) {
                return redirect()->back()->withInput()->withErrors(['birth_date' => $parsed['message']]);
            }
            $request->merge(['birth_date' => $parsed['iso']]);

            $place = trim((string) $request->input('birth_place', ''));
            $request->merge(['birth_place' => $place === '' ? null : $place]);
        }

        $validated = $request->validate($rules, $messages);

        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if ($apiUrl === '' || $apiToken === '') {
            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        try {
            $newsletterConsent = filter_var($request->input('newsletter_consent'), FILTER_VALIDATE_BOOLEAN);
            $payload = [
                'token' => $token,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'rodo_consent' => 1,
                'newsletter_consent' => $newsletterConsent,
            ];
            if ($collectBirthData) {
                $payload['birth_date'] = $request->input('birth_date');
                $payload['birth_place'] = $request->input('birth_place');
            }

            $response = Http::timeout(15)
                ->withToken($apiToken)
                ->post($apiUrl.'/api/certificate-registration/register', $payload);

            $data = $response->json() ?? [];

            if ($response->successful() && ! empty($data['success'])) {
                $email = $validated['email'];

                if ($newsletterConsent) {
                    $sendy = SendyService::fromConfig();
                    if ($sendy !== null) {
                        try {
                            $sendy->subscribeCertificateRegistrationNewsletter(
                                $email,
                                $validated['first_name'],
                                $validated['last_name']
                            );
                        } catch (\Throwable $e) {
                            Log::warning('CertificateRegistration: Sendy subscribe failed', ['email' => $email, 'message' => $e->getMessage()]);
                        }
                    }
                }

                return redirect()->route('home')->with([
                    'certificate_registration_success' => true,
                    'certificate_registration_updated' => ! empty($data['updated']),
                ]);
            }

            if ($response->status() === 422 && ! empty($data['errors']) && is_array($data['errors'])) {
                return redirect()->back()->withInput()->withErrors($data['errors']);
            }

            $message = $data['message'] ?? 'Wystąpił błąd. Spróbuj ponownie.';

            return redirect()->back()->withInput()->with('error', $message);
        } catch (\Throwable $e) {
            Log::error('CertificateRegistration: register API error', [
                'token' => $token,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('home')->with('error', 'Wystąpił błąd. Spróbuj ponownie później.');
        }
    }
}
