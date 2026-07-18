<?php

namespace App\Support;

/**
 * Dane do sekcji „spotkanie na żywo” na liście szkoleń w panelu uczestnika.
 */
final class DashboardCourseLiveAccess
{
    public function __construct(
        public readonly bool $show,
        public readonly ?string $joinUrl = null,
        public readonly ?string $password = null,
        public readonly ?string $platformLabel = null,
        public readonly ?string $countdownPhase = null,
        public readonly ?string $countdownTargetIso = null,
        public readonly ?string $countdownLabel = null,
    ) {}

    public static function hidden(): self
    {
        return new self(show: false);
    }
}
