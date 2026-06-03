<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UpcomingPneduCourses
{
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
            ->with('instructor');
    }
}
