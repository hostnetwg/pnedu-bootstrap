<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UpcomingPneduCourses
{
    public const SIDEBAR_LIMIT = 6;

    public const SIDEBAR_CACHE_KEY = 'dashboard.upcoming-offer.sidebar.v1';

    public const SIDEBAR_CACHE_TTL_MINUTES = 10;

    /**
     * Nadchodzące szkolenia online widoczne na pnedu.pl (jak na stronie głównej).
     *
     * @return Collection<int, Course>
     */
    public static function query(): Collection
    {
        return self::baseQuery()->get();
    }

    /**
     * Ograniczona lista nadchodzących szkoleń do sidebara dashboardu (cache globalny).
     *
     * @return Collection<int, Course>
     */
    public static function forSidebar(int $limit = self::SIDEBAR_LIMIT): Collection
    {
        $cacheKey = self::SIDEBAR_CACHE_KEY.'.'.$limit;

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::SIDEBAR_CACHE_TTL_MINUTES),
            fn () => self::baseQuery()->limit($limit)->get()
        );
    }

    /**
     * @return Builder<Course>
     */
    public static function baseQuery(): Builder
    {
        return Course::query()
            ->where('is_active', true)
            ->where('show_on_pnedu', true)
            ->where('type', 'online')
            ->where(function ($query) {
                $query->where('end_date', '>', now())
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery->whereNull('end_date')
                            ->where('start_date', '>', now());
                    });
            })
            ->whereNull('deleted_at')
            ->orderBy('start_date', 'asc')
            ->with(['instructor', 'priceVariants']);
    }
}
