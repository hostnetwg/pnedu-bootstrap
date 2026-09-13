<?php

namespace App\Support;

use App\Models\FormOrder;
use App\Models\OnlineCourse;
use App\Models\OrderItemRecipient;

final readonly class PendingProductCourseAccess
{
    public const STATE_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATE_AWAITING_ACCESS = 'awaiting_access';

    public function __construct(
        public FormOrder $order,
        public OrderItemRecipient $recipient,
        public OnlineCourse $course,
        public string $state,
        public ?string $paymentUrl,
        public bool $canResign,
    ) {}

    public function isAwaitingPayment(): bool
    {
        return $this->state === self::STATE_AWAITING_PAYMENT;
    }
}
