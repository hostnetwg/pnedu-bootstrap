<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class WithdrawalWindowService
{
    public function timezone(): string
    {
        return (string) config('app.timezone', 'Europe/Warsaw');
    }

    public function windowDays(): int
    {
        return (int) config('legal.early_performance.window_days', 14);
    }

    public function deadline(CarbonInterface $concludedAt): CarbonImmutable
    {
        return CarbonImmutable::instance($concludedAt)
            ->timezone($this->timezone())
            ->addDays($this->windowDays())
            ->endOfDay();
    }

    public function plannedStart(?CarbonInterface $scheduledStart, CarbonInterface $concludedAt): CarbonImmutable
    {
        if ($scheduledStart === null) {
            return CarbonImmutable::instance($concludedAt)->timezone($this->timezone());
        }

        return CarbonImmutable::instance($scheduledStart)->timezone($this->timezone());
    }

    public function startsWithinWindow(?CarbonInterface $scheduledStart, ?CarbonInterface $concludedAt = null): bool
    {
        $concluded = $concludedAt ?? now($this->timezone());

        return $this->plannedStart($scheduledStart, $concluded)
            ->lessThanOrEqualTo($this->deadline($concluded));
    }
}
