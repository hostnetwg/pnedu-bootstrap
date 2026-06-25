<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Course;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use App\Services\Analytics\AnalyticsPayloadSanitizer;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\BackendAnalyticsTracker;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class AnalyticsPaymentStatusChangedStage2B1Test extends TestCase
{
    private string $emailPrefix = 'analytics-stage-2b1';

    private array $temporaryPriceVariantIds = [];

    private array $temporaryEventUuids = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        if (! $this->requiredTablesAvailable()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm w środowisku testowym.');
        }
    }

    protected function tearDown(): void
    {
        $orderIds = FormOrder::withTrashed()
            ->where('orderer_email', 'like', $this->emailPrefix.'%')
            ->pluck('id');

        $paymentOrderIds = OnlinePaymentOrder::query()
            ->where('email', 'like', $this->emailPrefix.'%')
            ->pluck('id');

        if ($paymentOrderIds->isNotEmpty()) {
            DB::connection('pneadm')
                ->table('payment_webhook_logs')
                ->whereIn('online_payment_order_id', $paymentOrderIds)
                ->delete();
        }

        DB::connection('pneadm')
            ->table('online_payment_orders')
            ->where('email', 'like', $this->emailPrefix.'%')
            ->delete();

        if ($orderIds->isNotEmpty()) {
            DB::connection('pneadm')
                ->table('form_order_participants')
                ->whereIn('form_order_id', $orderIds)
                ->delete();

            FormOrder::withTrashed()
                ->whereIn('id', $orderIds)
                ->forceDelete();
        }

        if ($this->temporaryPriceVariantIds !== []) {
            DB::connection('pneadm')
                ->table('course_price_variants')
                ->whereIn('id', $this->temporaryPriceVariantIds)
                ->delete();
        }

        if ($this->temporaryEventUuids !== [] && $this->analyticsEventsTableAvailable()) {
            try {
                DB::connection('analytics')
                    ->table('analytics_events')
                    ->whereIn('event_uuid', $this->temporaryEventUuids)
                    ->delete();
            } catch (\Throwable) {
                // brak DB analityki w środowisku testowym — ignorujemy sprzątanie
            }
        }

        parent::tearDown();
    }

    // --- Normalizacja statusów (unit-level) -------------------------------------------------

    public function test_payu_status_normalization(): void
    {
        $this->assertSame('paid', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'COMPLETED'));
        $this->assertSame('canceled', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'CANCELED'));
        $this->assertSame('canceled', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'CANCELLED'));
        $this->assertSame('failed', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'REJECTED'));
        $this->assertSame('expired', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'EXPIRED'));
        $this->assertSame('pending', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'PENDING'));
        $this->assertSame('created', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'NEW'));
        $this->assertSame('unknown', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('payu', 'WHATEVER'));
    }

    public function test_paynow_status_normalization(): void
    {
        $this->assertSame('paid', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'CONFIRMED'));
        $this->assertSame('canceled', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'CANCELLED'));
        $this->assertSame('canceled', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'CANCELED'));
        $this->assertSame('failed', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'REJECTED'));
        $this->assertSame('failed', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'ERROR'));
        $this->assertSame('expired', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'EXPIRED'));
        $this->assertSame('pending', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'PENDING'));
        $this->assertSame('created', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'NEW'));
        $this->assertSame('unknown', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('paynow', 'WHATEVER'));
    }

    public function test_unknown_gateway_normalizes_to_unknown(): void
    {
        $this->assertSame('unknown', BackendAnalyticsTracker::normalizeAnalyticsPaymentStatus('stripe', 'COMPLETED'));
    }

    // --- Webhooki ----------------------------------------------------------------------------

    public function test_payu_webhook_dispatches_payment_status_changed(): void
    {
        Queue::fake();
        [$payload, $order] = $this->submitOnlineOrder();

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        $this->assertPaymentStatusChangedQueued($order, 'payu', 'paid', 'webhook');
        $this->assertPayloadHasNoPiiNorServerSideSessionFields($payload);
    }

    public function test_paynow_webhook_dispatches_payment_status_changed(): void
    {
        Queue::fake();
        [$payload, $order] = $this->submitOnlineOrder();
        $order->update(['payment_gateway' => 'paynow', 'payu_order_id' => 'paynow-payment-id']);

        $this->postJson(route('payment.paynow.notify'), [
            'paymentId' => 'paynow-payment-id',
            'externalId' => $order->ident,
            'status' => 'CONFIRMED',
        ])->assertStatus(200);

        $this->assertPaymentStatusChangedQueued($order, 'paynow', 'paid', 'webhook');
        $this->assertPayloadHasNoPiiNorServerSideSessionFields($payload);
    }

    public function test_webhook_without_form_order_id_does_not_dispatch(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();
        $order->update(['form_order_id' => null]);

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        $this->assertNoPaymentStatusChangedQueued();
    }

    public function test_payment_status_changed_goes_to_analytics_queue_with_uuid(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            return ($job->payload['event_name'] ?? null) === 'payment_status_changed'
                && ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && is_string($job->payload['event_uuid'] ?? null)
                && Str::isUuid($job->payload['event_uuid']);
        });
    }

    public function test_previous_status_is_included_when_available(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();
        // Po utworzeniu w bramce status modelu = 'created'.

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            if (($job->payload['event_name'] ?? null) !== 'payment_status_changed') {
                return false;
            }

            return ($job->payload['metadata']['payment_previous_status'] ?? null) === 'created';
        });
    }

    // --- Return sync -------------------------------------------------------------------------

    public function test_payu_return_sync_dispatches_payment_status_changed(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();

        Http::fake([
            '*/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/v2_1/orders/*' => Http::response([
                'orders' => [['status' => 'COMPLETED', 'extOrderId' => $order->ident]],
            ], 200),
        ]);

        $this->get(route('payment.payu.return', ['extOrderId' => $order->ident]))
            ->assertRedirect(route('payment.success', $order->ident));

        $this->assertPaymentStatusChangedQueued($order, 'payu', 'paid', 'return_sync');
    }

    public function test_paynow_return_sync_dispatches_payment_status_changed(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();
        $order->update(['payment_gateway' => 'paynow', 'payu_order_id' => 'paynow-payment-id']);

        Http::fake([
            '*/v3/payments/*/status' => Http::response(['status' => 'CONFIRMED'], 200),
        ]);

        $this->get(route('payment.paynow.return', ['externalId' => $order->ident]))
            ->assertRedirect(route('payment.success', $order->ident));

        $this->assertPaymentStatusChangedQueued($order, 'paynow', 'paid', 'return_sync');
    }

    // --- Idempotencja (deterministyczny event_uuid) -----------------------------------------

    public function test_event_uuid_is_deterministic_and_excludes_status_source(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();
        $tracker = app(BackendAnalyticsTracker::class);

        $tracker->trackPaymentStatusChanged($order, 'payu', 'paid', 'created', 'webhook');
        $tracker->trackPaymentStatusChanged($order, 'payu', 'paid', 'created', 'return_sync');

        $uuids = $this->pushedPaymentStatusUuids();

        $this->assertCount(1, $uuids, 'Webhook i return_sync tego samego statusu muszą mieć ten sam event_uuid.');
    }

    public function test_different_status_produces_different_event_uuid(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();
        $tracker = app(BackendAnalyticsTracker::class);

        $tracker->trackPaymentStatusChanged($order, 'payu', 'paid', 'pending', 'webhook');
        $tracker->trackPaymentStatusChanged($order, 'payu', 'failed', 'pending', 'webhook');

        $uuids = $this->pushedPaymentStatusUuids();

        $this->assertCount(2, $uuids, 'Różne statusy tej samej płatności muszą mieć różne event_uuid.');
    }

    public function test_duplicate_event_uuid_results_in_single_analytics_row(): void
    {
        if (! $this->analyticsEventsTableAvailable()) {
            $this->markTestSkipped('Brak tabeli analytics_events w środowisku testowym.');
        }

        $eventUuid = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'payment_status_changed|payu|987654|paid');
        $this->temporaryEventUuids[] = $eventUuid;

        $base = [
            'event_uuid' => $eventUuid,
            'event_name' => 'payment_status_changed',
            'event_category' => 'payment',
            'occurred_at' => now()->toDateTimeString(),
            'form_order_id' => 987654,
            'payment_order_id' => 987654,
            'metadata' => [
                'payment_gateway' => 'payu',
                'payment_status' => 'paid',
                'status_source' => 'webhook',
            ],
        ];

        (new StoreAnalyticsEventJob($base))->handle(new AnalyticsPayloadSanitizer);

        $returnSync = $base;
        $returnSync['metadata']['status_source'] = 'return_sync';
        (new StoreAnalyticsEventJob($returnSync))->handle(new AnalyticsPayloadSanitizer);

        $count = DB::connection('analytics')
            ->table('analytics_events')
            ->where('event_uuid', $eventUuid)
            ->count();

        $this->assertSame(1, $count);
    }

    // --- Tryby / fail-silent -----------------------------------------------------------------

    public function test_analytics_disabled_does_not_dispatch(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();

        config()->set('analytics.enabled', false);

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        $this->assertNoPaymentStatusChangedQueued();
    }

    public function test_off_mode_does_not_dispatch(): void
    {
        Queue::fake();
        [, $order] = $this->submitOnlineOrder();

        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'off');

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        $this->assertNoPaymentStatusChangedQueued();
    }

    public function test_analytics_failure_does_not_break_payu_webhook(): void
    {
        [, $order] = $this->submitOnlineOrderWithRealQueueFake();

        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis down with secret@example.com'));
        $this->app->instance(AnalyticsService::class, $service);

        $this->postJson(route('payment.payu.notify'), [
            'order' => [
                'orderId' => 'payu-test-order',
                'extOrderId' => $order->ident,
                'status' => 'COMPLETED',
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('online_payment_orders', [
            'id' => $order->id,
            'status' => OnlinePaymentOrder::STATUS_PAID,
        ], 'pneadm');
    }

    public function test_analytics_failure_does_not_break_paynow_webhook(): void
    {
        [, $order] = $this->submitOnlineOrderWithRealQueueFake();
        $order->update(['payment_gateway' => 'paynow', 'payu_order_id' => 'paynow-payment-id']);

        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis down with secret@example.com'));
        $this->app->instance(AnalyticsService::class, $service);

        $this->postJson(route('payment.paynow.notify'), [
            'paymentId' => 'paynow-payment-id',
            'externalId' => $order->ident,
            'status' => 'CONFIRMED',
        ])->assertStatus(200);

        $this->assertDatabaseHas('online_payment_orders', [
            'id' => $order->id,
            'status' => OnlinePaymentOrder::STATUS_PAID,
        ], 'pneadm');
    }

    public function test_analytics_failure_does_not_break_payu_return(): void
    {
        [, $order] = $this->submitOnlineOrderWithRealQueueFake();

        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis down'));
        $this->app->instance(AnalyticsService::class, $service);

        Http::fake([
            '*/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/v2_1/orders/*' => Http::response([
                'orders' => [['status' => 'COMPLETED', 'extOrderId' => $order->ident]],
            ], 200),
        ]);

        $this->get(route('payment.payu.return', ['extOrderId' => $order->ident]))
            ->assertRedirect(route('payment.success', $order->ident));
    }

    public function test_analytics_failure_does_not_break_paynow_return(): void
    {
        [, $order] = $this->submitOnlineOrderWithRealQueueFake();
        $order->update(['payment_gateway' => 'paynow', 'payu_order_id' => 'paynow-payment-id']);

        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis down'));
        $this->app->instance(AnalyticsService::class, $service);

        Http::fake([
            '*/v3/payments/*/status' => Http::response(['status' => 'CONFIRMED'], 200),
        ]);

        $this->get(route('payment.paynow.return', ['externalId' => $order->ident]))
            ->assertRedirect(route('payment.success', $order->ident));
    }

    // --- Helpers -----------------------------------------------------------------------------

    /**
     * @return array{0: array<string, mixed>, 1: OnlinePaymentOrder}
     */
    private function submitOnlineOrder(): array
    {
        $this->fakePayuGateway();
        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        $order = OnlinePaymentOrder::query()
            ->where('email', $payload['participant_email'])
            ->orderByDesc('id')
            ->firstOrFail();

        return [$payload, $order];
    }

    /**
     * Wariant tworzący zamówienie bez przechwytywania kolejki na czas notify,
     * używany w testach fail-silent (gdzie podmieniamy AnalyticsService na rzucający wyjątek).
     *
     * @return array{0: array<string, mixed>, 1: OnlinePaymentOrder}
     */
    private function submitOnlineOrderWithRealQueueFake(): array
    {
        Queue::fake();

        return $this->submitOnlineOrder();
    }

    private function pushedPaymentStatusUuids(): array
    {
        return collect(Queue::pushed(StoreAnalyticsEventJob::class))
            ->filter(fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === 'payment_status_changed')
            ->map(fn (StoreAnalyticsEventJob $job) => $job->payload['event_uuid'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function assertPaymentStatusChangedQueued(OnlinePaymentOrder $order, string $gateway, string $status, string $statusSource): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($order, $gateway, $status, $statusSource): bool {
            if (($job->payload['event_name'] ?? null) !== 'payment_status_changed') {
                return false;
            }

            $metadata = $job->payload['metadata'] ?? [];

            return ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && ($job->payload['payment_order_id'] ?? null) === (int) $order->id
                && ($job->payload['form_order_id'] ?? null) === (int) $order->form_order_id
                && ($metadata['payment_gateway'] ?? null) === $gateway
                && ($metadata['payment_status'] ?? null) === $status
                && ($metadata['status_source'] ?? null) === $statusSource
                && ($metadata['payment_type'] ?? null) === 'online'
                && ($metadata['order_flow'] ?? null) === 'online'
                && Str::isUuid($job->payload['event_uuid'] ?? '');
        });
    }

    private function assertNoPaymentStatusChangedQueued(): void
    {
        Queue::assertNotPushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === 'payment_status_changed');
    }

    private function assertPayloadHasNoPiiNorServerSideSessionFields(array $formPayload): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($formPayload): bool {
            if (($job->payload['event_name'] ?? null) !== 'payment_status_changed') {
                return false;
            }

            $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

            // Event server-to-server: brak sesji i kontekstu przeglądarki.
            $this->assertArrayNotHasKey('analytics_session_id', $job->payload);
            $this->assertArrayNotHasKey('order_form_session_id', $job->payload);
            $this->assertArrayNotHasKey('route_name', $job->payload);
            $this->assertArrayNotHasKey('path', $job->payload);
            $this->assertArrayNotHasKey('referrer_domain', $job->payload);
            $this->assertArrayNotHasKey('device_type', $job->payload);
            $this->assertArrayNotHasKey('url', $job->payload);
            $this->assertArrayNotHasKey('referrer', $job->payload);
            $this->assertArrayNotHasKey('raw_input', $job->payload);
            $this->assertArrayNotHasKey('raw_request', $job->payload);

            // Brak PII / danych płatnika.
            $this->assertStringNotContainsString($formPayload['contact_email'], $encoded);
            $this->assertStringNotContainsString($formPayload['contact_phone'], $encoded);
            $this->assertStringNotContainsString('Jan', $encoded);
            $this->assertStringNotContainsString('Kowalski', $encoded);
            $this->assertStringNotContainsString('Nowak', $encoded);
            $this->assertStringNotContainsString('Testowa 1', $encoded);
            $this->assertStringNotContainsString('Uwagi do faktury', $encoded);

            return true;
        });
    }

    private function fakePayuGateway(): void
    {
        Http::fake([
            '*/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/v2_1/orders' => Http::response([
                'redirectUri' => 'https://payu.test/redirect',
                'orderId' => 'payu-test-order',
            ], 201),
        ]);
        config()->set('services.payu.client_id', 'test-client');
        config()->set('services.payu.client_secret', 'test-secret');
        config()->set('services.payu.pos_id', 'test-pos');
    }

    private function validOnlinePayload(): array
    {
        [$courseId, $priceVariantId] = $this->courseIdAndPriceVariantId();
        $email = $this->emailPrefix.'-'.Str::lower(Str::random(8)).'@example.test';

        $payload = [
            'buyer_type' => 'person',
            'payment_type' => 'online',
            'payment_gateway' => 'payu',
            'contact_name' => 'Jan Kowalski',
            'contact_phone' => '501654274',
            'contact_email' => $email,
            'buyer_address' => 'Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'buyer_person_first_name' => 'Jan',
            'buyer_person_last_name' => 'Kowalski',
            'participant_first_name' => 'Anna',
            'participant_last_name' => 'Nowak',
            'participant_email' => $email,
            'invoice_notes' => 'Uwagi do faktury',
        ];

        $payload['price_variant_id'] = $priceVariantId ?? $this->ensurePositivePriceVariant($courseId);

        return [$courseId, $payload];
    }

    private function courseIdAndPriceVariantId(): array
    {
        $course = Course::with('priceVariants')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('sendy_suppression_list_id')
                    ->orWhere('sendy_suppression_list_id', '');
            })
            ->orderBy('id')
            ->first();

        $course ??= Course::with('priceVariants')
            ->where('is_active', true)
            ->orderBy('id')
            ->firstOrFail();

        $activeVariants = $course->priceVariants
            ->filter(fn ($variant) => (bool) $variant->is_active)
            ->filter(fn ($variant) => $variant->isAvailableForCourseEndState($course->hasEnded()))
            ->sortBy('id')
            ->values();

        $variantId = null;
        foreach ($activeVariants as $variant) {
            if ((float) $variant->price > 0) {
                $variantId = (int) $variant->id;
                break;
            }
        }

        return [$course->id, $variantId];
    }

    private function ensurePositivePriceVariant(int $courseId): int
    {
        $variantId = DB::connection('pneadm')
            ->table('course_price_variants')
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('price', '>', 0)
            ->orderBy('id')
            ->value('id');

        if ($variantId !== null) {
            return (int) $variantId;
        }

        $id = DB::connection('pneadm')
            ->table('course_price_variants')
            ->insertGetId([
                'course_id' => $courseId,
                'name' => 'Analytics test price',
                'description' => null,
                'is_active' => true,
                'price' => 199.00,
                'is_promotion' => false,
                'promotion_price' => null,
                'promotion_type' => 'disabled',
                'promotion_start' => null,
                'promotion_end' => null,
                'access_type' => '1',
                'access_start_datetime' => null,
                'access_end_datetime' => null,
                'access_duration_value' => null,
                'access_duration_unit' => null,
                'availability_after_course_end' => 'always',
                'post_end_access_rule' => 'inherit',
                'post_end_access_duration_value' => null,
                'post_end_access_duration_unit' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->temporaryPriceVariantIds[] = $id;

        return (int) $id;
    }

    private function requiredTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('course_price_variants')
                && Schema::connection('pneadm')->hasTable('form_orders')
                && Schema::connection('pneadm')->hasTable('form_order_participants')
                && Schema::connection('pneadm')->hasTable('online_payment_orders')
                && Schema::connection('pneadm')->hasTable('payment_webhook_logs');
        } catch (\Throwable) {
            return false;
        }
    }

    private function analyticsEventsTableAvailable(): bool
    {
        try {
            return Schema::connection('analytics')->hasTable('analytics_events');
        } catch (\Throwable) {
            return false;
        }
    }
}
