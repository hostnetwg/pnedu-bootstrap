<?php

namespace App\Services;

use App\Models\Course;
use App\Models\FormOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Konwersja nieopłaconego zamówienia online → faktura odroczona (to samo form_orders).
 */
class FormOrderOnlineToDeferredConversionService
{
    public function __construct(
        private readonly FormOrderOnlineAbandonmentService $abandonmentService,
        private readonly FormOrderOnlinePaymentRetryService $retryService,
    ) {}

    public function defaultPaymentTermsDays(): int
    {
        return max(0, min(30, (int) config('order_form.online_to_deferred_default_payment_terms', 14)));
    }

    public function maxPaymentTermsDays(): int
    {
        return max(0, (int) config('order_form.online_to_deferred_max_payment_terms', 30));
    }

    public function canConvert(FormOrder $formOrder): bool
    {
        if (! $this->abandonmentService->isUnpaidOnlineGatewayOrder($formOrder)) {
            return false;
        }

        // Opłacone nigdy (dodatkowa ochrona przy wyścigu z webhookiem).
        if ($formOrder->payment_status === FormOrder::PAYMENT_STATUS_PAID) {
            return false;
        }

        return true;
    }

    public function signedConfirmPageUrl(FormOrder $formOrder): string
    {
        return $this->retryService->signedConvertToDeferredUrl($formOrder);
    }

    public function signedConfirmSubmitUrl(FormOrder $formOrder): string
    {
        return URL::temporarySignedRoute(
            'orders.convert-to-deferred.confirm',
            now()->addDays($this->retryService->retrySignedUrlDays()),
            ['ident' => $formOrder->ident]
        );
    }

    /**
     * Link awaryjny: pełny formularz z prefillem (poprawa danych).
     */
    public function editDataUrl(FormOrder $formOrder): string
    {
        return $this->retryService->deferredOrderFormUrl($formOrder);
    }

    /**
     * @return array{success: bool, error?: string, code?: string, order?: FormOrder}
     */
    public function convert(FormOrder $formOrder, int $paymentTerms): array
    {
        $paymentTerms = max(0, min($this->maxPaymentTermsDays(), $paymentTerms));

        try {
            return DB::connection('pneadm')->transaction(function () use ($formOrder, $paymentTerms) {
                /** @var FormOrder $locked */
                $locked = FormOrder::query()
                    ->whereKey($formOrder->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return [
                        'success' => false,
                        'error' => 'Zamówienie nie zostało znalezione.',
                        'code' => 'not_found',
                    ];
                }

                $locked->refresh();

                if ($locked->payment_status === FormOrder::PAYMENT_STATUS_PAID) {
                    return [
                        'success' => false,
                        'error' => 'To zamówienie zostało już opłacone online. Konwersja na fakturę odroczoną nie jest możliwa.',
                        'code' => 'already_paid',
                    ];
                }

                if ($locked->payment_mode === FormOrder::PAYMENT_MODE_DEFERRED_INVOICE
                    && $locked->payment_status === FormOrder::PAYMENT_STATUS_SUBMITTED) {
                    return [
                        'success' => true,
                        'order' => $locked,
                        'code' => 'already_converted',
                    ];
                }

                if (! $this->canConvert($locked)) {
                    return [
                        'success' => false,
                        'error' => 'To zamówienie nie kwalifikuje się już do zmiany na fakturę odroczoną.',
                        'code' => 'not_eligible',
                    ];
                }

                $locked->payment_mode = FormOrder::PAYMENT_MODE_DEFERRED_INVOICE;
                $locked->payment_status = FormOrder::PAYMENT_STATUS_SUBMITTED;
                $locked->invoice_payment_delay = $paymentTerms;
                $locked->save();

                Log::info('Converted unpaid online form order to deferred invoice', [
                    'form_order_id' => $locked->id,
                    'ident' => $locked->ident,
                    'payment_terms' => $paymentTerms,
                ]);

                return [
                    'success' => true,
                    'order' => $locked,
                    'code' => 'converted',
                ];
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to convert online form order to deferred', [
                'form_order_id' => $formOrder->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Nie udało się zmienić sposobu płatności. Spróbuj ponownie lub skontaktuj się z nami.',
                'code' => 'exception',
            ];
        }
    }

    public function resolveCourse(FormOrder $formOrder): ?Course
    {
        $course = $formOrder->course ?? Course::query()->find($formOrder->product_id);

        if ($course) {
            $course->loadMissing('instructor');
        }

        return $course;
    }
}
