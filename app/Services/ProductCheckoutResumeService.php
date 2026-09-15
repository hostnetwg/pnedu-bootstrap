<?php

namespace App\Services;

use App\Models\FormOrder;
use Illuminate\Support\Facades\Log;

/**
 * Idempotencja checkoutu kursu online: sesja + to samo nieopłacone zamówienie.
 *
 * Zapobiega duplikatom przy double-click i „wstecz + wyślij ponownie”.
 */
class ProductCheckoutResumeService
{
    public const SESSION_KEY = 'product_checkout_resume';

    public const DEDUP_WINDOW_MINUTES = 60;

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function clearResume(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function clearResumeForProduct(int $productId): void
    {
        $payload = $this->readSessionPayload();
        if ($payload !== null && (int) ($payload['product_id'] ?? 0) === $productId) {
            $this->clearResume();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readSessionPayload(): ?array
    {
        $payload = session(self::SESSION_KEY);

        return is_array($payload) ? $payload : null;
    }

    public function storeAfterSubmit(int $productId, FormOrder $order, string $participantEmail): void
    {
        session([
            self::SESSION_KEY => [
                'product_id' => $productId,
                'ident' => $order->ident,
                'participant_email' => $this->normalizeEmail($participantEmail),
            ],
        ]);
    }

    public function resumableIdentForForm(int $productId, ?string $oldIdent = null): ?string
    {
        $old = trim((string) $oldIdent);
        if ($old !== '') {
            $fromOld = $this->loadReusableOrder($old, $productId);
            if ($fromOld !== null) {
                return $fromOld->ident;
            }
        }

        return $this->findResumableOrderFromSession($productId)?->ident;
    }

    public function findReusable(int $productId, ?string $requestOrderIdent, string $participantEmail): ?FormOrder
    {
        $normalizedEmail = $this->normalizeEmail($participantEmail);
        if ($normalizedEmail === '') {
            return null;
        }

        foreach ($this->candidateIdents($productId, $requestOrderIdent, $normalizedEmail) as $ident) {
            $order = $this->loadReusableOrder($ident, $productId);
            if ($order === null) {
                continue;
            }

            if (! $this->emailMatchesOrder($order, $normalizedEmail)) {
                continue;
            }

            return $order;
        }

        return null;
    }

    public function canReuse(FormOrder $order): bool
    {
        return $this->isReusable($order);
    }

    /**
     * @return list<string>
     */
    protected function candidateIdents(int $productId, ?string $requestOrderIdent, string $normalizedEmail): array
    {
        $idents = [];
        $requestIdent = trim((string) $requestOrderIdent);
        if ($requestIdent !== '') {
            $idents[] = $requestIdent;
        }

        $payload = $this->readSessionPayload();
        if (
            is_array($payload)
            && (int) ($payload['product_id'] ?? 0) === $productId
            && trim((string) ($payload['ident'] ?? '')) !== ''
        ) {
            $sessionEmail = $this->normalizeEmail((string) ($payload['participant_email'] ?? ''));
            if ($sessionEmail === '' || $sessionEmail === $normalizedEmail) {
                $idents[] = trim((string) $payload['ident']);
            }
        }

        $recent = $this->findRecentDuplicateOrder($productId, $normalizedEmail);
        if ($recent !== null) {
            $idents[] = $recent->ident;
        }

        return array_values(array_unique($idents));
    }

    protected function findResumableOrderFromSession(int $productId): ?FormOrder
    {
        $payload = $this->readSessionPayload();
        if (! is_array($payload) || (int) ($payload['product_id'] ?? 0) !== $productId) {
            return null;
        }

        $ident = trim((string) ($payload['ident'] ?? ''));
        if ($ident === '') {
            return null;
        }

        $order = $this->loadReusableOrder($ident, $productId);
        if ($order === null) {
            $this->clearResumeForProduct($productId);

            return null;
        }

        return $order;
    }

    protected function loadReusableOrder(string $ident, int $productId): ?FormOrder
    {
        $order = FormOrder::withTrashed()
            ->with(['participants', 'orderItems.recipients.fulfillments'])
            ->where('ident', $ident)
            ->where('order_kind', 'product')
            ->whereHas('orderItems', fn ($query) => $query->where('product_id', $productId))
            ->first();

        if ($order === null || ! $this->isReusable($order)) {
            return null;
        }

        if ($order->trashed()) {
            $order->restore();
            Log::info('Product FormOrder restored after customer resubmitted checkout', [
                'ident' => $order->ident,
                'form_order_id' => $order->id,
            ]);
        }

        return $order;
    }

    protected function findRecentDuplicateOrder(int $productId, string $normalizedEmail): ?FormOrder
    {
        return FormOrder::query()
            ->with(['participants', 'orderItems.recipients.fulfillments'])
            ->where('order_kind', 'product')
            ->where('submission_source', FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM)
            ->where('order_date', '>=', now('UTC')->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->where('status_completed', 0)
            ->where(function ($query) {
                $query->whereNull('invoice_number')
                    ->orWhere('invoice_number', '')
                    ->orWhere('invoice_number', '0');
            })
            ->whereIn('payment_status', [
                FormOrder::PAYMENT_STATUS_SUBMITTED,
                FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
                FormOrder::PAYMENT_STATUS_FAILED,
            ])
            ->whereHas('orderItems', fn ($query) => $query->where('product_id', $productId))
            ->where(function ($query) use ($normalizedEmail) {
                $query->whereRaw('LOWER(TRIM(orderer_email)) = ?', [$normalizedEmail])
                    ->orWhereHas('participants', function ($participantQuery) use ($normalizedEmail) {
                        $participantQuery->whereNull('deleted_at')
                            ->whereRaw('LOWER(TRIM(participant_email)) = ?', [$normalizedEmail]);
                    });
            })
            ->orderByDesc('id')
            ->get()
            ->first(fn (FormOrder $order) => $this->isReusable($order));
    }

    protected function emailMatchesOrder(FormOrder $order, string $normalizedEmail): bool
    {
        if ($this->normalizeEmail((string) $order->orderer_email) === $normalizedEmail) {
            return true;
        }

        $order->loadMissing(['participants', 'orderItems.recipients']);
        foreach ($order->participants as $participant) {
            if ($this->normalizeEmail((string) $participant->participant_email) === $normalizedEmail) {
                return true;
            }
        }
        foreach ($order->orderItems as $item) {
            foreach ($item->recipients as $recipient) {
                if ($this->normalizeEmail((string) $recipient->email) === $normalizedEmail) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isReusable(FormOrder $order): bool
    {
        if ($order->order_kind !== 'product') {
            return false;
        }

        if ((int) $order->status_completed === 1) {
            return false;
        }

        if ($order->cancelled_at !== null) {
            return false;
        }

        if (trim((string) ($order->invoice_number ?? '')) !== '') {
            return false;
        }

        if ($order->payment_status === FormOrder::PAYMENT_STATUS_PAID) {
            return false;
        }

        if (! in_array($order->payment_status, [
            FormOrder::PAYMENT_STATUS_SUBMITTED,
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            FormOrder::PAYMENT_STATUS_FAILED,
        ], true)) {
            return false;
        }

        $order->loadMissing('orderItems.recipients.fulfillments');
        foreach ($order->orderItems as $item) {
            foreach ($item->recipients as $recipient) {
                foreach ($recipient->fulfillments ?? [] as $fulfillment) {
                    if (in_array($fulfillment->status, ['succeeded', 'processing'], true)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
