<?php

namespace Tests\Feature;

use App\Enums\Analytics\AnalyticsEventName;
use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Course;
use App\Services\GusBirService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AnalyticsGusTrackingStage2ETest extends TestCase
{
    private const COURSE_ID = 999003;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'standard');
    }

    private function collectorSource(): string
    {
        return (string) file_get_contents(resource_path('views/courses/partials/order-form-client-tracking.blade.php'));
    }

    private function renderOrderForm(): string
    {
        if (! Schema::connection('pneadm')->hasTable('courses')) {
            $this->markTestSkipped('Brak tabeli courses.');
        }

        $course = Course::query()->where('is_active', true)->orderBy('id')->first();
        if ($course === null) {
            $this->markTestSkipped('Brak aktywnego kursu.');
        }

        return (string) $this->get(route('payment.order-form', $course->id))->assertOk()->getContent();
    }

    private function postClientEvents(array $events): void
    {
        $this->postJson(route('analytics.client-events.store'), [
            'course_id' => self::COURSE_ID,
            'form_session_id' => (string) Str::uuid(),
            'events' => $events,
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
        ])->assertNoContent();
    }

    /** @return list<StoreAnalyticsEventJob> */
    private function pushed(string $eventName): array
    {
        return Queue::pushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === $eventName)->all();
    }

    public function test_gus_buttons_have_explicit_target_attribute(): void
    {
        $html = $this->renderOrderForm();

        $this->assertStringContainsString('id="buyer_gus_button" data-gus-target="buyer"', $html);
        $this->assertStringContainsString('id="recipient_gus_button" data-gus-target="recipient"', $html);
        $this->assertStringContainsString('Pobierz dane z GUS', $html);
    }

    public function test_collector_handles_gus_lookup_clicked_without_nip_value(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('gus_lookup_clicked', $source);
        $this->assertStringContainsString('nip_present', $source);
        $this->assertStringContainsString('nip_format_valid_client', $source);
        $this->assertStringContainsString('data-gus-target', $source);
        $this->assertDoesNotMatchRegularExpression("/gus_lookup_clicked[^;]*\.value/", $source);
    }

    public function test_client_endpoint_accepts_gus_lookup_clicked_without_pii(): void
    {
        $this->postClientEvents([[
            'event_name' => 'gus_lookup_clicked',
            'target' => 'buyer',
            'section_key' => 'invoice_buyer',
            'nip_present' => true,
            'nip_format_valid_client' => true,
            'seconds_from_page_load' => 12,
            'nip' => '1234567890',
            'buyer_nip' => '1234567890',
        ]]);

        $meta = $this->pushed('gus_lookup_clicked')[0]->payload['metadata'] ?? [];
        $encoded = json_encode($this->pushed('gus_lookup_clicked')[0]->payload, JSON_THROW_ON_ERROR);

        $this->assertSame('buyer', $meta['target'] ?? null);
        $this->assertTrue($meta['nip_present'] ?? false);
        $this->assertStringNotContainsString('1234567890', $encoded);
    }

    public function test_client_endpoint_accepts_gus_data_applied_without_field_values(): void
    {
        $this->postClientEvents([[
            'event_name' => 'gus_data_applied',
            'target' => 'recipient',
            'section_key' => 'invoice_recipient',
            'fields_applied_count' => 4,
            'overwritten_manual_fields_count' => 1,
            'seconds_after_gus_success' => 2,
            'recipient_name' => 'Sekretna Szkoła',
            'recipient_address' => 'Tajna 1',
        ]]);

        $meta = $this->pushed('gus_data_applied')[0]->payload['metadata'] ?? [];
        $encoded = json_encode($this->pushed('gus_data_applied')[0]->payload, JSON_THROW_ON_ERROR);

        $this->assertSame(4, $meta['fields_applied_count'] ?? null);
        $this->assertStringNotContainsString('Sekretna', $encoded);
    }

    public function test_client_endpoint_accepts_form_field_edited_after_gus_without_values(): void
    {
        $this->postClientEvents([[
            'event_name' => 'form_field_edited_after_gus',
            'gus_target' => 'buyer',
            'section_key' => 'invoice_buyer',
            'field_key' => 'buyer_nip',
            'field_type' => 'text',
            'seconds_after_gus_success' => 8,
            'old_value' => '1234567890',
            'new_value' => '9876543210',
        ]]);

        $meta = $this->pushed('form_field_edited_after_gus')[0]->payload['metadata'] ?? [];
        $encoded = json_encode($this->pushed('form_field_edited_after_gus')[0]->payload, JSON_THROW_ON_ERROR);

        $this->assertSame('buyer_nip', $meta['field_key'] ?? null);
        $this->assertStringNotContainsString('1234567890', $encoded);
        $this->assertStringNotContainsString('9876543210', $encoded);
    }

    public function test_client_endpoint_accepts_gus_manual_fallback_started_once_metadata(): void
    {
        $this->postClientEvents([[
            'event_name' => 'gus_manual_fallback_started',
            'target' => 'buyer',
            'section_key' => 'invoice_buyer',
            'first_field_key' => 'buyer_address',
            'seconds_after_gus_error' => 5,
        ]]);

        $meta = $this->pushed('gus_manual_fallback_started')[0]->payload['metadata'] ?? [];
        $this->assertSame('buyer_address', $meta['first_field_key'] ?? null);
        $this->assertSame(5, $meta['seconds_after_gus_error'] ?? null);
    }

    public function test_collector_limits_manual_fallback_to_once_per_target(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('gusFallbackStarted', $source);
        $this->assertStringContainsString('if (!target || gusFallbackStarted[target]', $source);
    }

    public function test_collector_limits_field_edited_after_gus_to_once_per_field_key(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('fieldsEditedAfterGusSent', $source);
    }

    public function test_backend_tracks_started_success_and_error_events(): void
    {
        $gusBir = Mockery::mock(GusBirService::class);
        $gusBir->shouldReceive('normalizeNip')->andReturn('1234567890');
        $gusBir->shouldReceive('lookupByNip')->once()->andReturn([
            'nip' => '1234567890',
            'regon' => '123456789',
            'name' => 'Testowa Szkoła',
            'postcode' => '00-001',
            'city' => 'Warszawa',
            'address' => 'Testowa 1',
        ]);
        $this->app->instance(GusBirService::class, $gusBir);

        $this->postJson(route('courses.gus-lookup'), [
            'nip' => '1234567890',
            'target' => 'buyer',
            'course_id' => self::COURSE_ID,
            'form_session_id' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertCount(1, $this->pushed(AnalyticsEventName::GusLookupStarted->value));
        $this->assertCount(1, $this->pushed(AnalyticsEventName::GusLookupSuccess->value));

        $success = $this->pushed(AnalyticsEventName::GusLookupSuccess->value)[0]->payload;
        $encoded = json_encode($success, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('latency_ms', $success['metadata'] ?? []);
        $this->assertStringNotContainsString('Testowa Szkoła', $encoded);
        $this->assertStringNotContainsString('1234567890', $encoded);
    }

    public function test_backend_tracks_not_found_error_without_pii(): void
    {
        $gusBir = Mockery::mock(GusBirService::class);
        $gusBir->shouldReceive('normalizeNip')->andReturn('1234567890');
        $gusBir->shouldReceive('lookupByNip')->once()->andReturn(null);
        $this->app->instance(GusBirService::class, $gusBir);

        $this->postJson(route('courses.gus-lookup'), [
            'nip' => '1234567890',
            'target' => 'recipient',
            'course_id' => self::COURSE_ID,
        ])->assertNotFound();

        $error = $this->pushed(AnalyticsEventName::GusLookupError->value)[0]->payload;
        $this->assertSame('not_found', $error['metadata']['error_type'] ?? null);
        $this->assertSame(404, $error['metadata']['http_status'] ?? null);
    }

    public function test_backend_tracks_validation_error_for_invalid_nip(): void
    {
        $gusBir = Mockery::mock(GusBirService::class);
        $gusBir->shouldReceive('normalizeNip')->andReturn(null);
        $gusBir->shouldNotReceive('lookupByNip');
        $this->app->instance(GusBirService::class, $gusBir);

        $this->postJson(route('courses.gus-lookup'), [
            'nip' => '123',
            'target' => 'buyer',
            'course_id' => self::COURSE_ID,
        ])->assertUnprocessable();

        $this->assertCount(0, $this->pushed(AnalyticsEventName::GusLookupStarted->value));
        $this->assertCount(1, $this->pushed(AnalyticsEventName::GusLookupError->value));
        $this->assertSame('validation_error', $this->pushed(AnalyticsEventName::GusLookupError->value)[0]->payload['metadata']['error_type'] ?? null);
    }

    public function test_legacy_events_still_present_in_collector(): void
    {
        $source = $this->collectorSource();

        foreach (['order_form_started', 'order_form_submit_clicked'] as $legacy) {
            $this->assertStringContainsString($legacy, $source);
        }
    }

    public function test_gus_lookup_request_includes_optional_analytics_context_in_order_form(): void
    {
        $html = $this->renderOrderForm();

        $this->assertStringContainsString('analyticsContextForGus', $html);
        $this->assertStringContainsString('form_session_id', $html);
    }
}
