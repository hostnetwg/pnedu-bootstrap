<?php

namespace App\View\Composers;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TikCourseModalComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view): void
    {
        try {
            // Pobierz wszystkie course_id z course_series_course dla serii TIK (id = 1)
            $seriesCourseIds = DB::connection('pneadm')
                ->table('course_series_course')
                ->where('course_series_id', 1)
                ->pluck('course_id')
                ->toArray();

            if (empty($seriesCourseIds)) {
                $view->with('upcomingTikCourse', null);
                return;
            }

            // Pobierz najbliższe nadchodzące szkolenie TIK (gdzie start_date jeszcze nie nadeszła)
            $upcomingCourse = Course::with(['instructor', 'onlineDetail'])
                ->whereIn('id', $seriesCourseIds)
                ->where('is_active', true)
                ->where('start_date', '>', now())
                ->orderBy('start_date', 'asc')
                ->first();

            $view->with('upcomingTikCourse', $upcomingCourse);
        } catch (\Exception $e) {
            // W przypadku błędu, nie pokazuj modalki
            $view->with('upcomingTikCourse', null);
        }
    }
}


