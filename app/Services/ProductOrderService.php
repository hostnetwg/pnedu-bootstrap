<?php

namespace App\Services;

use App\Models\FormOrder;
use App\Models\OnlineCourseEnrollment;
use App\Models\OrderItem;
use App\Models\OrderItemRecipient;
use App\Models\Product;
use App\Models\ProductPrice;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductOrderService
{
    /**
     * @param  array<string, mixed>  $validated
     * @param  list<array{first_name: string, last_name: string, email: string}>  $participants
     */
    public function create(
        Product $product,
        ProductPrice $price,
        array $validated,
        array $participants,
        Request $request,
        array $legalEvidence
    ): FormOrder {
        return DB::connection('pneadm')->transaction(function () use (
            $product,
            $price,
            $validated,
            $participants,
            $request,
            $legalEvidence
        ) {
            $participantCount = count($participants);
            $unitCents = (int) round(((float) $price->currentPrice()) * 100);
            $lineTotal = number_format(($unitCents * $participantCount) / 100, 2, '.', '');
            $buyerType = $validated['buyer_type'];
            $buyerName = $buyerType === 'person'
                ? trim($validated['buyer_person_first_name'].' '.$validated['buyer_person_last_name'])
                : $validated['buyer_name'];
            $isDeferred = $validated['payment_type'] === 'deferred';
            $price->loadMissing('offer');
            $accessSnapshot = $this->accessSnapshot($product, $price, $participants);

            $order = FormOrder::query()->create([
                'ident' => FormOrder::generateIdent(),
                'order_date' => now('UTC'),
                'order_kind' => 'product',
                'product_id' => null,
                'product_name' => $product->name,
                'product_price' => $lineTotal,
                'product_description' => strip_tags((string) $product->onlineCourse?->description),
                'publigo_product_id' => null,
                'publigo_price_id' => null,
                'course_price_variant_id' => null,
                'publigo_sent' => 0,
                'orderer_name' => $validated['contact_name'],
                'orderer_address' => $validated['buyer_address'],
                'orderer_postal_code' => $validated['buyer_postcode'],
                'orderer_city' => $validated['buyer_city'],
                'orderer_phone' => $validated['contact_phone'],
                'orderer_email' => $validated['contact_email'],
                'buyer_name' => $buyerName,
                'buyer_address' => $validated['buyer_address'],
                'buyer_postal_code' => $validated['buyer_postcode'],
                'buyer_city' => $validated['buyer_city'],
                'buyer_nip' => $buyerType === 'organisation' ? ($validated['buyer_nip'] ?? null) : null,
                'recipient_name' => $buyerType === 'organisation' ? ($validated['recipient_name'] ?? null) : null,
                'recipient_address' => $buyerType === 'organisation' ? ($validated['recipient_address'] ?? null) : null,
                'recipient_postal_code' => $buyerType === 'organisation' ? ($validated['recipient_postcode'] ?? null) : null,
                'recipient_city' => $buyerType === 'organisation' ? ($validated['recipient_city'] ?? null) : null,
                'recipient_nip' => $buyerType === 'organisation' ? ($validated['recipient_nip'] ?? null) : null,
                'invoice_notes' => $validated['invoice_notes'] ?? null,
                'invoice_payment_delay' => $isDeferred ? (int) $validated['payment_terms'] : null,
                'ptw' => $isDeferred ? (int) $validated['payment_terms'] : null,
                'payment_mode' => $isDeferred
                    ? FormOrder::PAYMENT_MODE_DEFERRED_INVOICE
                    : FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
                'payment_status' => $isDeferred
                    ? FormOrder::PAYMENT_STATUS_SUBMITTED
                    : FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
                'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
                'order_form_variant' => 'product_v1',
                ...$legalEvidence,
                'status_completed' => 0,
                'ip_address' => $request->ip(),
            ]);

            app(OrderFormParticipantService::class)->sync($order, $participants);
            $formParticipants = $order->participants()->get()->keyBy(
                fn ($participant) => strtolower(trim((string) $participant->participant_email))
            );

            $item = OrderItem::query()->create([
                'form_order_id' => $order->id,
                'product_id' => $product->id,
                'product_offer_id' => $price->product_offer_id,
                'product_price_id' => $price->id,
                'product_type' => $product->type,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'fulfillment_type' => $product->fulfillment_type,
                'requires_shipping' => $product->requires_shipping,
                'unit_price' => $price->currentPrice(),
                'quantity' => $participantCount,
                'line_total' => $lineTotal,
                'currency' => $price->currency,
                'tax_treatment' => $price->tax_treatment,
                'tax_rate' => $price->tax_rate,
                'tax_exemption_basis' => $price->tax_exemption_basis,
                'access_policy' => $price->access_policy,
                'access_starts_at' => $price->access_starts_at,
                'access_note' => $price->access_note,
                'satisfaction_guarantee_days' => $price->offer?->satisfactionGuaranteeDays() ?: null,
                'access_duration_value' => $price->access_duration_value,
                'access_duration_unit' => $price->access_duration_unit,
                'access_expires_at' => $price->access_expires_at,
                'is_extension' => $accessSnapshot['is_extension'],
                'previous_access_expires_at' => $accessSnapshot['previous_access_expires_at'],
                'metadata' => [
                    'online_course_id' => $product->resource_id,
                    'offer_code' => $price->offer?->code,
                    'price_name' => $price->name,
                ],
                'commercial_terms' => $accessSnapshot['commercial_terms'],
            ]);

            foreach ($participants as $participant) {
                $email = strtolower(trim($participant['email']));
                OrderItemRecipient::query()->create([
                    'order_item_id' => $item->id,
                    'form_order_participant_id' => $formParticipants->get($email)?->id,
                    'first_name' => $participant['first_name'],
                    'last_name' => $participant['last_name'],
                    'email' => $email,
                    'status' => OrderItemRecipient::STATUS_PENDING,
                ]);
            }

            return $order->load(['orderItems.recipients', 'participants']);
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<array{first_name: string, last_name: string, email: string}>  $participants
     */
    public function update(
        FormOrder $order,
        Product $product,
        ProductPrice $price,
        array $validated,
        array $participants,
        array $legalEvidence,
        bool $refreshPayment = false
    ): FormOrder {
        return DB::connection('pneadm')->transaction(function () use (
            $order,
            $product,
            $price,
            $validated,
            $participants,
            $legalEvidence,
            $refreshPayment
        ) {
            $price->loadMissing('offer');
            $participantCount = count($participants);
            $unitCents = (int) round(((float) $price->currentPrice()) * 100);
            $lineTotal = number_format(($unitCents * $participantCount) / 100, 2, '.', '');
            $buyerType = $validated['buyer_type'];
            $buyerName = $buyerType === 'person'
                ? trim($validated['buyer_person_first_name'].' '.$validated['buyer_person_last_name'])
                : $validated['buyer_name'];
            $isDeferred = $refreshPayment
                ? ($validated['payment_type'] === 'deferred')
                : ($order->payment_mode === FormOrder::PAYMENT_MODE_DEFERRED_INVOICE);

            $order->fill([
                'product_name' => $product->name,
                'product_price' => $lineTotal,
                'product_description' => strip_tags((string) $product->onlineCourse?->description),
                'orderer_name' => $validated['contact_name'],
                'orderer_address' => $validated['buyer_address'],
                'orderer_postal_code' => $validated['buyer_postcode'],
                'orderer_city' => $validated['buyer_city'],
                'orderer_phone' => $validated['contact_phone'],
                'orderer_email' => $validated['contact_email'],
                'buyer_name' => $buyerName,
                'buyer_address' => $validated['buyer_address'],
                'buyer_postal_code' => $validated['buyer_postcode'],
                'buyer_city' => $validated['buyer_city'],
                'buyer_nip' => $buyerType === 'organisation' ? ($validated['buyer_nip'] ?? null) : null,
                'recipient_name' => $buyerType === 'organisation' ? ($validated['recipient_name'] ?? null) : null,
                'recipient_address' => $buyerType === 'organisation' ? ($validated['recipient_address'] ?? null) : null,
                'recipient_postal_code' => $buyerType === 'organisation' ? ($validated['recipient_postcode'] ?? null) : null,
                'recipient_city' => $buyerType === 'organisation' ? ($validated['recipient_city'] ?? null) : null,
                'recipient_nip' => $buyerType === 'organisation' ? ($validated['recipient_nip'] ?? null) : null,
                'invoice_notes' => $validated['invoice_notes'] ?? null,
                'invoice_payment_delay' => $isDeferred ? (int) ($validated['payment_terms'] ?? $order->invoice_payment_delay) : $order->invoice_payment_delay,
                'ptw' => $isDeferred ? (int) ($validated['payment_terms'] ?? $order->ptw) : $order->ptw,
                'updated_manually_at' => now('UTC'),
            ]);
            if ($refreshPayment) {
                $order->fill([
                    'invoice_payment_delay' => $isDeferred ? (int) ($validated['payment_terms'] ?? $order->invoice_payment_delay) : null,
                    'ptw' => $isDeferred ? (int) ($validated['payment_terms'] ?? $order->ptw) : null,
                    'payment_mode' => $isDeferred
                        ? FormOrder::PAYMENT_MODE_DEFERRED_INVOICE
                        : FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
                    'payment_status' => $isDeferred
                        ? FormOrder::PAYMENT_STATUS_SUBMITTED
                        : FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
                ]);
            }
            if (! filled($order->terms_version)) {
                $order->fill($legalEvidence);
            }
            $order->save();

            app(OrderFormParticipantService::class)->sync($order, $participants);
            $formParticipants = $order->participants()->get()->keyBy(
                fn ($participant) => strtolower(trim((string) $participant->participant_email))
            );

            $item = $order->orderItems()->firstOrFail();
            $item->update([
                'product_id' => $product->id,
                'product_offer_id' => $price->product_offer_id,
                'product_price_id' => $price->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_price' => $price->currentPrice(),
                'quantity' => $participantCount,
                'line_total' => $lineTotal,
                'currency' => $price->currency,
                'tax_treatment' => $price->tax_treatment,
                'tax_rate' => $price->tax_rate,
                'tax_exemption_basis' => $price->tax_exemption_basis,
                'access_policy' => $price->access_policy,
                'access_starts_at' => $price->access_starts_at,
                'access_note' => $price->access_note,
                'satisfaction_guarantee_days' => $price->offer?->satisfactionGuaranteeDays() ?: null,
                'access_duration_value' => $price->access_duration_value,
                'access_duration_unit' => $price->access_duration_unit,
                'access_expires_at' => $price->access_expires_at,
                'metadata' => array_merge($item->metadata ?? [], [
                    'online_course_id' => $product->resource_id,
                    'offer_code' => $price->offer?->code,
                    'price_name' => $price->name,
                ]),
            ]);

            $item->recipients()->delete();
            foreach ($participants as $participant) {
                $email = strtolower(trim($participant['email']));
                OrderItemRecipient::query()->create([
                    'order_item_id' => $item->id,
                    'form_order_participant_id' => $formParticipants->get($email)?->id,
                    'first_name' => $participant['first_name'],
                    'last_name' => $participant['last_name'],
                    'email' => $email,
                    'status' => OrderItemRecipient::STATUS_PENDING,
                ]);
            }

            return $order->fresh(['orderItems.recipients', 'participants']);
        });
    }

    /**
     * @param  list<array{first_name: string, last_name: string, email: string}>  $participants
     * @return array{is_extension: bool, previous_access_expires_at: CarbonImmutable|null, commercial_terms: array<string, mixed>}
     */
    private function accessSnapshot(Product $product, ProductPrice $price, array $participants): array
    {
        $courseId = (int) $product->resource_id;
        $isExtension = false;
        $previousExpiry = null;

        foreach ($participants as $participant) {
            $enrollment = OnlineCourseEnrollment::query()
                ->where('online_course_id', $courseId)
                ->where('email', strtolower(trim($participant['email'])))
                ->first();
            if (! $enrollment || ! $enrollment->hasAccessStarted() || $enrollment->hasExpiredAccess()) {
                continue;
            }
            if ($enrollment->access_expires_at === null) {
                continue;
            }
            $isExtension = true;
            $expiry = CarbonImmutable::instance($enrollment->access_expires_at)->utc();
            if ($previousExpiry === null || $expiry->lt($previousExpiry)) {
                $previousExpiry = $expiry;
            }
        }

        return [
            'is_extension' => $isExtension,
            'previous_access_expires_at' => $previousExpiry,
            'commercial_terms' => [
                'variant_name' => $price->name,
                'access_policy' => $price->access_policy,
                'access_label' => $price->accessLabel(),
                'access_starts_rule' => $price->access_starts_at
                    ? 'scheduled'
                    : 'from_grant_or_existing_end',
                'is_extension' => $isExtension,
                'previous_access_expires_at' => $previousExpiry?->toIso8601String(),
                'guarantee_days' => $price->offer?->satisfactionGuaranteeDays() ?: null,
                'guarantee_starts_rule' => 'actual_access_start_not_order_date',
                'technical_requirements' => 'Urządzenie z dostępem do Internetu, aktualna przeglądarka, aktywny adres e-mail i możliwość odtwarzania treści audio-wideo.',
            ],
        ];
    }
}
