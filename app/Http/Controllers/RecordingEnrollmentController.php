<?php

namespace App\Http\Controllers;

use App\Jobs\SubscribeCertificateRegistrationNewsletterJob;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class RecordingEnrollmentController extends Controller
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
     * GET /dostep-do-szkolenia/{token}
     */
    public function show(string $token)
    {
        $data = $this->fetchStatus($token);

        if ($data === null) {
            Log::warning('RecordingEnrollment: PNEADM API not configured');

            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        $viewData = $this->viewData($data);

        if (! $data['_http_successful'] || empty($data['active'])) {
            return view('recording-enrollment.inactive', array_merge($viewData, [
                'message' => $data['message'] ?? 'Link jest nieprawidłowy lub dopisywanie do nagrania nie jest aktywne.',
            ]));
        }

        return view('recording-enrollment.form', array_merge($viewData, [
            'token' => $token,
        ]));
    }

    /**
     * POST /dostep-do-szkolenia/{token}
     */
    public function submit(Request $request, string $token)
    {
        $status = $this->fetchStatus($token);

        if ($status === null) {
            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        if (! $status['_http_successful'] || empty($status['active'])) {
            return redirect()->route('home')->with('error', $status['message'] ?? 'Dopisywanie do nagrania nie jest aktywne.');
        }

        $email = User::normalizeEmail($request->input('email'));
        $request->merge(['email' => $email]);

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

        $existingUser = $this->findActiveUser($validated['email']);
        $createdUser = null;

        if ($existingUser === null) {
            $password = $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ], [
                'password.required' => 'Ustaw hasło do nagrania.',
                'password.confirmed' => 'Hasła nie są takie same.',
            ])['password'];

            try {
                $createdUser = $this->createVerifiedUser(
                    $validated['first_name'],
                    $validated['last_name'],
                    $validated['email'],
                    $password,
                );
            } catch (UniqueConstraintViolationException) {
                $createdUser = null;
                $existingUser = $this->findActiveUser($validated['email']);
            }
        }

        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if ($apiUrl === '' || $apiToken === '') {
            return redirect()->route('home')->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        try {
            $newsletterConsent = filter_var($request->input('newsletter_consent'), FILTER_VALIDATE_BOOLEAN);
            $timeout = (int) config('services.pneadm.timeout', 30);

            $response = Http::timeout($timeout)
                ->withToken($apiToken)
                ->post($apiUrl.'/api/recording-enrollment/register', [
                    'token' => $token,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'rodo_consent' => 1,
                ]);

            $data = $response->json() ?? [];

            if ($response->successful() && ! empty($data['success'])) {
                if ($newsletterConsent) {
                    SubscribeCertificateRegistrationNewsletterJob::dispatch(
                        $validated['email'],
                        $validated['first_name'],
                        $validated['last_name'],
                    );
                }

                $accountCreated = $createdUser !== null;
                if ($accountCreated) {
                    Auth::login($createdUser);
                }

                return response()->view('recording-enrollment.success', [
                    'updated' => ! empty($data['updated']),
                    'accountCreated' => $accountCreated,
                    'hasPneduAccount' => $accountCreated || $existingUser !== null || ! empty($data['has_pnedu_account']),
                    'nextUrl' => $accountCreated
                        ? route('dashboard.szkolenia')
                        : ($data['next_url'] ?? null),
                    'emailSent' => ! empty($data['email_sent']),
                    'emailFailed' => ! empty($data['email_failed']),
                    'message' => $data['message'] ?? null,
                    'email' => $validated['email'],
                    'courseTitle' => $status['course_title'] ?? null,
                ]);
            }

            if ($createdUser !== null) {
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->with('error', 'Konto z podanym hasłem już powstało, ale zapis na szkolenie nie doszedł. Wyślij formularz jeszcze raz — hasła nie zmienimy.');
            }

            if ($response->status() === 422 && ! empty($data['errors']) && is_array($data['errors'])) {
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors($data['errors']);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', $data['message'] ?? 'Wystąpił błąd. Spróbuj ponownie.');
        } catch (\Throwable $e) {
            Log::error('RecordingEnrollment: submit failed', [
                'message' => $e->getMessage(),
            ]);

            $message = $createdUser !== null
                ? 'Konto z podanym hasłem już powstało, ale zapis na szkolenie nie doszedł. Wyślij formularz jeszcze raz — hasła nie zmienimy.'
                : 'Usługa jest chwilowo niedostępna. Spróbuj ponownie za chwilę.';

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', $message);
        }
    }

    private function findActiveUser(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    private function createVerifiedUser(string $firstName, string $lastName, string $email, string $password): User
    {
        $user = new User;
        $user->forceFill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now('UTC'),
        ]);
        $user->save();

        return $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchStatus(string $token): ?array
    {
        $apiUrl = $this->getApiUrl();
        $apiToken = $this->getApiToken();

        if ($apiUrl === '' || $apiToken === '') {
            return null;
        }

        $timeout = (int) config('services.pneadm.timeout', 30);

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiToken)
                ->get($apiUrl.'/api/recording-enrollment/status/'.$token);

            $data = $response->json() ?? [];
            $data['_http_successful'] = $response->successful();

            return $data;
        } catch (\Throwable $e) {
            Log::error('RecordingEnrollment: API error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function viewData(array $data): array
    {
        return [
            'courseTitle' => $data['course_title'] ?? null,
            'courseStartDisplay' => $data['course_start_display'] ?? null,
            'enrollmentEndsDisplay' => $data['enrollment_ends_at_display'] ?? null,
            'instructorName' => $data['instructor_name'] ?? null,
            'instructorPhoto' => $data['instructor_photo'] ?? null,
        ];
    }
}
