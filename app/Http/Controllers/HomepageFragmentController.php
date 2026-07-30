<?php

namespace App\Http\Controllers;

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
}
