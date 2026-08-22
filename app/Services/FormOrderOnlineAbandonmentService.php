<?php

namespace App\Services;

use App\Models\FormOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Nieopłacone zamówienia online (bramka) a slot e-mail uczestnika na kursie.
 *
 * Etap 1 odzyskiwania checkoutu: zwalnianie konfliktu e-mail oraz auto-anulowanie
 * przy przejściu klienta na fakturę odroczoną.
 */
class FormOrderOnlineAbandonmentService
{
    public function abandonmentMinutes(): int
    {
        return max(1, (int) config('order_form.online_abandonment_minutes', 60));
    }

    public function supersededCancelReason(): string
    {
        return (string) config(
            'order_form.online_superseded_cancel_reason',
            'zastąpione zamówieniem odroczonym'
        );
    }

    /**
     * Moment ostatniej aktywności płatności online (UTC) — do liczenia progu porzucenia.
     */
    public function onlineActivityReferenceAt(FormOrder $order): ?Carbon
    {
        $order->loadMissing('onlinePaymentOrders');

        $candidates = [];

        if ($order->order_date instanceof Carbon) {
            $candidates[] = $order->order_date->copy()->utc();
        } elseif (! empty($order->order_date)) {
            $candidates[] = Carbon::parse($order->order_date, 'UTC');
        }

        if ($order->created_at instanceof Carbon) {
            $candidates[] = $order->created_at->copy()->utc();
        }

        foreach ($order->onlinePaymentOrders as $attempt) {
            if ($attempt->created_at instanceof Carbon) {
                $candidates[] = $attempt->created_at->copy()->utc();
            }
        }

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->max();
    }

    public function isUnpaidOnlineGatewayOrder(FormOrder $order): bool
    {
        if ($order->payment_mode !== FormOrder::PAYMENT_MODE_ONLINE_GATEWAY) {
            return false;
        }

        if ($order->cancelled_at !== null) {
            return false;
        }

        if ($order->payment_status === FormOrder::PAYMENT_STATUS_PAID) {
            return false;
        }

        if (trim((string) ($order->invoice_number ?? '')) !== '') {
            return false;
        }

        if ($order->status_completed) {
            return false;
        }

        return true;
    }

    /**
     * Czy nieopłacone online uznajemy za porzucone (nie blokuje już e-maila na kursie).
     */
    public function isAbandonedUnpaidOnline(FormOrder $order, ?Carbon $now = null): bool
    {
        if (! $this->isUnpaidOnlineGatewayOrder($order)) {
            return false;
        }

        $status = $order->payment_status;

        if (in_array($status, [FormOrder::PAYMENT_STATUS_CANCELLED, FormOrder::PAYMENT_STATUS_FAILED], true)) {
            return true;
        }

        if ($status !== FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT) {
            return false;
        }

        $now = ($now ?? now('UTC'))->copy()->utc();
        $reference = $this->onlineActivityReferenceAt($order);

        if ($reference === null) {
            return false;
        }

        return $reference->lte($now->copy()->subMinutes($this->abandonmentMinutes()));
    }

    /**
     * Ogranicza zapytanie do zamówień form_orders, które nadal blokują e-mail uczestnika.
     */
    public function scopeOrdersBlockingParticipantEmail(
        Builder $query,
        ?int $exceptFormOrderId = null,
        ?Carbon $now = null
    ): Builder {
        $now = ($now ?? now('UTC'))->copy()->utc();
        $cutoff = $now->copy()->subMinutes($this->abandonmentMinutes())->format('Y-m-d H:i:s');

        $query->whereNull('cancelled_at');

        if ($exceptFormOrderId) {
            $query->where('id', '!=', $exceptFormOrderId);
        }

        return $query->where(function (Builder $block) use ($cutoff) {
            $block
                ->where(function (Builder $nonOnline) {
                    $nonOnline->whereNull('payment_mode')
                        ->orWhere('payment_mode', '!=', FormOrder::PAYMENT_MODE_ONLINE_GATEWAY);
                })
                ->orWhere('payment_status', FormOrder::PAYMENT_STATUS_PAID)
                ->orWhere(function (Builder $recentAwaiting) use ($cutoff) {
                    $recentAwaiting
                        ->where('payment_mode', FormOrder::PAYMENT_MODE_ONLINE_GATEWAY)
                        ->where('payment_status', FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT)
                        ->whereRaw($this->onlineActivityReferenceSql().' > ?', [$cutoff]);
                })
                ->orWhere(function (Builder $otherOnline) {
                    $otherOnline
                        ->where('payment_mode', FormOrder::PAYMENT_MODE_ONLINE_GATEWAY)
                        ->whereNotIn('payment_status', [
                            FormOrder::PAYMENT_STATUS_CANCELLED,
                            FormOrder::PAYMENT_STATUS_FAILED,
                            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
                            FormOrder::PAYMENT_STATUS_PAID,
                        ]);
                });
        });
    }

    /**
     * Anuluje nieopłacone zamówienia online dla tych samych uczestników przed złożeniem FV odroczonej.
     *
     * @param  list<string>  $participantEmails
     */
    public function cancelSupersededUnpaidOnlineOrders(
        int $courseId,
        array $participantEmails,
        ?int $exceptFormOrderId = null
    ): int {
        $emails = array_values(array_unique(array_filter(array_map(
            static fn ($email) => strtolower(trim((string) $email)),
            $participantEmails
        ))));

        if ($emails === []) {
            return 0;
        }

        $query = FormOrder::query()
            ->where('product_id', $courseId)
            ->whereNull('cancelled_at')
            ->whereNull('deleted_at')
            ->where('payment_mode', FormOrder::PAYMENT_MODE_ONLINE_GATEWAY)
            ->where('payment_status', '!=', FormOrder::PAYMENT_STATUS_PAID)
            ->where(function (Builder $invoice) {
                $invoice->whereNull('invoice_number')
                    ->orWhere('invoice_number', '')
                    ->orWhere('invoice_number', '0');
            })
            ->where(function (Builder $completed) {
                $completed->where('status_completed', '!=', 1)
                    ->orWhereNull('status_completed');
            })
            ->whereHas('participants', function (Builder $participantQuery) use ($emails) {
                $participantQuery->whereNull('deleted_at')
                    ->where(function (Builder $emailQuery) use ($emails) {
                        foreach ($emails as $email) {
                            $emailQuery->orWhereRaw('LOWER(TRIM(participant_email)) = ?', [$email]);
                        }
                    });
            });

        if ($exceptFormOrderId) {
            $query->where('id', '!=', $exceptFormOrderId);
        }

        $cancelledCount = 0;
        $reason = $this->supersededCancelReason();
        $cancelledAt = now('UTC');

        foreach ($query->get() as $order) {
            $order->cancelled_at = $cancelledAt;
            $order->cancelled_reason = $reason;
            $order->save();

            Log::info('Auto-cancelled unpaid online form order superseded by deferred checkout', [
                'form_order_id' => $order->id,
                'ident' => $order->ident,
                'course_id' => $courseId,
                'payment_status' => $order->payment_status,
            ]);

            $cancelledCount++;
        }

        return $cancelledCount;
    }

    private function onlineActivityReferenceSql(): string
    {
        return 'GREATEST(
            COALESCE(form_orders.order_date, form_orders.created_at),
            COALESCE(
                (SELECT MAX(opo.created_at) FROM online_payment_orders opo WHERE opo.form_order_id = form_orders.id),
                form_orders.order_date,
                form_orders.created_at
            )
        )';
    }
}
