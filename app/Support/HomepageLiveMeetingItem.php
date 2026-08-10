<?php

namespace App\Support;

/**
 * Jedno szkolenie live w pasku na stronie głównej.
 */
final class HomepageLiveMeetingItem
{
    public function __construct(
        public readonly string $courseTitle,
        public readonly string $startDateLabel,
        public readonly DashboardCourseLiveAccess $live,
    ) {}
}
