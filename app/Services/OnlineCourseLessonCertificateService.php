<?php

namespace App\Services;

use App\Models\Course;
use App\Models\OnlineCourseEnrollment;
use App\Models\OnlineCourseLesson;
use App\Models\Participant;

class OnlineCourseLessonCertificateService
{
    public function __construct(
        private CertificateRegistrationStatusService $statusService,
    ) {}

    public function lessonHasLinkedCourse(OnlineCourseLesson $lesson): bool
    {
        return $lesson->linked_course_id !== null && (int) $lesson->linked_course_id > 0;
    }

    public function enrollmentHasIdentity(OnlineCourseEnrollment $enrollment): bool
    {
        return trim((string) ($enrollment->first_name ?? '')) !== ''
            && trim((string) ($enrollment->last_name ?? '')) !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStatusForLesson(OnlineCourseLesson $lesson): ?array
    {
        if (! $this->lessonHasLinkedCourse($lesson)) {
            return null;
        }

        return $this->statusService->getStatusByCourse((int) $lesson->linked_course_id);
    }

    public function isAlreadyRegistered(OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): bool
    {
        if (! $this->lessonHasLinkedCourse($lesson)) {
            return false;
        }

        $email = OnlineCourseEnrollment::normalizeEmail($enrollment->email);
        if ($email === null) {
            return false;
        }

        return Participant::query()
            ->where('course_id', (int) $lesson->linked_course_id)
            ->forNormalizedEmail($email)
            ->exists();
    }

    /**
     * @return array{
     *     show: bool,
     *     show_button: bool,
     *     needs_registration: bool,
     *     already_registered: bool,
     *     can_download: bool,
     *     linked_course_id: ?int,
     *     course_title: ?string,
     *     dashboard_certificate_url: ?string,
     *     message: ?string,
     *     status: ?array
     * }
     */
    public function lessonCertificateContext(OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): array
    {
        $empty = [
            'show' => false,
            'show_button' => false,
            'needs_registration' => false,
            'already_registered' => false,
            'can_download' => false,
            'linked_course_id' => null,
            'course_title' => null,
            'dashboard_certificate_url' => null,
            'message' => null,
            'status' => null,
        ];

        if (! $this->lessonHasLinkedCourse($lesson)) {
            return $empty;
        }

        $linkedCourseId = (int) $lesson->linked_course_id;
        $linkedCourse = Course::query()->find($linkedCourseId);
        $canDownload = $linkedCourse !== null
            && ($linkedCourse->certificate_download_status ?? '') === 'download_enabled';
        $dashboardUrl = route('dashboard.zaswiadczenia.course', ['course' => $linkedCourseId]);

        if (! $this->enrollmentHasIdentity($enrollment)) {
            return array_merge($empty, [
                'show' => true,
                'linked_course_id' => $linkedCourseId,
                'course_title' => $linkedCourse?->title ? trim(strip_tags((string) $linkedCourse->title)) : null,
                'message' => 'Aby pobrać zaświadczenie, skontaktuj się z administratorem — w zapisie na kurs brakuje imienia lub nazwiska.',
            ]);
        }

        $status = $this->getStatusForLesson($lesson);
        if ($status === null) {
            return array_merge($empty, [
                'show' => true,
                'linked_course_id' => $linkedCourseId,
                'course_title' => $linkedCourse?->title ? trim(strip_tags((string) $linkedCourse->title)) : null,
                'message' => 'Usługa zaświadczeń jest chwilowo niedostępna.',
            ]);
        }

        $courseTitle = trim(strip_tags((string) ($status['course_title'] ?? $linkedCourse?->title ?? 'Szkolenie')));
        $alreadyRegistered = $this->isAlreadyRegistered($enrollment, $lesson);
        $registrationOpen = ($status['_http_successful'] ?? false) && ! empty($status['active']);

        if ($alreadyRegistered) {
            return [
                'show' => true,
                'show_button' => true,
                'needs_registration' => false,
                'already_registered' => true,
                'can_download' => $canDownload,
                'linked_course_id' => $linkedCourseId,
                'course_title' => $courseTitle,
                'dashboard_certificate_url' => $dashboardUrl,
                'message' => $canDownload ? null : 'Zaświadczenie jest w przygotowaniu — pobieranie nie jest jeszcze udostępnione.',
                'status' => $status,
            ];
        }

        if (! $registrationOpen) {
            return [
                'show' => true,
                'show_button' => false,
                'needs_registration' => false,
                'already_registered' => false,
                'can_download' => false,
                'linked_course_id' => $linkedCourseId,
                'course_title' => $courseTitle,
                'dashboard_certificate_url' => $dashboardUrl,
                'message' => $status['message'] ?? 'Rejestracja zaświadczenia nie jest aktywna dla tego szkolenia.',
                'status' => $status,
            ];
        }

        if (! $canDownload) {
            return [
                'show' => true,
                'show_button' => false,
                'needs_registration' => false,
                'already_registered' => false,
                'can_download' => false,
                'linked_course_id' => $linkedCourseId,
                'course_title' => $courseTitle,
                'dashboard_certificate_url' => $dashboardUrl,
                'message' => 'Rejestracja jest włączona, ale pobieranie zaświadczeń nie jest jeszcze udostępnione dla tego szkolenia.',
                'status' => $status,
            ];
        }

        return [
            'show' => true,
            'show_button' => true,
            'needs_registration' => true,
            'already_registered' => false,
            'can_download' => true,
            'linked_course_id' => $linkedCourseId,
            'course_title' => $courseTitle,
            'dashboard_certificate_url' => $dashboardUrl,
            'message' => null,
            'status' => $status,
        ];
    }
}
