<?php

namespace App\Support;

use App\Models\TrainingOffer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class FeaturedHomepageTrainingOffers
{
    public const INITIAL_LIMIT = 6;

    public const BATCH_LIMIT = 6;

    /**
     * @return Builder<TrainingOffer>
     */
    public static function query(): Builder
    {
        return TrainingOffer::query()
            ->with('instructor')
            ->publiclyVisible()
            ->where('featured_on_homepage', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public static function count(): int
    {
        return (int) self::query()->count();
    }

    /**
     * @return Collection<int, TrainingOffer>
     */
    public static function page(int $offset, int $limit): Collection
    {
        $offset = max(0, $offset);
        $limit = max(1, min(24, $limit));

        return self::query()
            ->skip($offset)
            ->take($limit)
            ->get();
    }
}
