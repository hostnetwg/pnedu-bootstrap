<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Etap 2C — testy kontraktu JS collectora schema v2 na formularzu zamówienia.
 *
 * Weryfikujemy źródło skryptu (blade partial) oraz render strony — bez przeglądarki.
 */
class AnalyticsOrderFormClientTrackingV2Test extends TestCase
{
    private const V2_EVENTS = [
        'form_visible',
        'form_first_interaction',
        'form_section_viewed',
        'form_section_started',
        'form_section_completed',
        'form_submit_clicked',
        'client_validation_failed',
        'form_last_activity',
    ];

    private const LEGACY_EVENTS = [
        'order_form_started',
        'order_form_section_interacted',
        'order_form_cta_clicked',
        'order_form_submit_clicked',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config()->set('analytics.enabled', true);

        if (! $this->requiredTablesAvailable()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm w środowisku testowym.');
        }
    }

    private function collectorSource(): string
    {
        $path = resource_path('views/courses/partials/order-form-client-tracking.blade.php');

        return (string) file_get_contents($path);
    }

    private function activeCourseId(): int
    {
        $course = Course::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($course === null) {
            $this->markTestSkipped('Brak aktywnego kursu do wyrenderowania formularza zamówienia.');
        }

        return (int) $course->id;
    }

    private function renderOrderForm(): string
    {
        return (string) $this->get(route('payment.order-form', $this->activeCourseId()))
            ->assertStatus(200)
            ->getContent();
    }

    public function test_render_contains_form_session_id(): void
    {
        $html = $this->renderOrderForm();

        $this->assertStringContainsString('data-form-session-id="', $html);
        $this->assertSame(1, preg_match('/data-form-session-id="([^"]+)"/', $html, $match));
        $this->assertTrue(Str::isUuid($match[1]));
    }

    public function test_script_reads_form_session_id_from_config(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString("cfgEl.getAttribute('data-form-session-id')", $source);
        $this->assertStringContainsString('payload.form_session_id = formSessionId', $source);
    }

    public function test_form_visible_emits_only_once_per_session(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('form_visible', $source);
        $this->assertStringContainsString('formVisibleSent', $source);
        $this->assertStringContainsString('if (formVisibleSent)', $source);
        $this->assertStringContainsString('IntersectionObserver', $source);
    }

    public function test_form_first_interaction_emits_only_once(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('form_first_interaction', $source);
        $this->assertStringContainsString('firstInteractionSent', $source);
        $this->assertStringContainsString('first_interaction_type', $source);
        $this->assertStringContainsString('first_section_key', $source);
        $this->assertStringContainsString('first_field_key', $source);
        $this->assertStringContainsString('seconds_from_page_load', $source);
    }

    public function test_form_section_viewed_emits_once_per_section(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('form_section_viewed', $source);
        $this->assertStringContainsString('sectionsViewedSent', $source);
        foreach (['contact', 'invoice_buyer', 'invoice_recipient', 'participants', 'payment', 'submit'] as $section) {
            $this->assertStringContainsString("'{$section}'", $source);
        }
    }

    public function test_form_section_started_emits_once_per_section(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('form_section_started', $source);
        $this->assertStringContainsString('sectionsStartedSent', $source);
        $this->assertStringContainsString('markSectionStarted', $source);
    }

    public function test_form_submit_clicked_sends_metadata_without_field_values(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('form_submit_clicked', $source);
        $this->assertStringContainsString('completed_sections_count', $source);
        $this->assertStringContainsString('visible_validation_errors_count', $source);
        $this->assertStringContainsString('selected_payment_method', $source);

        // Payload enqueue nie może zawierać wartości pól — tylko liczniki/klucze techniczne.
        $this->assertDoesNotMatchRegularExpression(
            "/enqueue\([^)]*\.value/",
            $source,
            'Collector nie powinien wysyłać wartości pól w enqueue().'
        );
    }

    public function test_client_validation_failed_does_not_send_field_values_or_pii(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('client_validation_failed', $source);
        $this->assertStringContainsString('validation_error_codes', $source);
        $this->assertStringContainsString('error_fields', $source);
        $this->assertStringContainsString('fieldKeyForElement', $source);

        // Wartości pól mogą być czytane lokalnie (kompletność sekcji), ale nie trafiają do enqueue.
        $this->assertDoesNotMatchRegularExpression(
            "/enqueue\([^)]*\.value/",
            $source,
            'Collector nie powinien wysyłać wartości pól w enqueue().'
        );
        $this->assertStringNotContainsString('FormData', $source);
        $this->assertStringNotContainsString('innerText', $source);
    }

    public function test_missing_form_session_id_does_not_break_collector(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString("var formSessionId = cfgEl.getAttribute('data-form-session-id') || null;", $source);
        $this->assertStringContainsString('if (formSessionId)', $source);

        // Brak form_session_id nie powoduje wczesnego return — formularz działa dalej.
        $this->assertStringNotContainsString('if (!formSessionId)', $source);
    }

    public function test_legacy_events_still_present(): void
    {
        $source = $this->collectorSource();
        $html = $this->renderOrderForm();

        foreach (self::LEGACY_EVENTS as $event) {
            $this->assertStringContainsString($event, $source, "Brak legacy eventu: {$event}");
            $this->assertStringContainsString($event, $html, "Strona nie zawiera legacy eventu: {$event}");
        }
    }

    public function test_v2_events_present_in_collector(): void
    {
        $source = $this->collectorSource();
        $html = $this->renderOrderForm();

        foreach (self::V2_EVENTS as $event) {
            $this->assertStringContainsString($event, $source, "Brak v2 eventu w collectorze: {$event}");
        }

        $this->assertStringContainsString('data-tracking-schema-version="2"', $html);
    }

    public function test_form_last_activity_is_throttled(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString('form_last_activity', $source);
        $this->assertStringContainsString('LAST_ACTIVITY_THROTTLE_MS', $source);
        $this->assertStringContainsString('scheduleLastActivity', $source);
    }

    public function test_form_field_changed_is_emitted_once_per_field_key(): void
    {
        $source = $this->collectorSource();

        $this->assertStringContainsString("enqueue('form_field_changed'", $source);
        $this->assertStringContainsString('fieldsChangedSent', $source);
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
