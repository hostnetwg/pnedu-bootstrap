<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Etap B2 — testy obecności i bezpieczeństwa JS collectora na stronie formularza zamówienia.
 *
 * Sprawdzamy render widoku (PHP/Feature): config JS, whitelisty data-analytics-*,
 * brak PII w configu, brak collectora poza formularzem.
 */
class AnalyticsOrderFormClientTrackingStageB2Test extends TestCase
{
    private const ALLOWED_SECTIONS = [
        'buyer_data', 'recipient_data', 'participants', 'payment_method', 'invoice', 'consents', 'summary',
    ];

    private const ALLOWED_CTAS = [
        'add_participant', 'remove_participant', 'select_online_payment',
        'select_deferred_invoice', 'back_to_course', 'submit_order',
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
        $html = $this->get(route('payment.order-form', $this->activeCourseId()))
            ->assertStatus(200)
            ->getContent();

        return (string) $html;
    }

    public function test_collector_is_present_on_order_form_page(): void
    {
        $html = $this->renderOrderForm();

        $this->assertStringContainsString('id="order-form-analytics-config"', $html);
        $this->assertStringContainsString('order_form_started', $html);
        $this->assertStringContainsString('order_form_submit_clicked', $html);
    }

    public function test_config_contains_endpoint_and_course_id(): void
    {
        $courseId = $this->activeCourseId();
        $html = $this->get(route('payment.order-form', $courseId))->assertStatus(200)->getContent();

        $this->assertStringContainsString('data-endpoint="'.route('analytics.client-events.store').'"', $html);
        $this->assertStringContainsString('data-course-id="'.$courseId.'"', $html);
        $this->assertStringContainsString('data-max-batch="', $html);
    }

    public function test_section_data_attributes_are_whitelisted(): void
    {
        $html = $this->renderOrderForm();

        preg_match_all('/data-analytics-section="([^"]+)"/', $html, $matches);

        $this->assertNotEmpty($matches[1], 'Brak oznaczonych sekcji formularza.');
        foreach ($matches[1] as $sectionKey) {
            $this->assertContains($sectionKey, self::ALLOWED_SECTIONS, "Sekcja spoza whitelisty: {$sectionKey}");
        }
    }

    public function test_cta_data_attributes_are_whitelisted(): void
    {
        $html = $this->renderOrderForm();

        preg_match_all('/data-analytics-cta="([^"]+)"/', $html, $matches);

        $this->assertNotEmpty($matches[1], 'Brak oznaczonych CTA formularza.');
        foreach ($matches[1] as $ctaKey) {
            $this->assertContains($ctaKey, self::ALLOWED_CTAS, "CTA spoza whitelisty: {$ctaKey}");
        }
    }

    public function test_config_element_contains_no_pii(): void
    {
        $html = $this->renderOrderForm();

        $this->assertSame(1, preg_match('/<div id="order-form-analytics-config"[^>]*>/', $html, $configMatch));
        $config = $configMatch[0];

        // Config może zawierać wyłącznie bezpieczne klucze techniczne.
        $this->assertStringNotContainsString('@', $config);
        foreach (['email', 'phone', 'nip', 'buyer', 'recipient', 'address', 'participant', 'name'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $config, "Config JS zawiera podejrzany klucz: {$forbidden}");
        }
    }

    public function test_order_form_still_renders_with_form(): void
    {
        $courseId = $this->activeCourseId();
        $html = $this->get(route('payment.order-form', $courseId))->assertStatus(200)->getContent();

        $this->assertStringContainsString('action="'.route('payment.order-form.store', $courseId).'"', $html);
        $this->assertStringContainsString('id="order-form-submit-btn"', $html);
    }

    public function test_collector_is_absent_on_non_form_page(): void
    {
        $html = $this->get(route('home'))->assertStatus(200)->getContent();

        $this->assertStringNotContainsString('id="order-form-analytics-config"', $html);
    }

    public function test_collector_absent_when_analytics_hard_disabled(): void
    {
        config()->set('analytics.enabled', false);

        $html = $this->get(route('payment.order-form', $this->activeCourseId()))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringNotContainsString('id="order-form-analytics-config"', $html);
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
