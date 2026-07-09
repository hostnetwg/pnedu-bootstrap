<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Course;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Etap 2D — jawne sekcje v2 w HTML + kontrolowana emisja form_field_changed.
 */
class AnalyticsOrderFormClientTrackingStage2DTest extends TestCase
{
    private const V2_SECTIONS = [
        'contact',
        'invoice_buyer',
        'invoice_recipient',
        'participants',
        'payment',
        'consents',
        'submit',
    ];

    private const COURSE_ID = 999002;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'standard');

        if (! $this->requiredTablesAvailable()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm w środowisku testowym.');
        }
    }

    private function collectorSource(): string
    {
        return (string) file_get_contents(resource_path('views/courses/partials/order-form-client-tracking.blade.php'));
    }

    private function renderOrderForm(): string
    {
        $course = Course::query()->where('is_active', true)->orderBy('id')->first();
        if ($course === null) {
            $this->markTestSkipped('Brak aktywnego kursu.');
        }

        return (string) $this->get(route('payment.order-form', $course->id))
            ->assertStatus(200)
            ->getContent();
    }

    private function postEvents(array $body): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('analytics.client-events.store'), $body, [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
        ]);
    }

    /** @return list<StoreAnalyticsEventJob> */
    private function pushedEvents(?string $eventName = null): array
    {
        return Queue::pushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job) use ($eventName): bool {
            if ($eventName === null) {
                return true;
            }

            return ($job->payload['event_name'] ?? null) === $eventName;
        })->all();
    }

    public function test_order_form_renders_explicit_v2_section_attributes(): void
    {
        $html = $this->renderOrderForm();

        foreach (self::V2_SECTIONS as $section) {
            $this->assertStringContainsString(
                'data-analytics-section-v2="'.$section.'"',
                $html,
                "Brak jawnego atrybutu v2 dla sekcji: {$section}"
            );
        }
    }

    public function test_legacy_data_analytics_section_still_present_for_b1_b2(): void
    {
        $html = $this->renderOrderForm();

        foreach (['buyer_data', 'recipient_data', 'participants', 'payment_method', 'invoice'] as $legacy) {
            $this->assertStringContainsString('data-analytics-section="'.$legacy.'"', $html);
        }
    }

    public function test_js_prefers_data_analytics_section_v2(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString("el.closest('[data-analytics-section-v2]')", $source);
        $this->assertStringContainsString('data-analytics-section-v2', $source);
        $this->assertStringContainsString('legacyV2SectionKeyForElement', $source);
    }

    public function test_js_fallback_to_legacy_mapping_when_v2_attribute_missing(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('return legacyV2SectionKeyForElement(el);', $source);
        $this->assertStringContainsString('buyerFieldsets()', $source);
    }

    public function test_form_field_changed_sent_at_most_once_per_field_key(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('fieldsChangedSent', $source);
        $this->assertStringContainsString('if (!fKey || fieldsChangedSent[fKey])', $source);
        $this->assertStringContainsString("fieldsChangedSent[fKey] = true", $source);
    }

    public function test_form_field_changed_does_not_enqueue_field_values(): void
    {
        $source = $this->collectorSource();

        $this->assertDoesNotMatchRegularExpression(
            "/enqueue\('form_field_changed'[^)]*\.value/",
            $source
        );
    }

    public function test_endpoint_accepts_form_field_changed_without_pii(): void
    {
        $formSessionId = (string) Str::uuid();

        $this->postEvents([
            'course_id' => self::COURSE_ID,
            'form_session_id' => $formSessionId,
            'events' => [
                [
                    'event_name' => 'form_field_changed',
                    'section_key' => 'invoice_buyer',
                    'field_key' => 'buyer_nip',
                    'field_type' => 'text',
                    'source' => 'manual',
                    'has_value' => true,
                    'seconds_from_page_load' => 42,
                    'buyer_nip' => '1234567890',
                    'email' => 'sekret@example.pl',
                    'field_value' => 'Publiczna Szkoła',
                ],
            ],
        ])->assertNoContent();

        $job = $this->pushedEvents('form_field_changed')[0];
        $meta = $job->payload['metadata'] ?? [];
        $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

        $this->assertSame('buyer_nip', $meta['field_key'] ?? null);
        $this->assertSame('invoice_buyer', $meta['section_key'] ?? null);
        $this->assertSame('manual', $meta['source'] ?? null);
        $this->assertTrue($meta['has_value'] ?? false);
        $this->assertSame(2, $meta['tracking_schema_version'] ?? null);
        $this->assertSame(42, $meta['seconds_from_page_load'] ?? null);
        $this->assertStringNotContainsString('1234567890', $encoded);
        $this->assertStringNotContainsString('sekret@example.pl', $encoded);
        $this->assertStringNotContainsString('Publiczna Szkoła', $encoded);
    }

    public function test_form_field_changed_for_email_phone_and_name_fields_strips_values(): void
    {
        $cases = [
            ['field_key' => 'contact_email', 'pii' => 'uczen@szkola.pl'],
            ['field_key' => 'contact_phone', 'pii' => '501654274'],
            ['field_key' => 'contact_name', 'pii' => 'Szkoła Podstawowa nr 1'],
        ];

        foreach ($cases as $case) {
            Queue::fake();

            $this->postEvents([
                'course_id' => self::COURSE_ID,
                'events' => [[
                    'event_name' => 'form_field_changed',
                    'section_key' => 'contact',
                    'field_key' => $case['field_key'],
                    'field_type' => 'text',
                    'source' => 'manual',
                    'has_value' => true,
                    'field_value' => $case['pii'],
                    $case['field_key'] => $case['pii'],
                ]],
            ])->assertNoContent();

            $encoded = json_encode($this->pushedEvents('form_field_changed')[0]->payload, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString($case['pii'], $encoded);
        }
    }

    public function test_form_last_activity_uses_last_activity_type_for_field_changed(): void
    {
        $this->postEvents([
            'course_id' => self::COURSE_ID,
            'events' => [[
                'event_name' => 'form_last_activity',
                'last_activity_type' => 'field_changed',
                'last_event_name' => 'form_field_changed',
                'last_section_key' => 'contact',
                'last_field_key' => 'contact_email',
                'completed_sections_count' => 2,
            ]],
        ])->assertNoContent();

        $meta = $this->pushedEvents('form_last_activity')[0]->payload['metadata'] ?? [];

        $this->assertSame('field_changed', $meta['last_activity_type'] ?? null);
        $this->assertSame('form_field_changed', $meta['last_event_name'] ?? null);
        $this->assertSame('contact', $meta['last_section_key'] ?? null);
        $this->assertSame('contact_email', $meta['last_field_key'] ?? null);
    }

    public function test_js_last_activity_uses_last_activity_type_not_misleading_event_name(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('last_activity_type', $source);
        $this->assertStringContainsString("scheduleLastActivity('field_changed'", $source);
        $this->assertStringNotContainsString("scheduleLastActivity(e.type === 'input' ? 'form_field_changed'", $source);
    }

    public function test_legacy_b1_b2_events_still_emitted_from_collector(): void
    {
        $source = $this->collectorSource();

        foreach (['order_form_started', 'order_form_section_interacted', 'order_form_cta_clicked', 'order_form_submit_clicked'] as $event) {
            $this->assertStringContainsString($event, $source);
        }
    }

    public function test_form_field_changed_is_emitted_from_collector(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString("enqueue('form_field_changed'", $source);
        $this->assertStringContainsString('FIELD_CHANGED_DEBOUNCE_MS', $source);
    }

    public function test_order_form_page_still_renders_successfully(): void
    {
        $html = $this->renderOrderForm();

        $this->assertStringContainsString('id="order-form-submit-btn"', $html);
        $this->assertStringContainsString('id="order-form-analytics-config"', $html);
        $this->assertStringContainsString('name="contact_email"', $html);
    }

    private function requiredTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('course_price_variants');
        } catch (\Throwable) {
            return false;
        }
    }
}
