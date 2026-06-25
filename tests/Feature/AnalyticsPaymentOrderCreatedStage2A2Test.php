<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Course;
use App\Models\FormOrder;
use App\Models\OnlinePaymentOrder;
use App\Services\Analytics\AnalyticsService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AnalyticsPaymentOrderCreatedStage2A2Test extends TestCase
{
    private string $emailPrefix = 'analytics-stage-2a2';

    private array $temporaryPriceVariantIds = [];

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

        parent::tearDown();
    }

    public function test_online_submit_dispatches_payment_order_created_after_online_payment_order_create(): void
    {
        Queue::fake();
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        $formOrderId = (int) FormOrder::query()
            ->where('orderer_email', $payload['contact_email'])
            ->value('id');

        $paymentOrderId = (int) OnlinePaymentOrder::query()
            ->where('form_order_id', $formOrderId)
            ->value('id');

        $this->assertGreaterThan(0, $formOrderId);
        $this->assertGreaterThan(0, $paymentOrderId);

        $this->assertPaymentOrderCreatedQueued($formOrderId, $paymentOrderId, 'payu');
        $this->assertNoAnalyticsEventQueued('payment_status_changed');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_deferred_submit_does_not_dispatch_payment_order_created(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        $this->assertNoAnalyticsEventQueued('payment_order_created');
    }

    public function test_payment_order_created_goes_to_analytics_queue(): void
    {
        Queue::fake();
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            return ($job->payload['event_name'] ?? null) === 'payment_order_created'
                && ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics';
        });
    }

    public function test_payload_contains_safe_payment_fields_without_personal_data(): void
    {
        Queue::fake();
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->withHeader('Referer', 'https://facebook.com/post?fbclid=raw-secret')
            ->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            if (($job->payload['event_name'] ?? null) !== 'payment_order_created') {
                return false;
            }

            $metadata = $job->payload['metadata'] ?? [];
            $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

            $this->assertIsInt($job->payload['payment_order_id'] ?? null);
            $this->assertIsInt($job->payload['form_order_id'] ?? null);
            $this->assertIsFloat($job->payload['amount_snapshot'] ?? null);
            $this->assertGreaterThan(0, $job->payload['amount_snapshot']);
            $this->assertSame('payu', $metadata['payment_gateway'] ?? null);
            $this->assertSame('online', $metadata['payment_type'] ?? null);
            $this->assertSame('online', $metadata['order_flow'] ?? null);

            $this->assertArrayNotHasKey('url', $job->payload);
            $this->assertArrayNotHasKey('referrer', $job->payload);
            $this->assertArrayNotHasKey('raw_input', $job->payload);
            $this->assertArrayNotHasKey('raw_request', $job->payload);
            $this->assertStringNotContainsString('secret@example.com', $encoded);
            $this->assertStringNotContainsString('501654274', $encoded);
            $this->assertStringNotContainsString('1234567890', $encoded);
            $this->assertStringNotContainsString('Jan', $encoded);
            $this->assertStringNotContainsString('Kowalski', $encoded);
            $this->assertStringNotContainsString('Publiczna Szkoła Testowa', $encoded);
            $this->assertStringNotContainsString('fbclid=raw-secret', $encoded);

            return true;
        });
    }

    public function test_analytics_disabled_does_not_dispatch_payment_order_created(): void
    {
        Queue::fake();
        config()->set('analytics.enabled', false);
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
    }

    public function test_off_mode_does_not_dispatch_payment_order_created(): void
    {
        Queue::fake();
        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'off');
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
    }

    public function test_analytics_failure_does_not_break_online_gateway_redirect(): void
    {
        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis unavailable with secret@example.com'));
        $this->app->instance(AnalyticsService::class, $service);

        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');
    }

    public function test_online_payment_order_creation_is_unchanged(): void
    {
        Queue::fake();
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        $formOrderId = (int) FormOrder::query()
            ->where('orderer_email', $payload['contact_email'])
            ->value('id');

        $this->assertDatabaseHas('online_payment_orders', [
            'form_order_id' => $formOrderId,
            'email' => $payload['participant_email'],
            'payment_gateway' => 'payu',
        ], 'pneadm');
    }

    private function assertPaymentOrderCreatedQueued(int $formOrderId, int $paymentOrderId, string $gateway): void
    {
        $matchingJobs = Queue::pushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === 'payment_order_created');

        $this->assertCount(1, $matchingJobs);

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($formOrderId, $paymentOrderId, $gateway): bool {
            if (($job->payload['event_name'] ?? null) !== 'payment_order_created') {
                return false;
            }

            $metadata = $job->payload['metadata'] ?? [];

            return ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && ($job->payload['form_order_id'] ?? null) === $formOrderId
                && ($job->payload['payment_order_id'] ?? null) === $paymentOrderId
                && is_float($job->payload['amount_snapshot'] ?? null)
                && ($metadata['payment_gateway'] ?? null) === $gateway
                && ($metadata['payment_type'] ?? null) === 'online'
                && ($metadata['order_flow'] ?? null) === 'online'
                && isset($job->payload['analytics_session_id'])
                && Str::isUuid($job->payload['analytics_session_id'])
                && isset($job->payload['order_form_session_id'])
                && Str::isUuid($job->payload['order_form_session_id']);
        });
    }

    private function assertNoAnalyticsEventQueued(string $eventName): void
    {
        Queue::assertNotPushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === $eventName);
    }

    private function assertEveryAnalyticsPayloadIsSafe(): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

            $this->assertArrayNotHasKey('url', $job->payload);
            $this->assertArrayNotHasKey('referrer', $job->payload);
            $this->assertArrayNotHasKey('raw_input', $job->payload);
            $this->assertArrayNotHasKey('raw_request', $job->payload);
            $this->assertStringNotContainsString('Kowalski', $encoded);
            $this->assertStringNotContainsString('501654274', $encoded);

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
        [$courseId, $payload] = $this->validDeferredPayload();
        $payload['price_variant_id'] = $this->ensurePositivePriceVariant($courseId);
        $payload['payment_type'] = 'online';
        $payload['payment_gateway'] = 'payu';
        unset($payload['payment_terms']);

        return [$courseId, $payload];
    }

    private function validDeferredPayload(): array
    {
        [$courseId, $priceVariantId] = $this->courseIdAndPriceVariantId();
        $email = $this->emailPrefix.'-'.Str::lower(Str::random(8)).'@example.test';

        $payload = [
            'buyer_type' => 'person',
            'payment_type' => 'deferred',
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
            'payment_terms' => 14,
        ];

        if ($priceVariantId !== null) {
            $payload['price_variant_id'] = $priceVariantId;
        }

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

        return [$course->id, $activeVariants->count() > 0 ? (int) $activeVariants->first()->id : null];
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
                && Schema::connection('pneadm')->hasTable('online_payment_orders');
        } catch (\Throwable) {
            return false;
        }
    }
}
