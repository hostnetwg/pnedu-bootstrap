<?php

namespace App\Http\Controllers;

use App\Models\SurveySetting;
use App\Models\SurveyTestimonial;
use App\Services\StatisticsService;
use App\Support\FeaturedHomepageTrainingOffers;
use App\Support\HomepageLiveMeetingNotice;
use App\Support\UpcomingPneduCourses;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public const HOMEPAGE_TESTIMONIALS_LIMIT = 6;

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
        [$homepageTestimonials, $homepageTestimonialsTotal] = $this->publishedTestimonials();
        $showTestimonialDate = (bool) (SurveySetting::getSettings()->show_testimonial_date_on_homepage ?? false);

        return view('welcome', compact(
            'courses',
            'statistics',
            'homepageLiveNotice',
            'featuredTrainingOffers',
            'featuredTrainingOffersTotal',
            'homepageTestimonials',
            'homepageTestimonialsTotal',
            'showTestimonialDate',
        ));
    }

    /**
     * @return array{0: Collection<int, SurveyTestimonial>, 1: int}
     */
    private function publishedTestimonials(): array
    {
        try {
            $base = SurveyTestimonial::query()
                ->where('is_published', true)
                ->where('publish_consent', true);

            $total = (clone $base)->count();
            $items = (clone $base)
                ->orderByDesc('created_at')
                ->limit(self::HOMEPAGE_TESTIMONIALS_LIMIT)
                ->get();

            return [$items, $total];
        } catch (\Throwable) {
            return [collect(), 0];
        }
    }
}
