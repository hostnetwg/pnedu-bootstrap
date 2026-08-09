<?php

namespace App\Http\Controllers;

use App\Models\SurveyTestimonial;
use App\Services\StatisticsService;
use App\Support\FeaturedHomepageTrainingOffers;
use App\Support\HomepageLiveMeetingNotice;
use App\Support\UpcomingPneduCourses;
use Illuminate\Support\Collection;

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
        $homepageTestimonials = $this->publishedTestimonials();

        return view('welcome', compact(
            'courses',
            'statistics',
            'homepageLiveNotice',
            'featuredTrainingOffers',
            'featuredTrainingOffersTotal',
            'homepageTestimonials',
        ));
    }

    /**
     * @return Collection<int, SurveyTestimonial>
     */
    private function publishedTestimonials(): Collection
    {
        try {
            return SurveyTestimonial::query()->published()->limit(6)->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}
