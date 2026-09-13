<?php

namespace App\Services;

use App\Models\OnlineCourseEnrollment;
use App\Support\StorefrontCourseAccess;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class StorefrontCourseAccessService
{
    /**
     * @param  iterable<int|string|null>  $courseIds
     * @return array<int, StorefrontCourseAccess>
     */
    public function mapForUser(?Authenticatable $user, iterable $courseIds): array
    {
        $ids = Collection::make($courseIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $email = OnlineCourseEnrollment::normalizeEmail($user?->email ?? null);
        if ($email === null || $ids->isEmpty()) {
            return [];
        }

        $enrollments = OnlineCourseEnrollment::query()
            ->where('email', $email)
            ->whereIn('online_course_id', $ids->all())
            ->get()
            ->keyBy(fn (OnlineCourseEnrollment $row) => (int) $row->online_course_id);

        $map = [];
        foreach ($ids as $id) {
            $enrollment = $enrollments->get($id);
            if ($enrollment) {
                $map[$id] = StorefrontCourseAccess::fromEnrollment($enrollment);
            }
        }

        return $map;
    }

    public function forCourse(?Authenticatable $user, ?int $courseId): ?StorefrontCourseAccess
    {
        if ($courseId === null || $courseId < 1) {
            return null;
        }

        return $this->mapForUser($user, [$courseId])[$courseId] ?? null;
    }
}
