<?php

namespace App\Services;

use App\Mail\OnlinePaymentRecoveryMail;
use App\Models\Course;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * E-mail recovery porzuconej / nieudanej płatności online (Etap 3).
 */
class FormOrderOnlinePaymentRecoveryService
{
    public function __construct(
        private readonly FormOrderOnlineAbandonmentService $abandonmentService,
        private readonly FormOrderOnlinePaymentRetryService $retryService,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('order_form.online_recovery_enabled', true);
    }

    public function eligibleForAutomaticRecovery(FormOrder $order, ?Carbon $now = null): bool
    {
        if ($order->online_payment_recovery_sent_at !== null) {
            return false;
        }

        return $this->abandonmentService->isAbandonedUnpaidOnline($order, $now)
            && $this->retryService->canRetryPayment($order);
    }

    public function eligibleForManualRecovery(FormOrder $order): bool
    {
        return $this->retryService->canRetryPayment($order);
    }

    /**
     * @return array{success: bool, error?: string, code?: string, emails?: list<string>, sent_at?: string}
     */
    public function sendRecoveryEmail(FormOrder $order, bool $allowResend = false): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Recovery e-mail jest wyłączony w konfiguracji.',
                'code' => 'disabled',
            ];
        }

        if (! $allowResend && $order->online_payment_recovery_sent_at !== null) {
            return [
                'success' => false,
                'error' => 'Recovery e-mail został już wysłany dla tego zamówienia.',
                'code' => 'already_sent',
            ];
        }

        if (! $this->eligibleForManualRecovery($order)) {
            return [
                'success' => false,
                'error' => 'Zamówienie nie kwalifikuje się do wysyłki recovery e-mail.',
                'code' => 'not_eligible',
            ];
        }

        $course = Course::find($order->product_id);
        if (! $course) {
            return [
                'success' => false,
                'error' => 'Nie znaleziono kursu powiązanego z zamówieniem.',
                'code' => 'course_missing',
            ];
        }

        $onlinePaymentOrder = OnlinePaymentOrder::query()
            ->where('form_order_id', $order->id)
            ->orderByDesc('id')
            ->first();

        if (! $onlinePaymentOrder) {
            return [
                'success' => false,
                'error' => 'Brak powiązanej próby płatności online.',
                'code' => 'online_payment_missing',
            ];
        }

        $emailsToSend = $this->collectRecipientEmails($order);
        if ($emailsToSend === []) {
            return [
                'success' => false,
                'error' => 'Brak adresów e-mail odbiorców.',
                'code' => 'no_recipients',
            ];
        }

        $retryUrl = $this->retryService->signedRetryUrl($order);
        $deferredUrl = $this->retryService->deferredOrderFormUrl($order);
        $pendingUrl = route('payment.pending', $onlinePaymentOrder->ident);

        $sentCount = 0;
        foreach ($emailsToSend as $email) {
            try {
                Mail::to($email)->send(new OnlinePaymentRecoveryMail(
                    $order,
                    $course,
                    $onlinePaymentOrder,
                    $retryUrl,
                    $deferredUrl,
                    $pendingUrl
                ));
                $sentCount++;
            } catch (\Throwable $exception) {
                Log::error('Błąd wysyłki recovery e-mail płatności online: '.$exception->getMessage(), [
                    'form_order_id' => $order->id,
                    'form_order_ident' => $order->ident,
                    'email' => $email,
                ]);
            }
        }

        if ($sentCount === 0) {
            return [
                'success' => false,
                'error' => 'Nie udało się wysłać recovery e-mail na żaden adres.',
                'code' => 'send_failed',
            ];
        }

        $sentAt = now('UTC');
        $order->online_payment_recovery_sent_at = $sentAt;
        $order->save();

        Log::info('Online payment recovery email sent', [
            'form_order_id' => $order->id,
            'form_order_ident' => $order->ident,
            'emails' => $emailsToSend,
            'allow_resend' => $allowResend,
        ]);

        return [
            'success' => true,
            'emails' => $emailsToSend,
            'sent_at' => $sentAt->toIso8601String(),
        ];
    }

    public function sendDueAutomaticRecoveries(?Carbon $now = null): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $now = ($now ?? now('UTC'))->copy()->utc();
        $sentCount = 0;

        $this->recoveryCandidateQuery()
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($now, &$sentCount) {
                foreach ($orders as $order) {
                    if (! $this->eligibleForAutomaticRecovery($order, $now)) {
                        continue;
                    }

                    $result = $this->sendRecoveryEmail($order, allowResend: false);
                    if ($result['success'] ?? false) {
                        $sentCount++;
                    }
                }
            });

        return $sentCount;
    }

    public function countAutomaticRecoveryCandidates(?Carbon $now = null): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $now = ($now ?? now('UTC'))->copy()->utc();
        $count = 0;

        $this->recoveryCandidateQuery()
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($now, &$count) {
                foreach ($orders as $order) {
                    if ($this->eligibleForAutomaticRecovery($order, $now)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * @return list<string>
     */
    private function collectRecipientEmails(FormOrder $order): array
    {
        $emailsToSend = [];

        $ordererEmail = strtolower(trim((string) ($order->orderer_email ?? '')));
        if ($ordererEmail !== '') {
            $emailsToSend[] = $ordererEmail;
        }

        $order->loadMissing(['participants' => fn ($query) => $query->orderBy('id')]);

        foreach ($order->participants as $participant) {
            $participantEmail = strtolower(trim((string) ($participant->participant_email ?? '')));
            if ($participantEmail === '' || in_array($participantEmail, $emailsToSend, true)) {
                continue;
            }
            $emailsToSend[] = $participantEmail;
        }

        return $emailsToSend;
    }

    private function recoveryCandidateQuery(): Builder
    {
        return FormOrder::query()
            ->whereNull('cancelled_at')
            ->whereNull('deleted_at')
            ->where('payment_mode', FormOrder::PAYMENT_MODE_ONLINE_GATEWAY)
            ->where('payment_status', '!=', FormOrder::PAYMENT_STATUS_PAID)
            ->whereNull('online_payment_recovery_sent_at')
            ->where(function (Builder $invoice) {
                $invoice->whereNull('invoice_number')
                    ->orWhere('invoice_number', '')
                    ->orWhere('invoice_number', '0');
            })
            ->where(function (Builder $completed) {
                $completed->where('status_completed', '!=', 1)
                    ->orWhereNull('status_completed');
            });
    }
}
