<?php

namespace App\View\Composers;

use App\Support\DashboardResourceCounts;
use App\Support\UpcomingPneduCourses;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardResourceCountsComposer
{
    public function compose(View $view): void
    {
        $counts = DashboardResourceCounts::forUser(Auth::user());

        $view->with([
            'dashboardSzkoleniaCount' => $counts['szkolenia'],
            'dashboardOnlineCoursesCount' => $counts['online_courses'],
            'dashboardZaswiadczeniaCount' => $counts['zaswiadczenia'],
            'dashboardMojeZasobyCount' => $counts['total'],
            'dashboardTwojeZasobyUrl' => $counts['twoje_zasoby_url'],
            'dashboardUpcomingCourses' => UpcomingPneduCourses::query(),
        ]);
    }
}
