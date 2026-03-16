<?php

namespace App\Http\Controllers;

use App\Services\SendyService;
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
     * GET /certificate-registration/{token}
     * Wyświetla formularz rejestracji lub widok "rejestracja nieaktywna".
     */
    public function show(string $token)
    {
        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if ($apiUrl === '' || $apiToken === '') {
            Log::warning('CertificateRegistration: PNEADM API not configured');
            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        try {
            $response = Http::timeout(15)
                ->withToken($apiToken)
                ->get($apiUrl . '/api/certificate-registration/status/' . $token);

            $data = $response->json();
            $courseTitle = $data['course_title'] ?? null;
            $instructorName = $data['instructor_name'] ?? null;
            $instructorPhoto = $data['instructor_photo'] ?? null;

            if (!$response->successful()) {
                return view('certificate-registration.inactive', [
                    'message' => $data['message'] ?? 'Link jest nieprawidłowy lub rejestracja nie jest aktywna.',
                    'courseTitle' => $courseTitle,
                    'instructorName' => $instructorName,
                    'instructorPhoto' => $instructorPhoto,
                ]);
            }

            if (empty($data['active'])) {
                return view('certificate-registration.inactive', [
                    'message' => $data['message'] ?? 'Rejestracja nie jest aktywna.',
                    'courseTitle' => $courseTitle,
                    'instructorName' => $instructorName,
                    'instructorPhoto' => $instructorPhoto,
                ]);
            }

            return view('certificate-registration.form', [
                'token' => $token,
                'courseTitle' => $courseTitle ?? 'Szkolenie',
                'instructorName' => $instructorName,
                'instructorPhoto' => $instructorPhoto,
            ]);
        } catch (\Throwable $e) {
            Log::error('CertificateRegistration: API error', [
                'token' => $token,
                'message' => $e->getMessage(),
            ]);
            return redirect()->route('home')->with('error', 'Wystąpił błąd. Spróbuj ponownie później.');
        }
    }

    /**
     * POST /certificate-registration/{token}
     * Wysyła dane do API pneadm i przekierowuje z komunikatem.
     */
    public function submit(Request $request, string $token)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'rodo_consent' => 'required|accepted',
            'newsletter_consent' => 'sometimes|boolean',
        ], [
            'first_name.required' => 'Podaj imię.',
            'last_name.required' => 'Podaj nazwisko.',
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
            'rodo_consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych.',
        ]);

        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if ($apiUrl === '' || $apiToken === '') {
            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        try {
            $newsletterConsent = filter_var($request->input('newsletter_consent'), FILTER_VALIDATE_BOOLEAN);
            $response = Http::timeout(15)
                ->withToken($apiToken)
                ->post($apiUrl . '/api/certificate-registration/register', [
                    'token' => $token,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'rodo_consent' => 1,
                    'newsletter_consent' => $newsletterConsent,
                ]);

            $data = $response->json();

            if ($response->successful() && !empty($data['success'])) {
                $email = $validated['email'];
                $sendyUrl = config('services.sendy.url');
                $sendyApiKey = config('services.sendy.api_key');

                // Zapis do newslettera tylko jeśli zaznaczono zgodę marketingową.
                // Dla rejestracji zaświadczenia dodajemy do listy NAUCZYCIELE wraz z imieniem i nazwiskiem.
                if ($newsletterConsent && !empty($sendyUrl) && !empty($sendyApiKey)) {
                    try {
                        $sendy = new SendyService($sendyUrl, $sendyApiKey);
                        $sendy->subscribe($email, SendyService::LIST_NAUCZYCIELE, [
                            // Standardowe pole Sendy – tylko imię
                            'name' => $validated['first_name'],
                            // Opcjonalnie: pola custom, jeśli masz je zdefiniowane w Sendy
                            'Name' => $validated['first_name'],
                            'Sername' => $validated['last_name'],
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('CertificateRegistration: Sendy subscribe failed', ['email' => $email, 'message' => $e->getMessage()]);
                    }
                }

                return redirect()->route('home')->with('certificate_registration_success', true);
            }

            if ($response->status() === 422 && !empty($data['already_registered'])) {
                return redirect()->route('home')->with('info', $data['message'] ?? 'Jesteś już zarejestrowany dla tego szkolenia.');
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
