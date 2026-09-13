<?php

namespace App\Services;

use App\Models\FormOrder;
use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderItemRecipient;
use App\Support\PendingProductCourseAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PendingProductCourseAccessService
{
    public const RESIGN_REASON = 'Rezygnacja klienta z nieopłaconego zamówienia';

    public function __construct(
        private readonly FormOrderOnlineAbandonmentService $abandonmentService,
        private readonly FormOrderOnlinePaymentRetryService $retryService,
    ) {}

    /**
     * @return Collection<int, PendingProductCourseAccess>
     */
    public function listForEmail(?string $email): Collection
    {
        $normalized = OnlineCourseEnrollment::normalizeEmail($email);
        if ($normalized === null) {
            return collect();
        }

        $recipients = OrderItemRecipient::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalized])
            ->where('status', '!=', OrderItemRecipient::STATUS_CANCELLED)
            ->whereHas('item.formOrder', function ($query) {
                $query->where('order_kind', 'product')
                    ->whereNull('cancelled_at');
            })
            ->whereDoesntHave('fulfillments', function ($query) {
                $query->where('status', OrderFulfillment::STATUS_SUCCEEDED);
            })
            ->with([
                'item.product',
                'item.formOrder.onlinePaymentOrders' => fn ($query) => $query->orderByDesc('id'),
            ])
            ->orderByDesc('id')
            ->get();

        if ($recipients->isEmpty()) {
            return collect();
        }

        $courseIds = $recipients
            ->map(fn (OrderItemRecipient $recipient) => $this->resolveCourseId($recipient->item))
            ->filter()
            ->unique()
            ->values();

        $courses = $courseIds->isEmpty()
            ? collect()
            : OnlineCourse::query()->with('instructor')->whereIn('id', $courseIds->all())->get()->keyBy('id');

        $enrolledCourseIds = $courseIds->isEmpty()
            ? []
            : OnlineCourseEnrollment::query()
                ->where('email', $normalized)
                ->whereIn('online_course_id', $courseIds->all())
                ->pluck('online_course_id')
                ->map(fn ($id) => (int) $id)
                ->all();

        return $recipients
            ->map(function (OrderItemRecipient $recipient) use ($courses, $enrolledCourseIds) {
                $order = $recipient->item?->formOrder;
                $courseId = $this->resolveCourseId($recipient->item);
                $course = $courseId ? $courses->get($courseId) : null;

                if (! $order instanceof FormOrder || ! $course instanceof OnlineCourse) {
                    return null;
                }

                if ($order->cancelled_at !== null || ! $order->isProductOrder()) {
                    return null;
                }

                if (in_array((int) $course->id, $enrolledCourseIds, true)) {
                    return null;
                }

                $awaitingPayment = $this->abandonmentService->isUnpaidOnlineGatewayOrder($order);

                return new PendingProductCourseAccess(
                    order: $order,
                    recipient: $recipient,
                    course: $course,
                    state: $awaitingPayment
                        ? PendingProductCourseAccess::STATE_AWAITING_PAYMENT
                        : PendingProductCourseAccess::STATE_AWAITING_ACCESS,
                    paymentUrl: $awaitingPayment ? $this->paymentUrl($order) : null,
                    canResign: $awaitingPayment,
                );
            })
            ->filter()
            ->sortBy(fn (PendingProductCourseAccess $item) => [
                $item->isAwaitingPayment() ? 0 : 1,
                -$item->order->id,
            ])
            ->values();
    }

    public function countForEmail(?string $email): int
    {
        return $this->listForEmail($email)->count();
    }

    /**
     * @return array{success: bool, error?: string}
     */
    public function resignUnpaidOrder(FormOrder $order, ?string $userEmail): array
    {
        $normalized = OnlineCourseEnrollment::normalizeEmail($userEmail);
        if ($normalized === null) {
            return ['success' => false, 'error' => 'Nie można potwierdzić adresu e-mail konta.'];
        }

        $recipient = $this->activeResignableRecipient($order, $normalized);
        if (! $recipient) {
            return ['success' => false, 'error' => 'Tego zamówienia nie można już anulować z konta uczestnika.'];
        }

        $recipient->update(['status' => OrderItemRecipient::STATUS_CANCELLED]);
        if ($recipient->form_order_participant_id) {
            $order->participants()
                ->whereKey($recipient->form_order_participant_id)
                ->delete();
        }

        $remaining = $order->orderItems()
            ->whereHas('recipients', function ($query) {
                $query->where('status', '!=', OrderItemRecipient::STATUS_CANCELLED)
                    ->whereRaw("TRIM(email) != ''");
            })
            ->exists();

        if (! $remaining) {
            $order->cancelled_at = now('UTC');
            $order->cancelled_reason = self::RESIGN_REASON;
            if ($order->payment_status === FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT) {
                $order->payment_status = FormOrder::PAYMENT_STATUS_CANCELLED;
            }
            if (trim((string) ($order->invoice_number ?? '')) === '') {
                $order->status_completed = 1;
            }
            $order->save();
        }

        Log::info('Customer resigned from unpaid product order recipient', [
            'form_order_id' => $order->id,
            'ident' => $order->ident,
            'order_item_recipient_id' => $recipient->id,
            'email' => $normalized,
            'order_cancelled' => ! $remaining,
        ]);

        return ['success' => true];
    }

    public function canUserResign(FormOrder $order, string $normalizedEmail): bool
    {
        return $this->activeResignableRecipient($order, $normalizedEmail) !== null;
    }

    private function activeResignableRecipient(FormOrder $order, string $normalizedEmail): ?OrderItemRecipient
    {
        if (! $order->isProductOrder() || ! $this->abandonmentService->isUnpaidOnlineGatewayOrder($order)) {
            return null;
        }

        $recipient = OrderItemRecipient::query()
            ->whereHas('item', fn ($query) => $query->where('form_order_id', $order->id))
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->where('status', '!=', OrderItemRecipient::STATUS_CANCELLED)
            ->first();

        if (! $recipient) {
            return null;
        }

        $hasSucceededFulfillment = $recipient->fulfillments()
            ->where('status', OrderFulfillment::STATUS_SUCCEEDED)
            ->exists();

        return $hasSucceededFulfillment ? null : $recipient;
    }

    private function paymentUrl(FormOrder $order): string
    {
        $latestAttempt = $order->onlinePaymentOrders
            ->sortByDesc('id')
            ->first()
            ?? $order->onlinePaymentOrders()->orderByDesc('id')->first();
        if ($latestAttempt) {
            return route('payment.pending', $latestAttempt->ident);
        }

        if ($this->retryService->canRetryPayment($order)) {
            return $this->retryService->signedRetryUrl($order);
        }

        return route('online-courses.checkout.summary', $order->ident);
    }

    private function resolveCourseId(?OrderItem $item): ?int
    {
        if (! $item) {
            return null;
        }

        $fromMeta = (int) ($item->metadata['online_course_id'] ?? 0);
        if ($fromMeta > 0) {
            return $fromMeta;
        }

        $fromProduct = (int) ($item->product?->resource_id ?? 0);

        return $fromProduct > 0 ? $fromProduct : null;
    }
}
