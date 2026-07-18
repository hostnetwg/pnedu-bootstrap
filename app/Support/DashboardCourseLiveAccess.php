<?php

namespace App\Support;

/**
 * Dane do sekcji „spotkanie na żywo” (panel + homepage).
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
        public readonly bool $joinUnlocked = false,
        public readonly ?string $joinUnlockAtIso = null,
        public readonly ?string $joinUnlockHint = null,
    ) {}

    public static function hidden(): self
    {
        return new self(show: false);
    }
}
