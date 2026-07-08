<?php

namespace App\Services;

use App\Models\OnlineCourse;
use App\Models\User;

class UserCertificateProfileService
{
    /**
     * @return list<string> Klucze: first_name, last_name, birth_date, birth_place
     */
    public function missingIdentityFields(User $user): array
    {
        $missing = [];
        if (trim((string) ($user->first_name ?? '')) === '') {
            $missing[] = 'first_name';
        }
        if (trim((string) ($user->last_name ?? '')) === '') {
            $missing[] = 'last_name';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function missingBirthFields(User $user): array
    {
        $missing = [];
        if ($user->birth_date === null) {
            $missing[] = 'birth_date';
        }
        if (trim((string) ($user->birth_place ?? '')) === '') {
            $missing[] = 'birth_place';
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function missingFieldsForOnlineCourse(User $user, OnlineCourse $course): array
    {
        $missing = $this->missingIdentityFields($user);

        if ($course->certificate_collect_birth_data && $course->certificate_birth_data_required) {
            $missing = array_values(array_unique(array_merge($missing, $this->missingBirthFields($user))));
        }

        return $missing;
    }

    public function hasCompleteProfileForOnlineCourse(User $user, OnlineCourse $course): bool
    {
        return $this->missingFieldsForOnlineCourse($user, $course) === [];
    }

    /**
     * @return array{
     *     email: string,
     *     first_name: string,
     *     last_name: string,
     *     birth_date: ?string,
     *     birth_place: ?string,
     * }
     */
    public function holderPayloadFromUser(User $user): array
    {
        return [
            'email' => (string) $user->email,
            'first_name' => trim((string) $user->first_name),
            'last_name' => trim((string) $user->last_name),
            'birth_date' => $user->birth_date?->format('Y-m-d'),
            'birth_place' => trim((string) ($user->birth_place ?? '')) !== ''
                ? trim((string) $user->birth_place)
                : null,
        ];
    }
}
