<?php

namespace App\Http\Controllers;

use App\Models\TrainingOffer;
use App\Services\StatisticsService;
use App\Support\HomepageLiveMeetingNotice;
use App\Support\UpcomingPneduCourses;

class HomeController extends Controller
{
    public function __construct(
        protected StatisticsService $statisticsService,
    ) {}

    public function index()
    {
        $courses = UpcomingPneduCourses::forHomepage();
        $statistics = $this->statisticsService->getStatistics();
        $homepageLiveNotice = HomepageLiveMeetingNotice::forCurrentUser();
        $featuredTrainingOffers = TrainingOffer::query()
            ->with('instructor')
            ->publiclyVisible()
            ->where('featured_on_homepage', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(3)
            ->get();

        return view('welcome', compact('courses', 'statistics', 'homepageLiveNotice', 'featuredTrainingOffers'));
    }
}
