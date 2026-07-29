<?php

namespace App\Http\Controllers;

use App\Models\TrainingOffer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingOfferController extends Controller
{
    public function pedagogicalCouncils(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $offers = TrainingOffer::query()
            ->with('instructor')
            ->publiclyVisible()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%'.$search.'%')
                        ->orWhere('summary', 'like', '%'.$search.'%')
                        ->orWhere('scope', 'like', '%'.$search.'%')
                        ->orWhere('audience', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('training-offers.pedagogical-councils.index', compact('offers', 'search'));
    }

    public function showPedagogicalCouncilOffer(string $slug): View
    {
        $offer = TrainingOffer::query()
            ->with('instructor')
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('training-offers.pedagogical-councils.show', compact('offer'));
    }
}
