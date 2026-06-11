<?php

namespace App\Http\Controllers;

use App\Support\UpcomingPneduCourses;
use Illuminate\View\View;

class DashboardFragmentController extends Controller
{
    /**
     * Fragment HTML bocznej sekcji „Aktualna oferta” (ładowany asynchronicznie).
     */
    public function aktualnaOferta(): View
    {
        return view('dashboard.partials.sidebar-nav-offer', [
            'dashboardUpcomingCourses' => UpcomingPneduCourses::forSidebar(),
        ]);
    }
}
