<?php

namespace Tests\Unit;

use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\User;
use App\Services\UserCertificateProfileService;
use Tests\TestCase;

class UserCertificateProfileServiceTest extends TestCase
{
    public function test_missing_identity_fields_when_last_name_empty(): void
    {
        $user = new User([
            'first_name' => 'Jan',
            'last_name' => '',
            'email' => 'jan@example.com',
        ]);

        $missing = (new UserCertificateProfileService)->missingIdentityFields($user);

        $this->assertContains('last_name', $missing);
        $this->assertNotContains('first_name', $missing);
    }

    public function test_missing_birth_fields_when_collect_and_required_on_course(): void
    {
        $user = new User([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan@example.com',
            'birth_date' => null,
            'birth_place' => null,
        ]);

        $course = new OnlineCourse([
            'certificate_collect_birth_data' => true,
            'certificate_birth_data_required' => true,
        ]);

        $missing = (new UserCertificateProfileService)->missingFieldsForOnlineCourse($user, $course);

        $this->assertContains('birth_date', $missing);
        $this->assertContains('birth_place', $missing);
    }
}
