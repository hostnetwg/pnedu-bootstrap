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
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AnalyticsOrderFormStage1BTest extends TestCase
{
    private string $emailPrefix = 'analytics-stage-1b';

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

    public function test_submit_attempt_and_validation_failed_are_dispatched_on_invalid_submit(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->invalidPayloadMissingPaymentType();

        $this->withHeader('Referer', 'https://facebook.com/post?fbclid=raw-secret')
            ->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors(['payment_type']);

        $this->assertAnalyticsEventQueued('order_form_submit_attempted');
        $this->assertAnalyticsEventQueued('order_form_validation_failed');
        $this->assertNoAnalyticsEventQueued('form_order_created');
        $this->assertEveryAnalyticsPayloadIsSafe();
        $this->assertValidationPayloadIsTechnicalOnly();
    }

    public function test_validation_exception_is_rethrown_after_tracking(): void
    {
        Queue::fake();
        $this->withoutExceptionHandling();

        [$courseId, $payload] = $this->invalidPayloadMissingPaymentType();

        try {
            $this->post(route('payment.order-form.store', $courseId), $payload);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment_type', $exception->errors());
        }

        $this->assertAnalyticsEventQueued('order_form_submit_attempted');
        $this->assertAnalyticsEventQueued('order_form_validation_failed');
    }

    public function test_manual_with_errors_path_dispatches_validation_failed_and_does_not_create_order(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();
        $payload['buyer_type'] = 'organisation';
        $payload['buyer_name'] = 'Publiczna Szkoła Testowa';
        $payload['buyer_nip'] = '1234567890';
        unset($payload['buyer_person_first_name'], $payload['buyer_person_last_name']);
        $payload['recipient_name'] = 'Publiczna Szkoła Testowa';
        $payload['recipient_nip'] = '123';

        $before = $this->formOrdersCount();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors(['recipient_nip'])
            ->assertSessionHasInput('contact_email', $payload['contact_email']);

        $this->assertSame($before, $this->formOrdersCount());
        $this->assertAnalyticsEventQueued('order_form_submit_attempted');
        $this->assertAnalyticsEventQueued('order_form_validation_failed');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_valid_deferred_submit_creates_order_and_dispatches_form_order_created(): void
    {
        Queue::fake();

        [$courseId, $payload] = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('form_orders', [
            'orderer_email' => $payload['contact_email'],
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
        ], 'pneadm');

        $this->assertAnalyticsEventQueued('order_form_submit_attempted');
        $this->assertNoAnalyticsEventQueued('order_form_validation_failed');
        $this->assertFormOrderCreatedQueued('deferred');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_valid_online_submit_creates_payment_order_and_dispatches_form_order_created_before_gateway_redirect(): void
    {
        Queue::fake();
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

        [$courseId, $payload] = $this->validDeferredPayload();
        $payload['price_variant_id'] = $this->ensurePositivePriceVariant($courseId);

        $payload['payment_type'] = 'online';
        $payload['payment_gateway'] = 'payu';
        unset($payload['payment_terms']);

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');

        $formOrderId = (int) FormOrder::query()
            ->where('orderer_email', $payload['contact_email'])
            ->value('id');

        $this->assertGreaterThan(0, $formOrderId);
        $this->assertDatabaseHas('online_payment_orders', [
            'form_order_id' => $formOrderId,
            'email' => $payload['participant_email'],
            'payment_gateway' => 'payu',
        ], 'pneadm');

        $this->assertAnalyticsEventQueued('order_form_submit_attempted');
        $this->assertFormOrderCreatedQueued('online');
        $this->assertEveryAnalyticsPayloadIsSafe();
    }

    public function test_analytics_disabled_and_off_mode_do_not_dispatch_events(): void
    {
        Queue::fake();
        [$courseId, $payload] = $this->invalidPayloadMissingPaymentType();

        config()->set('analytics.enabled', false);
        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors(['payment_type']);
        Queue::assertNotPushed(StoreAnalyticsEventJob::class);

        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'off');
        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors(['payment_type']);
        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
    }

    public function test_analytics_failure_does_not_change_validation_response(): void
    {
        $service = Mockery::mock(AnalyticsService::class);
        $service->shouldReceive('track')->andThrow(new Exception('redis unavailable with secret@example.com'));
        $this->app->instance(AnalyticsService::class, $service);

        [$courseId, $payload] = $this->invalidPayloadMissingPaymentType();

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors(['payment_type'])
            ->assertSessionHasInput('contact_email', $payload['contact_email']);
    }

    public function test_analytics_failure_does_not_break_deferred_order_creation(): void
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

        [$courseId, $payload] = $this->validDeferredPayload();
        $payload['price_variant_id'] = $this->ensurePositivePriceVariant($courseId);
        $payload['payment_type'] = 'online';
        $payload['payment_gateway'] = 'payu';
        unset($payload['payment_terms']);

        $this->post(route('payment.order-form.store', $courseId), $payload)
            ->assertRedirect('https://payu.test/redirect');
    }

    private function assertAnalyticsEventQueued(string $eventName): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($eventName): bool {
            return ($job->payload['event_name'] ?? null) === $eventName
                && ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
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

    private function assertFormOrderCreatedQueued(string $orderFlow): void
    {
        $matchingJobs = Queue::pushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === 'form_order_created');

        $this->assertCount(1, $matchingJobs);

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($orderFlow): bool {
            if (($job->payload['event_name'] ?? null) !== 'form_order_created') {
                return false;
            }

            $metadata = $job->payload['metadata'] ?? [];

            return ($job->connection ?? null) === 'redis'
                && ($job->queue ?? null) === 'analytics'
                && isset($job->payload['form_order_id'])
                && isset($job->payload['analytics_session_id'])
                && Str::isUuid($job->payload['analytics_session_id'])
                && isset($job->payload['order_form_session_id'])
                && Str::isUuid($job->payload['order_form_session_id'])
                && ($metadata['order_flow'] ?? null) === $orderFlow
                && isset($metadata['participant_count'])
                && array_key_exists('has_recipient', $metadata);
        });
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
            $this->assertStringNotContainsString('Jan', $encoded);
            $this->assertStringNotContainsString('Kowalski', $encoded);
            $this->assertStringNotContainsString('Publiczna Szkoła Testowa', $encoded);
            $this->assertStringNotContainsString('Testowa 1', $encoded);
            $this->assertStringNotContainsString('Uwagi do faktury', $encoded);
            $this->assertStringNotContainsString('fbclid=raw-secret', $encoded);

            return true;
        });
    }

    private function assertValidationPayloadIsTechnicalOnly(): void
    {
        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            if (($job->payload['event_name'] ?? null) !== 'order_form_validation_failed') {
                return false;
            }

            $metadata = $job->payload['metadata'] ?? [];

            $this->assertSame('laravel_validation', $metadata['validation_context'] ?? null);
            $this->assertSame(1, $metadata['error_count'] ?? null);
            $this->assertContains('payment_type', $metadata['field_keys'] ?? []);
            $this->assertContains('payment', $metadata['section_keys'] ?? []);
            $this->assertContains('required', $metadata['error_codes'] ?? []);

            return true;
        });
    }

    private function invalidPayloadMissingPaymentType(): array
    {
        [$courseId, $payload] = $this->validDeferredPayload();
        unset($payload['payment_type']);

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

    private function formOrdersCount(): int
    {
        return (int) FormOrder::query()->count();
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
