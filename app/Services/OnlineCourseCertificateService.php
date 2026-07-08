<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\OnlineCourseEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OnlineCourseCertificateService
{
    public function __construct(
        private UserCertificateProfileService $userProfile,
    ) {}

    public function certificatesEnabled(OnlineCourseEnrollment $enrollment): bool
    {
        $course = $enrollment->onlineCourse;

        return $course !== null && $course->certificatesEnabledForDownload();
    }

    /**
     * @return array{
     *     show: bool,
     *     can_download: bool,
     *     needs_profile: bool,
     *     certificate_url: ?string,
     *     download_url: ?string,
     *     message: ?string,
     *     has_certificate: bool,
     * }
     */
    public function contextForEnrollment(OnlineCourseEnrollment $enrollment, ?User $user = null): array
    {
        $empty = [
            'show' => false,
            'can_download' => false,
            'needs_profile' => false,
            'certificate_url' => null,
            'download_url' => null,
            'message' => null,
            'has_certificate' => false,
        ];

        if (! $this->certificatesEnabled($enrollment)) {
            return $empty;
        }

        $user = $user ?? Auth::user();
        if (! $user) {
            return $empty;
        }

        if (! $enrollment->emailMatchesUser($user->email ?? '')) {
            return $empty;
        }

        $course = $enrollment->onlineCourse;
        $certificateUrl = route('dashboard.online-courses.certificate.show', $enrollment);
        $downloadUrl = route('dashboard.online-courses.certificate.download', $enrollment);
        $hasCertificate = Certificate::on('pneadm')
            ->where('online_course_enrollment_id', $enrollment->id)
            ->exists();

        $needsProfile = ! $this->userProfile->hasCompleteProfileForOnlineCourse($user, $course);

        return [
            'show' => true,
            'can_download' => ! $needsProfile,
            'needs_profile' => $needsProfile,
            'certificate_url' => $certificateUrl,
            'download_url' => $downloadUrl,
            'message' => $needsProfile
                ? 'Uzupełnij dane w profilu konta, aby pobrać zaświadczenie.'
                : null,
            'has_certificate' => $hasCertificate,
        ];
    }
}
