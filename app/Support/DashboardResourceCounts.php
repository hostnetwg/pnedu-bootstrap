<?php

namespace App\Support;

use App\Models\OnlineCourseEnrollment;
use App\Models\Participant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

class DashboardResourceCounts
{
    private static ?int $cachedUserId = null;

    /** @var array{szkolenia: int, online_courses: int, zaswiadczenia: int, total: int, twoje_zasoby_url: string}|null */
    private static ?array $cachedCounts = null;

    /** @var array<string, array{all: int, paid: int, free: int, with_course: int}> */
    private static array $participantEmailStatsCache = [];

    /**
     * @return array{szkolenia: int, online_courses: int, zaswiadczenia: int, total: int, twoje_zasoby_url: string}
     */
    public static function forUser(?Authenticatable $user): array
    {
        $userId = $user?->getAuthIdentifier();

        if (self::$cachedCounts !== null && self::$cachedUserId === $userId) {
            return self::$cachedCounts;
        }

        if (! $user) {
            self::$cachedUserId = null;
            self::$cachedCounts = [
                'szkolenia' => 0,
                'online_courses' => 0,
                'zaswiadczenia' => 0,
                'total' => 0,
                'twoje_zasoby_url' => route('dashboard'),
            ];

            return self::$cachedCounts;
        }

        self::$cachedUserId = $userId;

        return self::$cachedCounts = Cache::remember(
            'dashboard.resource-counts.v1.'.$userId,
            now()->addMinutes(2),
            fn () => self::computeForUser($user),
        );
    }

    /**
     * Liczby szkoleń w filtrach listy dashboardu (all / paid / free) — jedno zapytanie z menu.
     *
     * @return array{all: int, paid: int, free: int}
     */
    public static function szkoleniaFilterCountsForEmail(string $emailNormalized): array
    {
        $stats = self::participantEmailStats($emailNormalized);

        return [
            'all' => $stats['all'],
            'paid' => $stats['paid'],
            'free' => $stats['free'],
        ];
    }

    /**
     * @return array{all: int, paid: int, free: int, with_course: int}
     */
    public static function participantEmailStats(string $emailNormalized): array
    {
        if ($emailNormalized === '') {
            return ['all' => 0, 'paid' => 0, 'free' => 0, 'with_course' => 0];
        }

        if (isset(self::$participantEmailStatsCache[$emailNormalized])) {
            return self::$participantEmailStatsCache[$emailNormalized];
        }

        $row = Participant::query()
            ->forNormalizedEmail($emailNormalized)
            ->leftJoin('courses', 'participants.course_id', '=', 'courses.id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN courses.id IS NOT NULL AND courses.is_paid = 1 THEN 1 ELSE 0 END) as paid')
            ->selectRaw('SUM(CASE WHEN courses.id IS NOT NULL AND courses.is_paid = 0 THEN 1 ELSE 0 END) as free')
            ->selectRaw('SUM(CASE WHEN courses.id IS NOT NULL THEN 1 ELSE 0 END) as with_course')
            ->first();

        if (! $row) {
            return self::$participantEmailStatsCache[$emailNormalized] = [
                'all' => 0,
                'paid' => 0,
                'free' => 0,
                'with_course' => 0,
            ];
        }

        return self::$participantEmailStatsCache[$emailNormalized] = [
            'all' => (int) ($row->total ?? 0),
            'paid' => (int) ($row->paid ?? 0),
            'free' => (int) ($row->free ?? 0),
            'with_course' => (int) ($row->with_course ?? 0),
        ];
    }

    /**
     * @return array{szkolenia: int, online_courses: int, zaswiadczenia: int, total: int, twoje_zasoby_url: string}
     */
    private static function computeForUser(Authenticatable $user): array
    {
        $emailNormalized = strtolower(trim((string) $user->email));
        $onlineEmail = OnlineCourseEnrollment::normalizeEmail($user->email);

        $participantStats = self::participantEmailStats($emailNormalized);

        $onlineCoursesCount = 0;
        if ($onlineEmail) {
            $onlineCoursesCount = OnlineCourseEnrollment::query()
                ->where('email', $onlineEmail)
                ->whereHas('onlineCourse', function ($q) {
                    $q->where('is_active', true)->where('visible_in_dashboard', true);
                })
                ->count();
        }

        $szkoleniaCount = $participantStats['all'];

        return [
            'szkolenia' => $szkoleniaCount,
            'online_courses' => $onlineCoursesCount,
            'zaswiadczenia' => $participantStats['with_course'],
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
