<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnlineCourseCertificateProfileRequest;
use App\Models\OnlineCourseEnrollment;
use App\Services\CertificateApiClient;
use App\Services\OnlineCourseCertificateService;
use App\Services\UserCertificateProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OnlineCourseCertificateController extends Controller
{
    public function __construct(
        private OnlineCourseCertificateService $certificateService,
        private UserCertificateProfileService $userProfile,
    ) {}

    public function show(OnlineCourseEnrollment $enrollment): View|RedirectResponse
    {
        $this->assertEnrollmentAccess($enrollment);

        if (! $this->certificateService->certificatesEnabled($enrollment)) {
            abort(404);
        }

        $user = Auth::user();
        $course = $enrollment->onlineCourse;
        $context = $this->certificateService->contextForEnrollment($enrollment, $user);

        if ($context['needs_profile']) {
            return view('dashboard.online-courses.certificate-profile-form', [
                'enrollment' => $enrollment,
                'course' => $course,
                'user' => $user,
                'missingFields' => $this->userProfile->missingFieldsForOnlineCourse($user, $course),
                'collectBirthData' => (bool) $course->certificate_collect_birth_data,
                'birthDataRequired' => (bool) $course->certificate_birth_data_required,
            ]);
        }

        return view('dashboard.online-courses.certificate-preview', [
            'enrollment' => $enrollment,
            'course' => $course,
            'user' => $user,
            'context' => $context,
        ]);
    }

    public function updateProfile(OnlineCourseCertificateProfileRequest $request, OnlineCourseEnrollment $enrollment): RedirectResponse
    {
        $this->assertEnrollmentAccess($enrollment);

        if (! $this->certificateService->certificatesEnabled($enrollment)) {
            abort(404);
        }

        $user = $request->user();
        $user->fill($request->safe()->only([
            'first_name',
            'last_name',
            'birth_date',
            'birth_place',
        ]));
        $user->save();

        return redirect()
            ->route('dashboard.online-courses.certificate.show', $enrollment)
            ->with('success', 'Dane zostały zapisane w Twoim profilu. Możesz pobrać zaświadczenie.');
    }

    public function download(Request $request, OnlineCourseEnrollment $enrollment)
    {
        $this->assertEnrollmentAccess($enrollment);

        if (! $this->certificateService->certificatesEnabled($enrollment)) {
            abort(403, 'Pobieranie zaświadczeń nie jest udostępnione.');
        }

        $user = Auth::user();
        $course = $enrollment->onlineCourse;

        if (! $this->userProfile->hasCompleteProfileForOnlineCourse($user, $course)) {
            return redirect()
                ->route('dashboard.online-courses.certificate.show', $enrollment)
                ->with('error', 'Uzupełnij wymagane dane w profilu, aby pobrać zaświadczenie.');
        }

        try {
            $apiClient = app(CertificateApiClient::class);
            $holder = $this->userProfile->holderPayloadFromUser($user);

            $apiClient->ensureOnlineCourseCertificate($enrollment->id, $holder, 'pneadm');

            $data = $apiClient->getOnlineCourseCertificateData($enrollment->id, 'pneadm');
            $certificateNumber = $data['certificate_number'] ?? null;

            if (! $certificateNumber) {
                abort(500, 'Nie można wygenerować zaświadczenia.');
            }

            $pdfContent = $apiClient->generateOnlineCoursePdf($enrollment->id, [
                'connection' => 'pneadm',
                'save_to_storage' => true,
                'cache' => false,
            ]);

            $apiClient->markOnlineDownloaded($enrollment->id);

            $fileName = 'zaswiadczenie_'.str_replace('/', '-', $certificateNumber).'.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        } catch (\Throwable $e) {
            Log::error('Online course certificate download failed', [
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('dashboard.online-courses.certificate.show', $enrollment)
                ->with('error', 'Wystąpił błąd podczas generowania zaświadczenia. Spróbuj ponownie.');
        }
    }

    private function assertEnrollmentAccess(OnlineCourseEnrollment $enrollment): void
    {
        abort_unless($enrollment->emailMatchesUser(Auth::user()->email ?? ''), 403);
        abort_if($enrollment->hasExpiredAccess(), 403, 'Dostęp do tego kursu wygasł.');

        $course = $enrollment->onlineCourse;
        abort_unless($course && $course->is_active && $course->visible_in_dashboard, 404);
    }
}
