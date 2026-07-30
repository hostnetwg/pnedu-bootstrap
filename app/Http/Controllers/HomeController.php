<?php

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use App\Support\FeaturedHomepageTrainingOffers;
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
        $featuredTrainingOffersTotal = FeaturedHomepageTrainingOffers::count();
        $featuredTrainingOffers = FeaturedHomepageTrainingOffers::page(
            0,
            FeaturedHomepageTrainingOffers::INITIAL_LIMIT
        );

        return view('welcome', compact(
            'courses',
            'statistics',
            'homepageLiveNotice',
            'featuredTrainingOffers',
            'featuredTrainingOffersTotal'
        ));
    }
}
