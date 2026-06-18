<?php

namespace App\Services;

use App\Models\FormOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Wznawianie checkoutu formularza zamówienia (sesja + idempotencja zapisu).
 *
 * Zapobiega duplikatom przy double-click i „wstecz + wyślij ponownie”, ale pozwala
 * złożyć kolejne zamówienie na ten sam kurs z innym uczestnikiem (inny e-mail).
 */
class FormOrderCheckoutResumeService
{
    public const SESSION_KEY = 'form_order_checkout_resume';

    /** @deprecated Odczyt tylko dla kompatybilności wstecznej. */
    public const LEGACY_ONLINE_SESSION_KEY = 'form_order_online_checkout_resume';

    public const DEDUP_WINDOW_MINUTES = 60;

    public function normalizeParticipantEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function clearResume(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->forget(self::LEGACY_ONLINE_SESSION_KEY);
    }

    public function clearResumeForCourse(int $courseId): void
    {
        $payload = $this->readSessionPayload();
        if ($payload !== null && (int) ($payload['course_id'] ?? 0) === $courseId) {
            $this->clearResume();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readSessionPayload(): ?array
    {
        $payload = session(self::SESSION_KEY);
        if (! is_array($payload)) {
            $payload = session(self::LEGACY_ONLINE_SESSION_KEY);
        }

        return is_array($payload) ? $payload : null;
    }

    public function storeAfterSubmit(int $courseId, FormOrder $order, string $participantEmail): void
    {
        session([
            self::SESSION_KEY => [
                'course_id' => $courseId,
                'ident' => $order->ident,
                'participant_email' => $this->normalizeParticipantEmail($participantEmail),
            ],
        ]);
        session()->forget(self::LEGACY_ONLINE_SESSION_KEY);
    }

    /**
     * Dołącza order_ident z sesji do danych formularza (GET), gdy brak trybu edycji z URL.
     *
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    public function mergeResumeIntoFormData(int $courseId, array $formData, bool $isEditMode): array
    {
        if ($isEditMode || ! empty($formData['order_ident'])) {
            return $formData;
        }

        $order = $this->findResumableOrderFromSession($courseId);
        if ($order === null) {
            return $formData;
        }

        $formData['order_ident'] = $order->ident;

        return $formData;
    }

    /**
     * Kontekst banera informacyjnego na formularzu (tylko gdy sesja wskazuje zamówienie do aktualizacji).
     *
     * @return array{
     *     ident: string,
     *     participant_email: string,
     *     edit_url: string,
     *     new_order_url: string,
     * }|null
     */
    public function resumeBannerContext(int $courseId, bool $isEditMode, string $editRouteName, string $formRouteName, array $formRouteParams = []): ?array
    {
        if ($isEditMode) {
            return null;
        }

        $order = $this->findResumableOrderFromSession($courseId);
        if ($order === null) {
            return null;
        }

        $participantEmail = $order->display_participant_email ?? '';
        $newOrderParams = array_merge($formRouteParams, [
            'id' => $courseId,
            'new_order' => 1,
        ]);

        return [
            'ident' => $order->ident,
            'participant_email' => $participantEmail,
            'edit_url' => route($editRouteName, ['id' => $courseId, 'ident' => $order->ident]),
            'new_order_url' => route($formRouteName, $newOrderParams),
        ];
    }

    /**
     * Rozstrzyga, czy POST ma zaktualizować istniejące zamówienie (z lockiem na email uczestnika + kurs).
     */
    public function resolveForSubmit(int $courseId, ?string $requestOrderIdent, string $participantEmail): ?FormOrder
    {
        $normalizedEmail = $this->normalizeParticipantEmail($participantEmail);
        if ($normalizedEmail === '') {
            return null;
        }

        $lockKey = sprintf('form_order_submit:%d:%s', $courseId, hash('sha256', $normalizedEmail));

        return Cache::lock($lockKey, 15)->block(10, function () use ($courseId, $requestOrderIdent, $normalizedEmail) {
            foreach ($this->candidateIdentsForSubmit($courseId, $requestOrderIdent, $normalizedEmail) as $ident) {
                $order = $this->loadOrderForUpdateCandidate($ident, $courseId);
                if ($order === null) {
                    continue;
                }

                if (! $this->participantEmailMatchesOrder($order, $normalizedEmail)) {
                    continue;
                }

                if (! $this->isOrderResumableForFormSubmit($order)) {
                    continue;
                }

                $this->restoreTrashedOrderIfNeeded($order);

                return $order;
            }

            return null;
        });
    }

    /**
     * @return list<string>
     */
    protected function candidateIdentsForSubmit(int $courseId, ?string $requestOrderIdent, string $normalizedEmail): array
    {
        $idents = [];

        $requestIdent = trim((string) $requestOrderIdent);
        if ($requestIdent !== '') {
            $idents[] = $requestIdent;
        }

        $payload = $this->readSessionPayload();
        if (
            is_array($payload)
            && (int) ($payload['course_id'] ?? 0) === $courseId
            && trim((string) ($payload['ident'] ?? '')) !== ''
        ) {
            $sessionIdent = trim((string) $payload['ident']);
            $sessionParticipantEmail = $this->normalizeParticipantEmail((string) ($payload['participant_email'] ?? ''));
            if ($sessionParticipantEmail === '' || $sessionParticipantEmail === $normalizedEmail) {
                $idents[] = $sessionIdent;
            }
        }

        $recent = $this->findRecentDuplicateOrder($courseId, $normalizedEmail);
        if ($recent !== null) {
            $idents[] = $recent->ident;
        }

        return array_values(array_unique($idents));
    }

    protected function findResumableOrderFromSession(int $courseId): ?FormOrder
    {
        $payload = $this->readSessionPayload();
        if (! is_array($payload) || (int) ($payload['course_id'] ?? 0) !== $courseId) {
            return null;
        }

        $ident = trim((string) ($payload['ident'] ?? ''));
        if ($ident === '') {
            return null;
        }

        $order = $this->loadOrderForUpdateCandidate($ident, $courseId);
        if ($order === null || ! $this->isOrderResumableForFormSubmit($order)) {
            $this->clearResumeForCourse($courseId);

            return null;
        }

        return $order;
    }

    protected function loadOrderForUpdateCandidate(string $ident, int $courseId): ?FormOrder
    {
        return FormOrder::withTrashed()
            ->with('primaryParticipant')
            ->where('ident', $ident)
            ->where('product_id', $courseId)
            ->first();
    }

    protected function findRecentDuplicateOrder(int $courseId, string $normalizedEmail): ?FormOrder
    {
        return FormOrder::query()
            ->with('primaryParticipant')
            ->where('product_id', $courseId)
            ->where('submission_source', FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM)
            ->where('order_date', '>=', now('UTC')->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->where('status_completed', 0)
            ->where(function ($query) {
                $query->whereNull('invoice_number')
                    ->orWhere('invoice_number', '')
                    ->orWhere('invoice_number', '0');
            })
            ->whereHas('primaryParticipant', function ($query) use ($normalizedEmail) {
                $query->whereNull('deleted_at')
                    ->where('is_primary', true)
                    ->whereRaw('LOWER(TRIM(participant_email)) = ?', [$normalizedEmail]);
            })
            ->orderByDesc('id')
            ->first();
    }

    protected function participantEmailMatchesOrder(FormOrder $order, string $normalizedEmail): bool
    {
        $order->loadMissing('primaryParticipant');
        $stored = $order->display_participant_email;
        if ($stored === null || trim($stored) === '') {
            return true;
        }

        return $this->normalizeParticipantEmail($stored) === $normalizedEmail;
    }

    protected function isOrderResumableForFormSubmit(FormOrder $order): bool
    {
        if ($order->isEditLocked()) {
            return false;
        }

        if ($order->payment_mode === FormOrder::PAYMENT_MODE_ONLINE_GATEWAY) {
            if ($order->payment_status === FormOrder::PAYMENT_STATUS_PAID) {
                return false;
            }

            if (! in_array($order->payment_status, [
                FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
                FormOrder::PAYMENT_STATUS_FAILED,
                FormOrder::PAYMENT_STATUS_CANCELLED,
            ], true)) {
                return false;
            }
        }

        return true;
    }

    protected function restoreTrashedOrderIfNeeded(FormOrder $order): void
    {
        if (! $order->trashed()) {
            return;
        }

        if ($order->isEditLocked()) {
            return;
        }

        $order->restore();
        Log::info('FormOrder restored after customer resubmitted order form', [
            'ident' => $order->ident,
            'form_order_id' => $order->id,
        ]);
    }
}
