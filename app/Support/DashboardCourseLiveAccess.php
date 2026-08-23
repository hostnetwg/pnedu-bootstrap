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
        public readonly bool $clickmeetingJoinEnabled = true,
        public readonly bool $embedEnabled = false,
        public readonly ?string $embedUrl = null,
        /** Powrót do pokoju bez auto-pełnego ekranu (np. ze strony /po-szkoleniu). */
        public readonly ?string $embedReturnUrl = null,
    ) {}

    public static function hidden(): self
    {
        return new self(show: false);
    }
}
