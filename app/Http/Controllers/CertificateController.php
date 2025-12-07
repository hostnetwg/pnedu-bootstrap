<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certificate;
use App\Models\Participant;
use App\Models\Course;
use App\Services\CertificateApiClient;
use Pne\CertificateGenerator\Services\CertificateNumberGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    /**
     * Generuje i pobiera PDF zaświadczenia dla uczestnika
     *
     * @param int $courseId ID kursu
     * @return \Illuminate\Http\Response
     */
    public function generate($courseId)
    {
        try {
            // Sprawdź czy użytkownik jest zalogowany
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Musisz być zalogowany, aby pobrać zaświadczenie.');
            }

            $userEmail = trim(strtolower(auth()->user()->email ?? ''));

            if (empty($userEmail)) {
                Log::warning('Certificate generation failed: User email is empty', [
                    'user_id' => auth()->id(),
                    'course_id' => $courseId
                ]);
                return redirect()->back()->with('error', 'Nie można zidentyfikować Twojego adresu email.');
            }

            // Znajdź uczestnika po emailu i course_id (case-insensitive)
            $participant = Participant::where('course_id', $courseId)
                ->whereRaw('LOWER(TRIM(email)) = ?', [$userEmail])
                ->first();

            if (!$participant) {
                // Loguj szczegóły dla debugowania
                $existingParticipants = Participant::where('course_id', $courseId)
                    ->select('id', 'email', 'first_name', 'last_name')
                    ->get();
                
                Log::warning('Certificate generation failed: Participant not found', [
                    'user_email' => $userEmail,
                    'course_id' => $courseId,
                    'user_id' => auth()->id(),
                    'existing_participants_count' => $existingParticipants->count(),
                    'existing_emails' => $existingParticipants->pluck('email')->toArray()
                ]);

                return redirect()->back()->with('error', 'Nie jesteś uczestnikiem tego szkolenia. Skontaktuj się z administratorem, jeśli uważasz, że to błąd.');
            }

            // Pobierz kurs (pakiet pobiera dane szablonu bezpośrednio z bazy)
            $course = Course::findOrFail($courseId);

            // Sprawdź czy certyfikat już istnieje
            $certificate = Certificate::where('participant_id', $participant->id)
                ->where('course_id', $courseId)
                ->first();

            // Jeśli certyfikat nie istnieje, utwórz go z numerem
            if (!$certificate) {
                $numberGenerator = app(CertificateNumberGenerator::class);
                $certificateNumber = $numberGenerator->formatCertificateNumber(
                    $course,
                    $numberGenerator->determineNextSequence($course, $numberGenerator->resolveCourseYear($course)),
                    $numberGenerator->resolveCourseYear($course)
                );

                $certificate = Certificate::create([
                    'participant_id' => $participant->id,
                    'course_id' => $courseId,
                    'certificate_number' => $certificateNumber,
                    'generated_at' => now(),
                ]);
            }

            // Generuj PDF używając API
            $apiClient = app(CertificateApiClient::class);
            
            // Pobierz dane certyfikatu z API (zawiera numer certyfikatu)
            $data = $apiClient->getCertificateData($participant->id, 'pneadm');
            $certificateNumber = $data['certificate_number'] ?? $certificate->certificate_number;

            // Generuj PDF przez API
            $pdfContent = $apiClient->generatePdf($participant->id, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false
            ]);

            // Pobieranie pliku PDF (attachment zamiast inline - wymusza pobranie)
            $fileName = 'certificate-' . str_replace('/', '-', $certificateNumber) . '.pdf';
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            $errorDetails = [
                'course_id' => $courseId,
                'user_email' => auth()->user()->email ?? 'not logged in',
                'user_id' => auth()->id(),
                'participant_id' => $participant->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            
            Log::error('Error generating certificate', $errorDetails);

            // W trybie debug pokaż więcej szczegółów
            $errorMessage = 'Wystąpił błąd podczas generowania zaświadczenia.';
            if (config('app.debug')) {
                $errorMessage .= ' Błąd: ' . $e->getMessage() . ' (plik: ' . basename($e->getFile()) . ', linia: ' . $e->getLine() . ')';
            } else {
                $errorMessage .= ' Spróbuj ponownie później. Jeśli problem się powtarza, skontaktuj się z administratorem.';
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Generuje i pobiera PDF zaświadczenia dla uczestnika (alternatywna metoda z participant_id)
     *
     * @param int $participantId ID uczestnika
     * @return \Illuminate\Http\Response
     */
    public function generateByParticipant($participantId)
    {
        try {
            // Sprawdź czy użytkownik jest zalogowany
            if (!auth()->check()) {
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
                    'user_id' => auth()->id()
                ]);
                return redirect()->back()->with('error', 'Nie masz uprawnień do pobrania tego zaświadczenia.');
            }

            $courseId = $participant->course_id;

            // Pobierz kurs (pakiet pobiera dane szablonu bezpośrednio z bazy)
            $course = Course::findOrFail($courseId);

            // Sprawdź czy certyfikat już istnieje
            $certificate = Certificate::where('participant_id', $participant->id)
                ->where('course_id', $courseId)
                ->first();

            // Jeśli certyfikat nie istnieje, utwórz go z numerem
            if (!$certificate) {
                $numberGenerator = app(CertificateNumberGenerator::class);
                $certificateNumber = $numberGenerator->formatCertificateNumber(
                    $course,
                    $numberGenerator->determineNextSequence($course, $numberGenerator->resolveCourseYear($course)),
                    $numberGenerator->resolveCourseYear($course)
                );

                $certificate = Certificate::create([
                    'participant_id' => $participant->id,
                    'course_id' => $courseId,
                    'certificate_number' => $certificateNumber,
                    'generated_at' => now(),
                ]);
            }

            // Generuj PDF używając API
            $apiClient = app(CertificateApiClient::class);
            
            // Pobierz dane certyfikatu z API (zawiera numer certyfikatu)
            $data = $apiClient->getCertificateData($participant->id, 'pneadm');
            $certificateNumber = $data['certificate_number'] ?? $certificate->certificate_number;

            // Generuj PDF przez API
            $pdfContent = $apiClient->generatePdf($participant->id, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false
            ]);

            // Pobieranie pliku PDF (attachment zamiast inline - wymusza pobranie)
            $fileName = 'certificate-' . str_replace('/', '-', $certificateNumber) . '.pdf';
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            $errorDetails = [
                'participant_id' => $participantId,
                'user_email' => auth()->user()->email ?? 'not logged in',
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            
            Log::error('Error generating certificate by participant', $errorDetails);

            // W trybie debug pokaż więcej szczegółów
            $errorMessage = 'Wystąpił błąd podczas generowania zaświadczenia.';
            if (config('app.debug')) {
                $errorMessage .= ' Błąd: ' . $e->getMessage() . ' (plik: ' . basename($e->getFile()) . ', linia: ' . $e->getLine() . ')';
            } else {
                $errorMessage .= ' Spróbuj ponownie później. Jeśli problem się powtarza, skontaktuj się z administratorem.';
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }
}

