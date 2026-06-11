<?php

namespace App\Http\Controllers;

use App\Support\DashboardParticipantsListing;
use App\Support\UpcomingPneduCourses;
use Illuminate\Http\Request;
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

    /**
     * Fragment listy szkoleń (filtry + wyniki) — przełączanie bez pełnego przeładowania strony.
     */
    public function szkoleniaList(Request $request): View
    {
        return view('dashboard.partials.szkolenia-list-inner', array_merge(
            DashboardParticipantsListing::forAuthenticatedUser($request),
            ['szkoleniaFilterRoute' => 'dashboard.szkolenia'],
        ));
    }
}
