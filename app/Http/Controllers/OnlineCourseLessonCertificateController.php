<?php

namespace App\Http\Controllers;

use App\Models\OnlineCourseEnrollment;
use App\Models\OnlineCourseLesson;
use App\Services\CertificateRegistrationStatusService;
use App\Services\OnlineCourseLessonCertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnlineCourseLessonCertificateController extends Controller
{
    public function __construct(
        private OnlineCourseLessonCertificateService $certificateService,
        private CertificateRegistrationStatusService $statusService,
    ) {}

    /**
     * Rejestracja uczestnika (jeśli potrzeba) i przekierowanie do klasycznego flow pobierania zaświadczenia.
     */
    public function submit(Request $request, OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): RedirectResponse
    {
        $this->assertLessonCertificateAccess($enrollment, $lesson);

        $context = $this->certificateService->lessonCertificateContext($enrollment, $lesson);
        $linkedCourseId = (int) ($context['linked_course_id'] ?? 0);
        abort_if($linkedCourseId <= 0, 404);

        $dashboardUrl = route('dashboard.zaswiadczenia.course', ['course' => $linkedCourseId]);

        if (! empty($context['already_registered'])) {
            return redirect()->to($dashboardUrl);
        }

        if (empty($context['needs_registration']) || empty($context['can_download'])) {
            return redirect()
                ->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
                ->with('error', $context['message'] ?? 'Pobieranie zaświadczenia nie jest dostępne.');
        }

        $request->validate([
            'confirmation_consent' => ['required', 'accepted'],
        ], [
            'confirmation_consent.accepted' => 'Musisz potwierdzić obejrzenie szkolenia i wyrazić zgodę RODO.',
        ]);

        $apiUrl = rtrim((string) config('services.pneadm.api_url', ''), '/');
        $apiToken = (string) config('services.pneadm.api_token', '');
        if ($apiUrl === '' || $apiToken === '') {
            return redirect()
                ->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
                ->with('open_certificate_modal', true)
                ->with('error', 'Usługa jest chwilowo niedostępna.');
        }

        try {
            $payload = [
                'course_id' => $linkedCourseId,
                'first_name' => trim((string) $enrollment->first_name),
                'last_name' => trim((string) $enrollment->last_name),
                'email' => $enrollment->email,
                'rodo_consent' => 1,
            ];

            $timeout = (int) config('services.pneadm.timeout', 30);
            $response = Http::timeout($timeout)
                ->withToken($apiToken)
                ->post($apiUrl.'/api/certificate-registration/register-extended', $payload);

            $data = $response->json() ?? [];

            if ($response->successful() && ! empty($data['success'])) {
                $this->statusService->forgetCourse($linkedCourseId);

                return redirect()->to($dashboardUrl);
            }

            if ($response->status() === 422 && ! empty($data['errors']) && is_array($data['errors'])) {
                return redirect()
                    ->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
                    ->with('open_certificate_modal', true)
                    ->withErrors($data['errors']);
            }

            $message = $data['message'] ?? 'Wystąpił błąd. Spróbuj ponownie.';

            return redirect()
                ->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
                ->with('open_certificate_modal', true)
                ->with('error', $message);
        } catch (\Throwable $e) {
            Log::error('OnlineCourseLessonCertificate: register-extended API error', [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
                'course_id' => $linkedCourseId,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
                ->with('open_certificate_modal', true)
                ->with('error', 'Wystąpił błąd. Spróbuj ponownie później.');
        }
    }

    private function assertLessonCertificateAccess(OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): void
    {
        abort_unless($enrollment->emailMatchesUser(Auth::user()->email ?? ''), 403);
        abort_if($enrollment->hasExpiredAccess(), 403, 'Dostęp do tego kursu wygasł.');

        $course = $enrollment->onlineCourse;
        abort_unless($course->is_active && $course->visible_in_dashboard, 404);
        abort_unless((int) $lesson->module->online_course_id === (int) $course->id, 404);
        abort_unless($lesson->is_published, 404);
        abort_unless($this->certificateService->lessonHasLinkedCourse($lesson), 404);
        abort_unless($this->certificateService->enrollmentHasIdentity($enrollment), 403, 'Brak danych do pobrania zaświadczenia.');
    }
}
