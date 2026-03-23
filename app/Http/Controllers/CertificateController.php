<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\ParticipantDownloadToken;
use App\Services\CertificateApiClient;
use App\Services\ParticipantCertificateListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CertificateController extends Controller
{
    /**
     * Generuje i pobiera PDF zaświadczenia dla uczestnika
     *
     * @param  int  $courseId  ID kursu
     * @return \Illuminate\Http\Response
     */
    public function generate($courseId)
    {
        try {
            // Sprawdź czy użytkownik jest zalogowany
            if (! auth()->check()) {
                return redirect()->route('login')->with('error', 'Musisz być zalogowany, aby pobrać zaświadczenie.');
            }

            $userEmail = trim(strtolower(auth()->user()->email ?? ''));

            if (empty($userEmail)) {
                Log::warning('Certificate generation failed: User email is empty', [
                    'user_id' => auth()->id(),
                    'course_id' => $courseId,
                ]);

                return redirect()->back()->with('error', 'Nie można zidentyfikować Twojego adresu email.');
            }

            // Znajdź uczestnika po emailu i course_id (case-insensitive)
            $participant = Participant::where('course_id', $courseId)
                ->whereRaw('LOWER(TRIM(email)) = ?', [$userEmail])
                ->first();

            if (! $participant) {
                // Loguj szczegóły dla debugowania
                $existingParticipants = Participant::where('course_id', $courseId)
                    ->select('id', 'email', 'first_name', 'last_name')
                    ->get();

                Log::warning('Certificate generation failed: Participant not found', [
                    'user_email' => $userEmail,
                    'course_id' => $courseId,
                    'user_id' => auth()->id(),
                    'existing_participants_count' => $existingParticipants->count(),
                    'existing_emails' => $existingParticipants->pluck('email')->toArray(),
                ]);

                return redirect()->back()->with('error', 'Nie jesteś uczestnikiem tego szkolenia. Skontaktuj się z administratorem, jeśli uważasz, że to błąd.');
            }

            // Generuj PDF używając API
            $apiClient = app(CertificateApiClient::class);

            // Pobierz dane certyfikatu z API (zawiera numer certyfikatu - generowany przez pneadm-bootstrap)
            $data = $apiClient->getCertificateData($participant->id, 'pneadm');
            $certificateNumber = $data['certificate_number'] ?? null;

            if (! $certificateNumber) {
                Log::error('Certificate number not found in API response', [
                    'participant_id' => $participant->id,
                    'course_id' => $courseId,
                ]);

                return redirect()->back()->with('error', 'Nie można pobrać numeru certyfikatu. Skontaktuj się z administratorem.');
            }

            // Generuj PDF przez API
            $pdfContent = $apiClient->generatePdf($participant->id, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false,
            ]);

            // Pobieranie pliku PDF (attachment zamiast inline - wymusza pobranie)
            $fileName = 'certificate-'.str_replace('/', '-', $certificateNumber).'.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        } catch (\Exception $e) {
            $errorDetails = [
                'course_id' => $courseId,
                'user_email' => auth()->user()->email ?? 'not logged in',
                'user_id' => auth()->id(),
                'participant_id' => $participant->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('Error generating certificate', $errorDetails);

            // W trybie debug pokaż więcej szczegółów
            $errorMessage = 'Wystąpił błąd podczas generowania zaświadczenia.';
            if (config('app.debug')) {
                $errorMessage .= ' Błąd: '.$e->getMessage().' (plik: '.basename($e->getFile()).', linia: '.$e->getLine().')';
            } else {
                $errorMessage .= ' Spróbuj ponownie później. Jeśli problem się powtarza, skontaktuj się z administratorem.';
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Generuje i pobiera PDF zaświadczenia dla uczestnika (alternatywna metoda z participant_id)
     *
     * @param  int  $participantId  ID uczestnika
     * @return \Illuminate\Http\Response
     */
    public function generateByParticipant($participantId)
    {
        try {
            // Sprawdź czy użytkownik jest zalogowany
            if (! auth()->check()) {
                return redirect()->route('login')->with('error', 'Musisz być zalogowany, aby pobrać zaświadczenie.');
            }

            $userEmail = trim(strtolower(auth()->user()->email ?? ''));

            // Znajdź uczestnika
            $participant = Participant::findOrFail($participantId);

            // Sprawdź czy użytkownik ma dostęp do tego uczestnika (po emailu)
            if (empty($userEmail) || strtolower(trim($participant->email)) !== $userEmail) {
                Log::warning('Certificate generation failed: User email mismatch', [
                    'user_email' => $userEmail,
                    'participant_email' => $participant->email,
                    'participant_id' => $participantId,
                    'user_id' => auth()->id(),
                ]);

                return redirect()->back()->with('error', 'Nie masz uprawnień do pobrania tego zaświadczenia.');
            }

            $courseId = $participant->course_id;

            // Generuj PDF używając API
            $apiClient = app(CertificateApiClient::class);

            // Pobierz dane certyfikatu z API (zawiera numer certyfikatu - generowany przez pneadm-bootstrap)
            $data = $apiClient->getCertificateData($participant->id, 'pneadm');
            $certificateNumber = $data['certificate_number'] ?? null;

            if (! $certificateNumber) {
                Log::error('Certificate number not found in API response', [
                    'participant_id' => $participantId,
                    'course_id' => $courseId,
                ]);

                return redirect()->back()->with('error', 'Nie można pobrać numeru certyfikatu. Skontaktuj się z administratorem.');
            }

            // Generuj PDF przez API
            $pdfContent = $apiClient->generatePdf($participant->id, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false,
            ]);

            // Pobieranie pliku PDF (attachment zamiast inline - wymusza pobranie)
            $fileName = 'certificate-'.str_replace('/', '-', $certificateNumber).'.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        } catch (\Exception $e) {
            $errorDetails = [
                'participant_id' => $participantId,
                'user_email' => auth()->user()->email ?? 'not logged in',
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('Error generating certificate by participant', $errorDetails);

            // W trybie debug pokaż więcej szczegółów
            $errorMessage = 'Wystąpił błąd podczas generowania zaświadczenia.';
            if (config('app.debug')) {
                $errorMessage .= ' Błąd: '.$e->getMessage().' (plik: '.basename($e->getFile()).', linia: '.$e->getLine().')';
            } else {
                $errorMessage .= ' Spróbuj ponownie później. Jeśli problem się powtarza, skontaktuj się z administratorem.';
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Lista szkoleń uczestnika po tokenie (bez logowania).
     * URL: /certificates/{token}
     */
    public function showListByToken(Request $request, string $token)
    {
        $tokenRecord = ParticipantDownloadToken::findByToken($token);
        if (! $tokenRecord) {
            abort(404, 'Link jest nieprawidłowy lub wygasł.');
        }

        $this->assertTokenMatchesAuthenticatedUserIfLoggedIn($tokenRecord);

        $redirect = $this->redirectVerifiedUserMatchingTokenToDashboardList($request, $tokenRecord);
        if ($redirect) {
            return $redirect;
        }

        $items = app(ParticipantCertificateListService::class)
            ->paginatedItemsForEmail($tokenRecord->email_normalized, 15);

        return view('certificates.list-by-token', [
            'token' => $token,
            'items' => $items,
            'isDashboardContext' => false,
            'highlightCourseId' => null,
        ]);
    }

    /**
     * Lista zaświadczeń w panelu konta (zalogowany, zweryfikowany e-mail).
     */
    public function dashboardCertificatesIndex(Request $request): View
    {
        $user = $request->user();
        $emailNormalized = ParticipantDownloadToken::normalizeEmail($user->email);
        $items = app(ParticipantCertificateListService::class)
            ->paginatedItemsForEmail($emailNormalized, 15);

        $highlightCourseId = $request->query('course');
        if ($highlightCourseId !== null && $highlightCourseId !== '' && ! ctype_digit((string) $highlightCourseId)) {
            $highlightCourseId = null;
        }
        $highlightCourseId = $highlightCourseId !== null ? (int) $highlightCourseId : null;

        $fromLink = $request->query('from') === 'link';

        return view('dashboard.zaswiadczenia', [
            'items' => $items,
            'highlightCourseId' => $highlightCourseId,
            'fromLink' => $fromLink,
        ]);
    }

    /**
     * Strona zaświadczenia po tokenie: formularz danych urodzenia (gdy brak) lub podgląd + przycisk pobierz.
     * URL: GET /certificate/{token}/{course}
     */
    public function showCertificateByToken(Request $request, string $token, $courseId)
    {
        $tokenRecord = ParticipantDownloadToken::findByToken($token);
        if (! $tokenRecord) {
            abort(404, 'Link jest nieprawidłowy lub wygasł.');
        }

        $this->assertTokenMatchesAuthenticatedUserIfLoggedIn($tokenRecord);

        $redirect = $this->redirectVerifiedUserMatchingTokenToDashboardCourse($request, $tokenRecord, (int) $courseId);
        if ($redirect) {
            return $redirect;
        }

        return $this->renderCertificateFlowForEmailAndCourse(
            $request,
            $tokenRecord->email_normalized,
            (int) $courseId,
            $token,
            false
        );
    }

    /**
     * Szczegóły zaświadczenia w panelu konta (bez tokenu w URL).
     */
    public function dashboardCertificateShow(Request $request, $courseId): View
    {
        $user = $request->user();
        $token = ParticipantDownloadToken::getOrCreateTokenForEmail($user->email);
        $emailNormalized = ParticipantDownloadToken::normalizeEmail($user->email);

        return $this->renderCertificateFlowForEmailAndCourse(
            $request,
            $emailNormalized,
            (int) $courseId,
            $token,
            true
        );
    }

    /**
     * Zapis danych urodzenia z formularza (POST).
     * Dla płatnych: pola wymagane. Dla bezpłatnych (optional=1): pola opcjonalne; jeśli oba puste – powrót do podglądu.
     */
    public function submitBirthDataByToken(Request $request, string $token, $courseId)
    {
        $tokenRecord = ParticipantDownloadToken::findByToken($token);
        if (! $tokenRecord) {
            return redirect()->back()->withErrors(['error' => 'Link jest nieprawidłowy lub wygasł.']);
        }
        $this->assertTokenMatchesAuthenticatedUserIfLoggedIn($tokenRecord);

        return $this->processBirthDataSubmit($request, $token, (int) $courseId, false);
    }

    public function dashboardSubmitBirth(Request $request, $courseId): RedirectResponse
    {
        $user = $request->user();
        $token = ParticipantDownloadToken::getOrCreateTokenForEmail($user->email);

        return $this->processBirthDataSubmit($request, $token, (int) $courseId, true);
    }

    /**
     * Generowanie i pobieranie PDF (ensure cert + generate). URL: GET /certificate/{token}/{course}/download
     */
    public function downloadByToken(Request $request, string $token, $courseId)
    {
        $tokenRecord = ParticipantDownloadToken::findByToken($token);
        if (! $tokenRecord) {
            abort(404, 'Link jest nieprawidłowy lub wygasł.');
        }

        $this->assertTokenMatchesAuthenticatedUserIfLoggedIn($tokenRecord);

        $redirect = $this->redirectVerifiedUserMatchingTokenToDashboardDownload($request, $tokenRecord, (int) $courseId);
        if ($redirect) {
            return $redirect;
        }

        return $this->performDownloadByTokenAfterChecks($tokenRecord->email_normalized, (int) $courseId, $token);
    }

    public function dashboardCertificateDownload(Request $request, $courseId)
    {
        $user = $request->user();
        $token = ParticipantDownloadToken::getOrCreateTokenForEmail($user->email);
        $emailNormalized = ParticipantDownloadToken::normalizeEmail($user->email);

        return $this->performDownloadByTokenAfterChecks($emailNormalized, (int) $courseId, $token);
    }

    /**
     * Strona „Trwa pobieranie…” – uruchamia pobranie PDF w tle i po chwili przekierowuje na stronę główną.
     */
    public function downloadWithRedirectPage(Request $request, string $token, $courseId)
    {
        $tokenRecord = ParticipantDownloadToken::findByToken($token);
        if (! $tokenRecord) {
            abort(404, 'Link jest nieprawidłowy lub wygasł.');
        }

        $this->assertTokenMatchesAuthenticatedUserIfLoggedIn($tokenRecord);

        $redirect = $this->redirectVerifiedUserMatchingTokenToDashboardDownloadRedirectPage($request, $tokenRecord, (int) $courseId);
        if ($redirect) {
            return $redirect;
        }

        $downloadUrl = route('certificates.download-by-token', ['token' => $token, 'course' => $courseId]);
        $homeUrl = route('home');

        return view('certificates.download-with-redirect', [
            'downloadUrl' => $downloadUrl,
            'homeUrl' => $homeUrl,
            'isDashboardContext' => false,
        ]);
    }

    public function dashboardCertificateDownloadWithRedirectPage(Request $request, $courseId): View
    {
        $user = $request->user();
        $courseId = (int) $courseId;
        $downloadUrl = route('dashboard.zaswiadczenia.course.download', ['course' => $courseId]);
        $homeUrl = route('dashboard');

        return view('certificates.download-with-redirect', [
            'downloadUrl' => $downloadUrl,
            'homeUrl' => $homeUrl,
            'isDashboardContext' => true,
        ]);
    }

    /**
     * Wspólna walidacja uczestnika + pobranie PDF (token używany do API mark-downloaded).
     */
    private function performDownloadByTokenAfterChecks(string $emailNormalized, int $courseId, string $token)
    {
        $participant = Participant::whereRaw('LOWER(TRIM(email)) = ?', [$emailNormalized])
            ->where('course_id', $courseId)
            ->with('course.instructor')
            ->first();

        if (! $participant) {
            abort(404, 'Nie znaleziono uczestnictwa w tym szkoleniu.');
        }

        $course = $participant->course;
        if (($course->certificate_download_status ?? '') !== 'download_enabled') {
            abort(403, 'Pobieranie zaświadczeń dla tego szkolenia nie jest udostępnione.');
        }

        $isPaid = (bool) ($course->is_paid ?? true);
        if ($isPaid && (! $participant->birth_date || trim((string) $participant->birth_place) === '')) {
            return $this->redirectBirthDataRequiredForDownload($emailNormalized, $courseId, $token);
        }

        return $this->streamCertificatePdfResponse($participant, $courseId, $token);
    }

    private function redirectBirthDataRequiredForDownload(string $emailNormalized, int $courseId, string $token): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->hasVerifiedEmail()
            && ParticipantDownloadToken::normalizeEmail($user->email) === $emailNormalized) {
            return redirect()->route('dashboard.zaswiadczenia.course', ['course' => $courseId])
                ->with('error', 'Do pobrania zaświadczenia wymagane są data i miejsce urodzenia.');
        }

        return redirect()->route('certificates.show-by-token', ['token' => $token, 'course' => $courseId])
            ->with('error', 'Do pobrania zaświadczenia wymagane są data i miejsce urodzenia.');
    }

    private function streamCertificatePdfResponse(Participant $participant, int $courseId, string $token)
    {
        try {
            $apiClient = app(CertificateApiClient::class);
            $apiClient->ensureCertificate($participant->id, 'pneadm');
            $data = $apiClient->getCertificateData($participant->id, 'pneadm');
            $certificateNumber = $data['certificate_number'] ?? null;
            if (! $certificateNumber) {
                Log::error('Certificate download by token: no certificate number', [
                    'participant_id' => $participant->id,
                    'course_id' => $courseId,
                ]);
                abort(500, 'Nie można wygenerować zaświadczenia.');
            }

            $pdfContent = $apiClient->generatePdf($participant->id, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false,
            ]);

            $apiClient->markDownloaded($token, $courseId);

            $fileName = 'zaswiadczenie_'.str_replace('/', '-', $certificateNumber).'.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        } catch (\Exception $e) {
            Log::error('Certificate download by token failed', [
                'participant_id' => $participant->id,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Wystąpił błąd podczas generowania zaświadczenia. Spróbuj ponownie później.');
        }
    }

    private function assertTokenMatchesAuthenticatedUserIfLoggedIn(ParticipantDownloadToken $tokenRecord): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $authNorm = ParticipantDownloadToken::normalizeEmail($user->email);
        if ($authNorm !== $tokenRecord->email_normalized) {
            abort(response()->view('certificates.errors.wrong-account-for-link', [], 403));
        }
    }

    private function redirectVerifiedUserMatchingTokenToDashboardList(Request $request, ParticipantDownloadToken $tokenRecord): ?RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasVerifiedEmail()) {
            return null;
        }

        if (ParticipantDownloadToken::normalizeEmail($user->email) !== $tokenRecord->email_normalized) {
            return null;
        }

        $query = array_filter([
            'from' => 'link',
            'course' => $request->query('course'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('dashboard.zaswiadczenia', $query);
    }

    private function redirectVerifiedUserMatchingTokenToDashboardCourse(Request $request, ParticipantDownloadToken $tokenRecord, int $courseId): ?RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasVerifiedEmail()) {
            return null;
        }

        if (ParticipantDownloadToken::normalizeEmail($user->email) !== $tokenRecord->email_normalized) {
            return null;
        }

        return redirect()->route('dashboard.zaswiadczenia.course', array_filter([
            'course' => $courseId,
            'from' => 'link',
        ]));
    }

    private function redirectVerifiedUserMatchingTokenToDashboardDownload(Request $request, ParticipantDownloadToken $tokenRecord, int $courseId): ?RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasVerifiedEmail()) {
            return null;
        }

        if (ParticipantDownloadToken::normalizeEmail($user->email) !== $tokenRecord->email_normalized) {
            return null;
        }

        return redirect()->route('dashboard.zaswiadczenia.course.download', ['course' => $courseId]);
    }

    private function redirectVerifiedUserMatchingTokenToDashboardDownloadRedirectPage(Request $request, ParticipantDownloadToken $tokenRecord, int $courseId): ?RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasVerifiedEmail()) {
            return null;
        }

        if (ParticipantDownloadToken::normalizeEmail($user->email) !== $tokenRecord->email_normalized) {
            return null;
        }

        return redirect()->route('dashboard.zaswiadczenia.course.download-redirect', ['course' => $courseId]);
    }

    private function renderCertificateFlowForEmailAndCourse(
        Request $request,
        string $emailNormalized,
        int $courseId,
        string $token,
        bool $isDashboardContext
    ): View {
        $participant = Participant::whereRaw('LOWER(TRIM(email)) = ?', [$emailNormalized])
            ->where('course_id', $courseId)
            ->with('course.instructor')
            ->first();

        if (! $participant) {
            abort(404, 'Nie znaleziono uczestnictwa w tym szkoleniu.');
        }

        $course = $participant->course;
        $status = $course->certificate_download_status ?? 'in_preparation';
        if ($status !== 'download_enabled') {
            abort(403, 'Pobieranie zaświadczeń dla tego szkolenia nie jest udostępnione.');
        }

        $isPaid = (bool) ($course->is_paid ?? true);
        $hasBirthData = $participant->birth_date && trim((string) $participant->birth_place) !== '';
        $forceEdit = $request->query('edit') === '1';

        if ($isPaid && (! $hasBirthData || $forceEdit)) {
            return view('certificates.birth-data-form', [
                'token' => $token,
                'course' => $course,
                'participant' => $participant,
                'optional' => false,
                'isDashboardContext' => $isDashboardContext,
            ]);
        }

        if (! $isPaid && $forceEdit) {
            return view('certificates.birth-data-form', [
                'token' => $token,
                'course' => $course,
                'participant' => $participant,
                'optional' => true,
                'isDashboardContext' => $isDashboardContext,
            ]);
        }

        return view('certificates.preview-and-download', [
            'token' => $token,
            'course' => $course,
            'participant' => $participant,
            'isDashboardContext' => $isDashboardContext,
        ]);
    }

    private function processBirthDataSubmit(Request $request, string $token, int $courseId, bool $isDashboardContext): RedirectResponse
    {
        $optional = $request->boolean('optional');

        if ($optional) {
            $birthDate = $request->input('birth_date');
            $birthPlace = trim((string) $request->input('birth_place', ''));
            if (empty($birthDate) && $birthPlace === '') {
                $route = $isDashboardContext
                    ? route('dashboard.zaswiadczenia.course', ['course' => $courseId])
                    : route('certificates.show-by-token', ['token' => $token, 'course' => $courseId]);

                return redirect()->to($route)
                    ->with('info', 'Możesz pobrać zaświadczenie bez tych danych.');
            }
            $request->validate([
                'birth_date' => 'required|date',
                'birth_place' => 'required|string|max:255',
            ]);
        } else {
            $request->validate([
                'birth_date' => 'required|date',
                'birth_place' => 'required|string|max:255',
            ]);
        }

        $tokenRecord = ParticipantDownloadToken::findByToken($token);
        if (! $tokenRecord) {
            return redirect()->back()->withErrors(['error' => 'Link jest nieprawidłowy lub wygasł.']);
        }

        $participant = Participant::whereRaw('LOWER(TRIM(email)) = ?', [$tokenRecord->email_normalized])
            ->where('course_id', $courseId)
            ->first();
        if (! $participant) {
            return redirect()->back()->withErrors(['error' => 'Nie znaleziono uczestnictwa.']);
        }

        try {
            $api = app(CertificateApiClient::class);
            $api->updateBirthData(
                $token,
                $courseId,
                $request->input('birth_date'),
                $request->input('birth_place')
            );
        } catch (\Exception $e) {
            Log::warning('Certificate birth data update failed', ['error' => $e->getMessage()]);

            return redirect()->back()->withErrors(['error' => 'Nie udało się zapisać danych. Spróbuj ponownie.'])->withInput();
        }

        $showRoute = $isDashboardContext
            ? route('dashboard.zaswiadczenia.course', ['course' => $courseId])
            : route('certificates.show-by-token', ['token' => $token, 'course' => $courseId]);

        return redirect()->to($showRoute)
            ->with('success', 'Dane zostały zapisane. Sprawdź je poniżej i pobierz zaświadczenie.');
    }
}
