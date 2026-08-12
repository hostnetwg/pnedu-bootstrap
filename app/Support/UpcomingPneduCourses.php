<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UpcomingPneduCourses
{
    /** Limit listy na stronie głównej (sidebar dashboardu pokazuje wszystkie). */
    public const HOMEPAGE_LIMIT = 9;

    /** @deprecated Używaj HOMEPAGE_LIMIT — zachowane dla kompatybilności cache/API. */
    public const SIDEBAR_LIMIT = self::HOMEPAGE_LIMIT;

    public const SIDEBAR_CACHE_KEY = 'dashboard.upcoming-offer.sidebar.v2';

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
     * Wszystkie nadchodzące / trwające szkolenia do sidebara „Aktualna oferta” (cache globalny).
     *
     * @return Collection<int, Course>
     */
    public static function forSidebar(?int $limit = null): Collection
    {
        return Cache::remember(
            self::cacheKey($limit),
            now()->addMinutes(self::SIDEBAR_CACHE_TTL_MINUTES),
            function () use ($limit) {
                $query = self::baseQuery();
                if ($limit !== null) {
                    $query->limit($limit);
                }

                return $query->get();
            }
        );
    }

    /**
     * Nadchodzące szkolenia na stronie głównej (ograniczona lista).
     *
     * @return Collection<int, Course>
     */
    public static function forHomepage(int $limit = self::HOMEPAGE_LIMIT): Collection
    {
        return self::forSidebar($limit);
    }

    public static function cacheKey(?int $limit = null): string
    {
        return self::SIDEBAR_CACHE_KEY.'.'.($limit === null ? 'all' : (string) $limit);
    }

    /** Po zmianie kursu w panelu adm — wywoływane przez wewnętrzne API pneadm → pnedu. */
    public static function forgetCache(?int $limit = null): void
    {
        if ($limit !== null) {
            Cache::forget(self::cacheKey($limit));

            return;
        }

        Cache::forget(self::cacheKey(null));
        Cache::forget(self::cacheKey(self::HOMEPAGE_LIMIT));
        // legacy keys (v1 + poprzedni limit 6 współdzielony ze sidebarem / homepage)
        Cache::forget(self::cacheKey(6));
        Cache::forget('dashboard.upcoming-offer.sidebar.v1.'.self::HOMEPAGE_LIMIT);
        Cache::forget('dashboard.upcoming-offer.sidebar.v1.6');
        Cache::forget('dashboard.upcoming-offer.sidebar.v1.9');
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
