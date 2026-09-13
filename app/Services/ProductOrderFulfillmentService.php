<?php

namespace App\Services;

use App\Models\FormOrder;
use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderItemRecipient;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OnlineCourseProductAccessGranted;
use App\Support\DashboardResourceCounts;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class ProductOrderFulfillmentService
{
    /**
     * @return array{success: bool, fulfilled: int, skipped: int, failed: int, message: string}
     */
    public function fulfillOrder(int $formOrderId, string $source = 'manual', ?int $recipientId = null): array
    {
        $order = FormOrder::query()
            ->whereKey($formOrderId)
            ->where('order_kind', 'product')
            ->with('orderItems.recipients')
            ->first();

        if (! $order) {
            return $this->result(false, 0, 0, 1, 'Nie znaleziono zamówienia produktowego.');
        }

        if ($source === 'online_payment' && $order->payment_status !== FormOrder::PAYMENT_STATUS_PAID) {
            return $this->result(false, 0, 0, 1, 'Płatność nie jest potwierdzona.');
        }

        $fulfilled = 0;
        $skipped = 0;
        $failed = 0;
        $matchedRecipient = false;

        foreach ($order->orderItems as $item) {
            foreach ($item->recipients as $recipient) {
                if ($recipientId !== null && (int) $recipient->id !== $recipientId) {
                    continue;
                }
                $matchedRecipient = true;
                if ($recipient->isCancelled()) {
                    $skipped++;

                    continue;
                }
                $result = $this->fulfillRecipient($item, $recipient, $source);
                match ($result) {
                    'fulfilled' => $fulfilled++,
                    'skipped' => $skipped++,
                    default => $failed++,
                };
            }
        }

        if ($recipientId !== null && ! $matchedRecipient) {
            return $this->result(false, 0, 0, 1, 'Nie znaleziono wskazanego uczestnika w tym zamówieniu.');
        }

        $this->syncOrderProvisionedAt($order->fresh(['orderItems.recipients.fulfillments']) ?? $order);

        return $this->result(
            $failed === 0,
            $fulfilled,
            $skipped,
            $failed,
            $failed === 0
                ? "Nadano dostęp: {$fulfilled}; wcześniej obsłużone: {$skipped}."
                : "Nadano dostęp: {$fulfilled}; pominięto: {$skipped}; błędy: {$failed}."
        );
    }

    /**
     * @return array{success: bool, revoked: int, skipped: int, failed: int, message: string}
     */
    public function revokeOrder(int $formOrderId, ?int $recipientId = null): array
    {
        $order = FormOrder::query()
            ->whereKey($formOrderId)
            ->where('order_kind', 'product')
            ->with('orderItems.recipients.fulfillments')
            ->first();

        if (! $order) {
            return [
                'success' => false,
                'revoked' => 0,
                'skipped' => 0,
                'failed' => 1,
                'message' => 'Nie znaleziono zamówienia produktowego.',
            ];
        }

        $recipients = $order->orderItems
            ->flatMap(fn (OrderItem $item) => $item->recipients)
            ->when($recipientId, fn ($collection) => $collection->where('id', $recipientId)->values())
            ->values();

        if ($recipientId !== null && $recipients->isEmpty()) {
            return [
                'success' => false,
                'revoked' => 0,
                'skipped' => 0,
                'failed' => 1,
                'message' => 'Nie znaleziono wskazanego uczestnika w tym zamówieniu.',
            ];
        }

        $revoked = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $outcome = $this->revokeRecipient($recipient);
            match ($outcome) {
                'revoked' => $revoked++,
                'skipped' => $skipped++,
                default => $failed++,
            };
        }

        $this->syncOrderProvisionedAt($order->fresh(['orderItems.recipients.fulfillments']) ?? $order);

        return [
            'success' => $failed === 0 && ($revoked > 0 || $skipped > 0),
            'revoked' => $revoked,
            'skipped' => $skipped,
            'failed' => $failed,
            'message' => $failed === 0
                ? "Wycofano dostęp: {$revoked}; bez nadanego dostępu: {$skipped}."
                : "Wycofano dostęp: {$revoked}; pominięto: {$skipped}; błędy: {$failed}.",
        ];
    }

    private function revokeRecipient(OrderItemRecipient $recipient): string
    {
        try {
            return DB::connection('pneadm')->transaction(function () use ($recipient) {
                $locked = OrderItemRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
                $fulfillment = OrderFulfillment::query()
                    ->where('order_item_recipient_id', $locked->id)
                    ->where('type', Product::FULFILLMENT_ONLINE_COURSE_ACCESS)
                    ->lockForUpdate()
                    ->first();

                if (! $fulfillment || $fulfillment->status !== OrderFulfillment::STATUS_SUCCEEDED) {
                    $locked->update(['status' => OrderItemRecipient::STATUS_PENDING]);

                    return 'skipped';
                }

                $enrollmentId = $fulfillment->online_course_enrollment_id
                    ? (int) $fulfillment->online_course_enrollment_id
                    : null;

                $fulfillment->update([
                    'status' => OrderFulfillment::STATUS_PENDING,
                    'granted_at' => null,
                    'notified_at' => null,
                    'failed_at' => null,
                    'error_message' => null,
                    'payload' => array_merge($fulfillment->payload ?? [], [
                        'revoked_at' => now('UTC')->toIso8601String(),
                    ]),
                ]);
                $locked->update(['status' => OrderItemRecipient::STATUS_PENDING]);

                if ($enrollmentId) {
                    $this->restoreEnrollmentAfterRevoke($enrollmentId, (int) $fulfillment->id);
                }

                return 'revoked';
            });
        } catch (Throwable) {
            return 'failed';
        }
    }

    private function fulfillRecipient(OrderItem $item, OrderItemRecipient $recipient, string $source): string
    {
        if (
            $item->product_type !== Product::TYPE_ONLINE_COURSE
            || $item->fulfillment_type !== Product::FULFILLMENT_ONLINE_COURSE_ACCESS
        ) {
            return $this->markUnsupported($item, $recipient);
        }

        $fulfillment = DB::connection('pneadm')->transaction(function () use ($recipient, $source) {
            $lockedRecipient = OrderItemRecipient::query()->lockForUpdate()->findOrFail($recipient->id);
            $record = OrderFulfillment::query()->firstOrCreate(
                [
                    'order_item_recipient_id' => $lockedRecipient->id,
                    'type' => Product::FULFILLMENT_ONLINE_COURSE_ACCESS,
                ],
                [
                    'idempotency_key' => "order-item-recipient:{$lockedRecipient->id}:online-course-access",
                    'status' => OrderFulfillment::STATUS_PENDING,
                ]
            );
            $record = OrderFulfillment::query()->lockForUpdate()->findOrFail($record->id);

            if ($record->status === OrderFulfillment::STATUS_SUCCEEDED) {
                return null;
            }

            if (
                $record->status === OrderFulfillment::STATUS_PROCESSING
                && $record->updated_at?->isAfter(now()->subMinutes(5))
            ) {
                return null;
            }

            $record->update([
                'status' => OrderFulfillment::STATUS_PROCESSING,
                'attempts' => $record->attempts + 1,
                'failed_at' => null,
                'error_message' => null,
                'payload' => array_merge($record->payload ?? [], ['source' => $source]),
            ]);

            return $record;
        });

        if (! $fulfillment) {
            return 'skipped';
        }

        try {
            $courseId = (int) ($item->metadata['online_course_id'] ?? 0);
            $course = OnlineCourse::withTrashed()->findOrFail($courseId);
            [$user, $userExisted] = $this->findOrCreateUser($recipient);
            $this->grantEnrollmentAndCompleteFulfillment(
                $course,
                $item,
                $recipient,
                $fulfillment,
                $user,
                $userExisted
            );
            DashboardResourceCounts::forgetForUser($user);
            $fulfillment->refresh();
            $this->notifyRecipient($fulfillment, $user, $course->title, $userExisted);

            return 'fulfilled';
        } catch (Throwable $exception) {
            Log::error('Product order fulfillment failed', [
                'order_item_recipient_id' => $recipient->id,
                'error' => $exception->getMessage(),
            ]);
            $fulfillment->update([
                'status' => OrderFulfillment::STATUS_FAILED,
                'failed_at' => now('UTC'),
                'error_message' => Str::limit($exception->getMessage(), 2000, ''),
            ]);
            $recipient->update(['status' => OrderItemRecipient::STATUS_FAILED]);

            return 'failed';
        }
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function findOrCreateUser(OrderItemRecipient $recipient): array
    {
        $email = strtolower(trim($recipient->email));

        return DB::transaction(function () use ($recipient, $email) {
            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            if ($user) {
                return [$user, true];
            }

            $user = new User;
            $user->forceFill([
                'first_name' => $recipient->first_name,
                'last_name' => $recipient->last_name,
                'email' => $email,
                'password' => Hash::make(Str::password(48)),
                'email_verified_at' => now('UTC'),
            ]);
            $user->save();

            return [$user, false];
        });
    }

    private function grantEnrollmentAndCompleteFulfillment(
        OnlineCourse $course,
        OrderItem $item,
        OrderItemRecipient $recipient,
        OrderFulfillment $fulfillment,
        User $user,
        bool $userExisted
    ): void {
        DB::connection('pneadm')->transaction(function () use (
            $course,
            $item,
            $recipient,
            $fulfillment,
            $user,
            $userExisted
        ) {
            $lockedFulfillment = OrderFulfillment::query()->lockForUpdate()->findOrFail($fulfillment->id);
            if ($lockedFulfillment->status === OrderFulfillment::STATUS_SUCCEEDED) {
                return;
            }

            $email = strtolower(trim($recipient->email));
            $enrollment = OnlineCourseEnrollment::query()
                ->where('online_course_id', $course->id)
                ->where('email', $email)
                ->lockForUpdate()
                ->first();
            $previousExpiry = $enrollment?->access_expires_at
                ? CarbonImmutable::instance($enrollment->access_expires_at)->utc()
                : null;
            $newExpiry = app(ProductAccessExpiryService::class)
                ->resolve($item, $enrollment !== null, $previousExpiry);

            if (! $enrollment) {
                $enrollment = new OnlineCourseEnrollment([
                    'online_course_id' => $course->id,
                    'email' => $email,
                ]);
            }
            $enrollment->first_name = $recipient->first_name;
            $enrollment->last_name = $recipient->last_name;
            $enrollment->access_expires_at = $newExpiry;
            $enrollment->access_starts_at = $this->resolveEnrollmentStartAt($item, $enrollment);
            if (filled($item->access_note)) {
                $enrollment->access_note = $item->access_note;
            }
            $enrollment->access_source = 'product_order';
            $enrollment->save();

            $lockedFulfillment->update([
                'status' => OrderFulfillment::STATUS_SUCCEEDED,
                'pnedu_user_id' => $user->id,
                'online_course_enrollment_id' => $enrollment->id,
                'granted_at' => now('UTC'),
                'previous_access_expires_at' => $previousExpiry,
                'new_access_expires_at' => $newExpiry,
                'failed_at' => null,
                'error_message' => null,
                'payload' => array_merge($lockedFulfillment->payload ?? [], [
                    'user_existed' => $userExisted,
                    'online_course_id' => $course->id,
                ]),
            ]);
            OrderItemRecipient::query()
                ->whereKey($recipient->id)
                ->update(['status' => OrderItemRecipient::STATUS_FULFILLED]);
        });
    }

    private function notifyRecipient(
        OrderFulfillment $fulfillment,
        User $user,
        string $courseTitle,
        bool $userExisted
    ): void {
        if ($fulfillment->notified_at) {
            return;
        }

        try {
            $token = $userExisted ? null : Password::createToken($user);
            $order = $fulfillment->recipient?->item?->formOrder
                ?? $fulfillment->loadMissing('recipient.item.formOrder')->recipient?->item?->formOrder;
            $user->notify(new OnlineCourseProductAccessGranted(
                $courseTitle,
                $token,
                $order,
                $user->first_name,
                $user->last_name,
                $user->email,
            ));
            $fulfillment->update(['notified_at' => now('UTC')]);
        } catch (Throwable $exception) {
            Log::error('Product fulfillment notification failed', [
                'order_fulfillment_id' => $fulfillment->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveEnrollmentStartAt(OrderItem $item, OnlineCourseEnrollment $enrollment): ?CarbonImmutable
    {
        $now = CarbonImmutable::now('UTC');
        $scheduled = $item->access_starts_at
            ? CarbonImmutable::instance($item->access_starts_at)->utc()
            : null;
        if ($scheduled && $scheduled->lte($now)) {
            $scheduled = null;
        }

        $existing = $enrollment->exists && $enrollment->access_starts_at
            ? CarbonImmutable::instance($enrollment->access_starts_at)->utc()
            : null;
        if ($existing && $existing->lte($now)) {
            return null;
        }

        if ($existing) {
            return $existing;
        }

        return $scheduled;
    }

    private function restoreEnrollmentAfterRevoke(int $enrollmentId, int $revokedFulfillmentId): void
    {
        $remaining = OrderFulfillment::query()
            ->where('online_course_enrollment_id', $enrollmentId)
            ->where('status', OrderFulfillment::STATUS_SUCCEEDED)
            ->where('id', '!=', $revokedFulfillmentId)
            ->get();

        if ($remaining->isEmpty()) {
            OnlineCourseEnrollment::query()->whereKey($enrollmentId)->delete();

            return;
        }

        $hasUnlimited = $remaining->contains(fn (OrderFulfillment $row) => $row->new_access_expires_at === null);
        $latest = $remaining
            ->pluck('new_access_expires_at')
            ->filter()
            ->max();

        OnlineCourseEnrollment::query()->whereKey($enrollmentId)->update([
            'access_expires_at' => $hasUnlimited ? null : $latest,
        ]);
    }

    private function syncOrderProvisionedAt(FormOrder $order): void
    {
        $recipients = $order->orderItems
            ->flatMap(fn (OrderItem $item) => $item->recipients)
            ->filter(fn (OrderItemRecipient $recipient) => trim((string) $recipient->email) !== '')
            ->reject(fn (OrderItemRecipient $recipient) => $recipient->isCancelled())
            ->values();

        if ($recipients->isEmpty()) {
            if ($order->pnedu_provisioned_at !== null) {
                $order->pnedu_provisioned_at = null;
                $order->save();
            }

            return;
        }

        $succeeded = $recipients->every(function (OrderItemRecipient $recipient) {
            return $recipient->fulfillments->contains(
                fn (OrderFulfillment $fulfillment) => $fulfillment->status === OrderFulfillment::STATUS_SUCCEEDED
            );
        });

        if ($succeeded) {
            if ($order->pnedu_provisioned_at === null) {
                $grantedAt = $recipients
                    ->flatMap(fn (OrderItemRecipient $recipient) => $recipient->fulfillments)
                    ->where('status', OrderFulfillment::STATUS_SUCCEEDED)
                    ->pluck('granted_at')
                    ->filter()
                    ->max();
                $order->pnedu_provisioned_at = $grantedAt ?? now('UTC');
                $order->save();
            }

            return;
        }

        if ($order->pnedu_provisioned_at !== null) {
            $order->pnedu_provisioned_at = null;
            $order->save();
        }
    }

    private function markUnsupported(OrderItem $item, OrderItemRecipient $recipient): string
    {
        Log::warning('Unsupported product fulfillment type', [
            'order_item_id' => $item->id,
            'recipient_id' => $recipient->id,
            'product_type' => $item->product_type,
            'fulfillment_type' => $item->fulfillment_type,
        ]);

        return 'failed';
    }

    /**
     * @return array{success: bool, fulfilled: int, skipped: int, failed: int, message: string}
     */
    private function result(
        bool $success,
        int $fulfilled,
        int $skipped,
        int $failed,
        string $message
    ): array {
        return compact('success', 'fulfilled', 'skipped', 'failed', 'message');
    }
}
