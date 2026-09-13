<?php

namespace App\Services;

use App\Models\CoursePriceVariant;
use App\Models\PriceOfferHistory;
use App\Models\ProductPrice;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PriceOmnibusService
{
    public function lowestFor(ProductPrice|CoursePriceVariant $subject, ?CarbonInterface $at = null): ?string
    {
        $at = CarbonImmutable::instance($at ?? now('UTC'))->utc();
        if (! $this->isPromotionNow($subject, $at)) {
            return null;
        }

        $type = $subject instanceof ProductPrice
            ? PriceOfferHistory::SUBJECT_PRODUCT_PRICE
            : PriceOfferHistory::SUBJECT_COURSE_VARIANT;
        $livePrice = $subject instanceof ProductPrice
            ? (float) $subject->currentPrice($at)
            : $subject->getCurrentPrice();
        $reductionStart = $this->reductionStartedAt($type, (int) $subject->id, $livePrice, $at);
        $lowest = $this->lowestInWindow($type, (int) $subject->id, $reductionStart->subDays(30), $reductionStart);

        if ($lowest === null) {
            return number_format((float) $subject->price, 2, '.', '');
        }

        return number_format($lowest, 2, '.', '');
    }

    private function isPromotionNow(ProductPrice|CoursePriceVariant $subject, CarbonInterface $at): bool
    {
        if ($subject instanceof ProductPrice) {
            return $subject->isPromotionActive($at);
        }

        return $subject->isPromotionActive();
    }

    private function reductionStartedAt(string $type, int $id, float $livePrice, CarbonImmutable $at): CarbonImmutable
    {
        $rows = PriceOfferHistory::query()
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->where('excluded_from_omnibus', false)
            ->where('effective_from', '<=', $at)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        $start = $at;
        foreach ($rows as $row) {
            if (
                $row->kind === PriceOfferHistory::KIND_PROMOTIONAL
                && (float) $row->offered_price === $livePrice
            ) {
                $start = CarbonImmutable::instance($row->effective_from)->utc();

                continue;
            }

            break;
        }

        return $start;
    }

    private function lowestInWindow(string $type, int $id, CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        $rows = PriceOfferHistory::query()
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->where('excluded_from_omnibus', false)
            ->where('effective_from', '<', $to)
            ->where(function ($query) use ($from) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $from);
            })
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return (float) $rows->min(fn (PriceOfferHistory $row) => (float) $row->offered_price);
    }
}
