<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\ProductPrice;
use Carbon\CarbonImmutable;

class ProductAccessExpiryService
{
    public function resolve(
        OrderItem $item,
        bool $hasExistingEnrollment,
        ?CarbonImmutable $previousExpiry,
        ?CarbonImmutable $grantedAt = null
    ): ?CarbonImmutable {
        if ($hasExistingEnrollment && $previousExpiry === null) {
            return null;
        }

        if ($item->access_policy === ProductPrice::ACCESS_UNLIMITED) {
            return null;
        }

        if ($item->access_policy === ProductPrice::ACCESS_FIXED_UNTIL) {
            $fixed = $item->access_expires_at
                ? CarbonImmutable::instance($item->access_expires_at)->utc()
                : null;
            if ($fixed === null) {
                throw new \InvalidArgumentException('Brak daty końcowej w pozycji zamówienia.');
            }

            if ($previousExpiry && $previousExpiry->gt($fixed)) {
                return $previousExpiry;
            }

            return $fixed;
        }

        if ($item->access_policy !== ProductPrice::ACCESS_DURATION_FROM_GRANT) {
            throw new \InvalidArgumentException('Nieznana reguła dostępu w pozycji zamówienia.');
        }

        $grant = ($grantedAt ?? CarbonImmutable::now('UTC'))->utc();
        if ($item->access_starts_at) {
            $scheduled = CarbonImmutable::instance($item->access_starts_at)->utc();
            if ($scheduled->gt($grant)) {
                $grant = $scheduled;
            }
        }
        $base = $previousExpiry && $previousExpiry->gt($grant) ? $previousExpiry : $grant;
        $value = (int) $item->access_duration_value;
        if ($value < 1 || ! in_array($item->access_duration_unit, ['days', 'months', 'years'], true)) {
            throw new \InvalidArgumentException('Nieprawidłowy okres dostępu w pozycji zamówienia.');
        }

        return match ($item->access_duration_unit) {
            'days' => $base->addDays($value),
            'months' => $base->addMonthsNoOverflow($value),
            'years' => $base->addYearsNoOverflow($value),
        };
    }
}
