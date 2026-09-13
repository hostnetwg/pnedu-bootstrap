<?php

namespace App\Support;

use App\Models\OnlineCourseEnrollment;

class StorefrontCourseAccess
{
    public const STATE_UNLIMITED = 'unlimited';

    public const STATE_ACTIVE = 'active';

    public const STATE_SCHEDULED = 'scheduled';

    public const STATE_EXPIRED = 'expired';

    public function __construct(
        public readonly OnlineCourseEnrollment $enrollment,
        public readonly string $state,
    ) {}

    public static function fromEnrollment(OnlineCourseEnrollment $enrollment): self
    {
        if (! $enrollment->hasAccessStarted()) {
            $state = self::STATE_SCHEDULED;
        } elseif ($enrollment->hasExpiredAccess()) {
            $state = self::STATE_EXPIRED;
        } elseif ($enrollment->access_expires_at === null) {
            $state = self::STATE_UNLIMITED;
        } else {
            $state = self::STATE_ACTIVE;
        }

        return new self($enrollment, $state);
    }

    public function canEnter(): bool
    {
        return in_array($this->state, [self::STATE_UNLIMITED, self::STATE_ACTIVE], true);
    }

    public function isUnlimited(): bool
    {
        return $this->state === self::STATE_UNLIMITED;
    }

    public function showPriceList(): bool
    {
        return $this->state !== self::STATE_UNLIMITED;
    }

    public static function purchaseButtonLabel(int $paidVariantCount): string
    {
        return $paidVariantCount > 1
            ? 'Zamawiam ten wariant'
            : 'Zamawiam kurs';
    }

    public function buyButtonLabel(int $paidVariantCount = 1): string
    {
        return $this->state === self::STATE_ACTIVE
            ? 'Przedłuż dostęp'
            : self::purchaseButtonLabel($paidVariantCount);
    }

    public function dashboardUrl(): string
    {
        return route('dashboard.online-courses.show', $this->enrollment);
    }

    public function endsLabel(): ?string
    {
        return $this->enrollment->accessEndLabel();
    }

    public function startsLabel(): ?string
    {
        return $this->enrollment->accessStartLabel();
    }
}
