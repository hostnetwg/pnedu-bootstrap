<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Course;
use App\Models\FormOrder;
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

class AnalyticsPaymentSelectionStage2A1Test extends TestCase
{
    private string $emailPrefix = 'analytics-stage-2a1';

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

    public function test_deferred_submit_dispatches_deferred_invoice_selected(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        $this->assertPaymentSelectionQueued('deferred_invoice_selected', [
            'form_variant' => 'legacy',
            'payment_type' => 'deferred_invoice',
            'order_flow' => 'deferred',
        ]);
        $this->assertNoAnalyticsEventQueued('online_payment_selected');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_online_submit_dispatches_online_payment_selected_before_gateway_redirect(): void
    {
        Queue::fake();
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        $this->assertPaymentSelectionQueued('online_payment_selected', [
            'form_variant' => 'legacy',
            'payment_type' => 'online',
            'payment_gateway' => 'payu',
            'order_flow' => 'online',
        ]);
        $this->assertNoAnalyticsEventQueued('deferred_invoice_selected');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_online_payment_gateway_is_whitelisted_value(): void
    {
        Queue::fake();
        $this->fakePayuGateway();

        [$courseId, $payload] = $this->validOnlinePayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            if (($job->payload['event_name'] ?? null) !== 'online_payment_selected') {
                return false;
            }

            $gateway = $job->payload['metadata']['payment_gateway'] ?? null;

            return in_array($gateway, ['payu', 'paynow', 'unknown'], true);
        });
    }

    public function test_payment_selection_events_go_to_analytics_queue(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            return ($job->payload['event_name'] ?? null) === 'deferred_invoice_selected'
                && ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics';
        });
    }

    public function test_payload_does_not_contain_personal_or_raw_data(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->withHeader('Referer', 'https://facebook.com/post?fbclid=raw-secret')
            ->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            if (($job->payload['event_name'] ?? null) !== 'deferred_invoice_selected') {
                return false;
            }

            $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

            $this->assertArrayNotHasKey('url', $job->payload);
            $this->assertArrayNotHasKey('referrer', $job->payload);
            $this->assertArrayNotHasKey('raw_input', $job->payload);
            $this->assertArrayNotHasKey('raw_request', $job->payload);

            // brak PII / danych formularza / bramki
            $this->assertStringNotContainsString('secret@example.com', $encoded);
            $this->assertStringNotContainsString('501654274', $encoded);
            $this->assertStringNotContainsString('1234567890', $encoded);
            $this->assertStringNotContainsString('Jan', $encoded);
            $this->assertStringNotContainsString('Kowalski', $encoded);
            $this->assertStringNotContainsString('Publiczna Szkoła Testowa', $encoded);
            $this->assertStringNotContainsString('Testowa 1', $encoded);
            $this->assertStringNotContainsString('Uwagi do faktury', $encoded);
            $this->assertStringNotContainsString('fbclid=raw-secret', $encoded);

            return true;
        });
    }

    public function test_analytics_disabled_does_not_dispatch_selection_events(): void
    {
        Queue::fake();
        config()->set('analytics.enabled', false);

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
    }

    public function test_off_mode_does_not_dispatch_selection_events(): void
    {
        Queue::fake();
        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'off');

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
    }

    public function test_analytics_failure_does_not_break_deferred_flow(): void
    {
        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis unavailable with secret@example.com'));
        $this->app->instance(AnalyticsService::class, $service);

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('form_orders', [
            'orderer_email' => $payload['contact_email'],
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
        ], 'pneadm');
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

    public function test_form_order_creation_is_unchanged_for_deferred(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('form_orders', [
            'orderer_email' => $payload['contact_email'],
            'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
            'payment_status' => FormOrder::PAYMENT_STATUS_SUBMITTED,
        ], 'pneadm');
    }

    private function assertPaymentSelectionQueued(string $eventName, array $expectedMetadata): void
    {
        $matchingJobs = Queue::pushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === $eventName);

        $this->assertCount(1, $matchingJobs);

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($eventName, $expectedMetadata): bool {
            if (($job->payload['event_name'] ?? null) !== $eventName) {
                return false;
            }

            $metadata = $job->payload['metadata'] ?? [];

            foreach ($expectedMetadata as $key => $value) {
                if (($metadata[$key] ?? null) !== $value) {
                    return false;
                }
            }

            return ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && isset($job->payload['analytics_session_id'])
                && Str::isUuid($job->payload['analytics_session_id'])
                && isset($job->payload['order_form_session_id'])
                && Str::isUuid($job->payload['order_form_session_id'])
                && isset($job->payload['course_id']);
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
            $this->assertStringNotContainsString('secret@example.com', $encoded);
            $this->assertStringNotContainsString('501654274', $encoded);
            $this->assertStringNotContainsString('1234567890', $encoded);
            $this->assertStringNotContainsString('Kowalski', $encoded);
            $this->assertStringNotContainsString('Publiczna Szkoła Testowa', $encoded);

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
