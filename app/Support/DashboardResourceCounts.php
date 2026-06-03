<?php

namespace App\Support;

use App\Models\OnlineCourseEnrollment;
use App\Models\Participant;
use Illuminate\Contracts\Auth\Authenticatable;

class DashboardResourceCounts
{
    /**
     * @return array{szkolenia: int, online_courses: int, zaswiadczenia: int, total: int, twoje_zasoby_url: string}
     */
    public static function forUser(?Authenticatable $user): array
    {
        if (! $user) {
            return [
                'szkolenia' => 0,
                'online_courses' => 0,
                'zaswiadczenia' => 0,
                'total' => 0,
                'twoje_zasoby_url' => route('dashboard'),
            ];
        }

        $emailNormalized = strtolower(trim((string) $user->email));
        $onlineEmail = OnlineCourseEnrollment::normalizeEmail($user->email);

        $szkoleniaCount = $emailNormalized !== ''
            ? Participant::query()
                ->whereRaw('LOWER(TRIM(participants.email)) = ?', [$emailNormalized])
                ->count()
            : 0;

        $zaswiadczeniaCount = $emailNormalized !== ''
            ? Participant::query()
                ->whereRaw('LOWER(TRIM(participants.email)) = ?', [$emailNormalized])
                ->whereHas('course')
                ->count()
            : 0;

        $onlineCoursesCount = 0;
        if ($onlineEmail) {
            $onlineCoursesCount = OnlineCourseEnrollment::query()
                ->where('email', $onlineEmail)
                ->whereHas('onlineCourse', function ($q) {
                    $q->where('is_active', true)->where('visible_in_dashboard', true);
                })
                ->count();
        }

        return [
            'szkolenia' => $szkoleniaCount,
            'online_courses' => $onlineCoursesCount,
            'zaswiadczenia' => $zaswiadczeniaCount,
            'total' => $szkoleniaCount + $onlineCoursesCount,
            'twoje_zasoby_url' => self::resolveTwojeZasobyUrl($szkoleniaCount, $onlineCoursesCount),
        ];
    }

    /**
     * @param  array{szkolenia: int, online_courses: int, zaswiadczenia: int, total: int, twoje_zasoby_url?: string}  $counts
     */
    public static function twojeZasobyUrlFromCounts(array $counts): string
    {
        return self::resolveTwojeZasobyUrl(
            (int) ($counts['szkolenia'] ?? 0),
            (int) ($counts['online_courses'] ?? 0),
        );
    }

    public static function twojeZasobyUrlForUser(?Authenticatable $user): string
    {
        return self::twojeZasobyUrlFromCounts(self::forUser($user));
    }

    private static function resolveTwojeZasobyUrl(int $szkoleniaCount, int $onlineCoursesCount): string
    {
        if ($szkoleniaCount > 0) {
            return route('dashboard.szkolenia');
        }

        if ($onlineCoursesCount > 0) {
            return route('dashboard.online-courses.index');
        }

        return route('dashboard');
    }
}
