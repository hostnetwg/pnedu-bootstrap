<?php

namespace App\Http\Controllers;

use App\Models\SurveyTestimonial;
use App\Support\FeaturedHomepageTrainingOffers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomepageFragmentController extends Controller
{
    /**
     * Fragment HTML kolejnych kart wyróżnionych ofert RP (dociąganie karuzeli).
     */
    public function featuredTrainingOffers(Request $request): View|Response
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit = max(1, min(
            24,
            (int) $request->query('limit', FeaturedHomepageTrainingOffers::BATCH_LIMIT)
        ));

        $total = FeaturedHomepageTrainingOffers::count();
        $offers = FeaturedHomepageTrainingOffers::page($offset, $limit);

        return response()
            ->view('training-offers.partials.featured-homepage-slides', [
                'offers' => $offers,
            ])
            ->header('X-Featured-Offers-Total', (string) $total)
            ->header('X-Featured-Offers-Count', (string) $offers->count())
            ->header('X-Featured-Offers-Offset', (string) $offset);
    }

    /**
     * Kolejne opublikowane rekomendacje na homepage (poza już pokazanymi).
     */
    public function homepageTestimonials(Request $request): Response
    {
        $exclude = collect(explode(',', (string) $request->query('exclude', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $limit = max(1, min(
            24,
            (int) $request->query('limit', HomeController::HOMEPAGE_TESTIMONIALS_LIMIT)
        ));

        try {
            $query = SurveyTestimonial::query()
                ->where('is_published', true)
                ->where('publish_consent', true);

            $total = (clone $query)->count();

            if ($exclude !== []) {
                $query->whereNotIn('id', $exclude);
            }

            $testimonials = $query
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        } catch (\Throwable) {
            $total = 0;
            $testimonials = collect();
        }

        $loaded = count($exclude) + $testimonials->count();

        return response()
            ->view('partials.homepage-testimonial-cards', [
                'testimonials' => $testimonials,
            ])
            ->header('X-Testimonials-Total', (string) $total)
            ->header('X-Testimonials-Count', (string) $testimonials->count())
            ->header('X-Testimonials-Loaded', (string) $loaded);
    }
}
