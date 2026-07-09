<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Etap B1 — testy kontraktu bezpieczeństwa publicznego endpointu JS analityki.
 *
 * Endpoint MUSI być fail-silent (zawsze 204), respektować tryby analityki,
 * i nigdy nie zapisywać PII ani wartości pól formularza.
 */
class AnalyticsClientEventsStageB1Test extends TestCase
{
    private const COURSE_ID = 999001;

    private const PRICE_VARIANT_ID = 78;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();

        config()->set('analytics.enabled', true);
        config()->set('analytics.default_mode', 'standard');
        config()->set('analytics.sample_rate', 100);
    }

    private function postEvents(array $body): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('analytics.client-events.store'), $body, [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
        ]);
    }

    private function validBatch(array $overrides = []): array
    {
        return array_merge([
            'course_id' => self::COURSE_ID,
            'price_variant_id' => self::PRICE_VARIANT_ID,
            'events' => [
                ['event_name' => 'order_form_started', 'trigger' => 'first_interaction'],
                ['event_name' => 'order_form_section_interacted', 'section_key' => 'buyer_data'],
                ['event_name' => 'order_form_cta_clicked', 'cta_key' => 'add_participant'],
                ['event_name' => 'order_form_submit_clicked'],
            ],
        ], $overrides);
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

    public function test_accepts_valid_batch_and_returns_204(): void
    {
        $this->postEvents($this->validBatch())->assertNoContent();

        $this->assertCount(1, $this->pushedEvents('order_form_started'));
        $this->assertCount(1, $this->pushedEvents('order_form_section_interacted'));
        $this->assertCount(1, $this->pushedEvents('order_form_cta_clicked'));
        $this->assertCount(1, $this->pushedEvents('order_form_submit_clicked'));
    }

    public function test_accepts_schema_v2_client_events_and_safe_properties(): void
    {
        $this->postEvents($this->validBatch([
            'events' => [
                [
                    'event_name' => 'form_first_interaction',
                    'first_interaction_type' => 'focus',
                    'first_section_key' => 'contact',
                    'first_field_key' => 'contact_email',
                    'seconds_from_page_load' => 12,
                ],
                [
                    'event_name' => 'form_section_completed',
                    'section_key' => 'invoice_buyer',
                    'required_fields_count' => 4,
                    'completed_fields_count' => 4,
                ],
                [
                    'event_name' => 'form_field_changed',
                    'section_key' => 'invoice_buyer',
                    'field_key' => 'buyer_nip',
                    'field_type' => 'text',
                    'source' => 'manual',
                    'has_value' => true,
                    'seconds_from_page_load' => 30,
                ],
                [
                    'event_name' => 'client_validation_failed',
                    'errors_count' => 2,
                    'error_sections' => ['contact', 'payment', 'not_allowed'],
                    'error_fields' => ['contact_email', 'payment_type', 'hacker_field'],
                    'validation_error_codes' => ['required', 'email', 'DROP TABLE users;'],
                ],
            ],
        ]))->assertNoContent();

        $this->assertCount(1, $this->pushedEvents('form_first_interaction'));
        $this->assertCount(1, $this->pushedEvents('form_section_completed'));
        $this->assertCount(1, $this->pushedEvents('form_field_changed'));
        $this->assertCount(1, $this->pushedEvents('client_validation_failed'));

        $fieldChanged = $this->pushedEvents('form_field_changed')[0]->payload['metadata'] ?? [];
        $this->assertSame('buyer_nip', $fieldChanged['field_key'] ?? null);
        $this->assertSame(true, $fieldChanged['has_value'] ?? null);
        $this->assertSame(30, $fieldChanged['seconds_from_page_load'] ?? null);

        $validation = $this->pushedEvents('client_validation_failed')[0]->payload['metadata'] ?? [];
        $this->assertSame(2, $validation['errors_count'] ?? null);
        $this->assertSame(['contact', 'payment'], $validation['error_sections'] ?? []);
        $this->assertSame(['contact_email', 'payment_type'], $validation['error_fields'] ?? []);
        $this->assertSame(['required', 'email'], $validation['validation_error_codes'] ?? []);
        $this->assertSame(2, $validation['tracking_schema_version'] ?? null);
    }

    public function test_session_and_course_context_present_in_payload(): void
    {
        $this->postEvents($this->validBatch())->assertNoContent();

        $job = $this->pushedEvents('order_form_started')[0];

        $this->assertSame(self::COURSE_ID, $job->payload['course_id'] ?? null);
        $this->assertTrue(Str::isUuid($job->payload['analytics_session_id'] ?? ''));
        $this->assertTrue(Str::isUuid($job->payload['order_form_session_id'] ?? ''));
        $this->assertSame('order_form', $job->payload['event_category'] ?? null);
        $this->assertSame(self::PRICE_VARIANT_ID, $job->payload['metadata']['price_variant_id'] ?? null);
        $this->assertSame($job->payload['order_form_session_id'], $job->payload['metadata']['form_session_id'] ?? null);
        $this->assertSame(2, $job->payload['metadata']['tracking_schema_version'] ?? null);
    }

    public function test_client_form_session_id_is_reused_as_order_form_session_id(): void
    {
        $formSessionId = (string) Str::uuid();

        $this->postEvents($this->validBatch([
            'form_session_id' => $formSessionId,
            'events' => [
                ['event_name' => 'form_visible', 'seconds_from_page_load' => 3],
            ],
        ]))->assertNoContent();

        $job = $this->pushedEvents('form_visible')[0];

        $this->assertSame($formSessionId, $job->payload['order_form_session_id'] ?? null);
        $this->assertSame($formSessionId, $job->payload['metadata']['form_session_id'] ?? null);
    }

    public function test_rejects_unknown_event_name_but_returns_204(): void
    {
        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_viewed'],          // tylko backend
                ['event_name' => 'form_order_created'],          // tylko backend
                ['event_name' => 'totally_made_up_event'],
                ['event_name' => 'order_form_started'],          // jedyny dozwolony
            ],
        ]))->assertNoContent();

        $this->assertCount(1, $this->pushedEvents());
        $this->assertCount(1, $this->pushedEvents('order_form_started'));
    }

    public function test_limits_number_of_events_per_batch(): void
    {
        config()->set('analytics.client_events.max_events_per_batch', 20);

        $events = [];
        for ($i = 0; $i < 30; $i++) {
            $events[] = ['event_name' => 'order_form_submit_clicked'];
        }

        $this->postEvents($this->validBatch(['events' => $events]))->assertNoContent();

        $this->assertCount(20, $this->pushedEvents('order_form_submit_clicked'));
    }

    public function test_limits_payload_size(): void
    {
        config()->set('analytics.client_events.max_payload_bytes', 10240);

        $this->postEvents($this->validBatch([
            'junk' => str_repeat('x', 11000),
        ]))->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_does_not_store_field_values_or_pii(): void
    {
        $this->postEvents($this->validBatch([
            'events' => [
                [
                    'event_name' => 'order_form_started',
                    'trigger' => 'first_interaction',
                    // próba przemycenia PII w eventie — musi zniknąć
                    'email' => 'secret@example.com',
                    'phone' => '501654274',
                    'nip' => '1234567890',
                    'buyer_name' => 'Jan Kowalski',
                    'buyer_address' => 'Testowa 1, Warszawa',
                    'field_value' => 'Publiczna Szkoła Testowa',
                    'metadata' => [
                        'email' => 'secret@example.com',
                        'first_name' => 'Jan',
                        'last_name' => 'Kowalski',
                        'nip' => '1234567890',
                        'raw_form_data' => 'cokolwiek',
                    ],
                ],
            ],
        ]))->assertNoContent();

        $job = $this->pushedEvents('order_form_started')[0];
        $encoded = json_encode($job->payload, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('secret@example.com', $encoded);
        $this->assertStringNotContainsString('501654274', $encoded);
        $this->assertStringNotContainsString('1234567890', $encoded);
        $this->assertStringNotContainsString('Kowalski', $encoded);
        $this->assertStringNotContainsString('Publiczna Szkoła Testowa', $encoded);
        $this->assertStringNotContainsString('raw_form_data', $encoded);

        $this->assertArrayNotHasKey('email', $job->payload['metadata'] ?? []);
        $this->assertArrayNotHasKey('nip', $job->payload['metadata'] ?? []);
    }

    public function test_section_event_with_non_whitelisted_key_is_dropped(): void
    {
        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_section_interacted', 'section_key' => 'hacker_injected_section'],
                ['event_name' => 'order_form_cta_clicked', 'cta_key' => 'definitely_not_allowed'],
            ],
        ]))->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_section_and_cta_keys_are_whitelisted_values(): void
    {
        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_section_interacted', 'section_key' => 'payment_method'],
                ['event_name' => 'order_form_cta_clicked', 'cta_key' => 'select_online_payment'],
            ],
        ]))->assertNoContent();

        $section = $this->pushedEvents('order_form_section_interacted')[0];
        $cta = $this->pushedEvents('order_form_cta_clicked')[0];

        $this->assertSame('payment_method', $section->payload['metadata']['section_key'] ?? null);
        $this->assertSame('select_online_payment', $cta->payload['metadata']['cta_key'] ?? null);
    }

    public function test_fail_silent_on_missing_course_id(): void
    {
        $this->postEvents([
            'events' => [['event_name' => 'order_form_started']],
        ])->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_fail_silent_on_empty_events(): void
    {
        $this->postEvents(['course_id' => self::COURSE_ID, 'events' => []])->assertNoContent();
        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_hard_kill_switch_blocks_everything(): void
    {
        config()->set('analytics.enabled', false);

        $this->postEvents($this->validBatch())->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_off_mode_blocks_js_events(): void
    {
        config()->set('analytics.default_mode', 'off');

        $this->postEvents($this->validBatch())->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_aggregate_only_mode_blocks_js_events(): void
    {
        config()->set('analytics.default_mode', 'aggregate_only');

        $this->postEvents($this->validBatch())->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_standard_mode_allows_all_mvp_events(): void
    {
        config()->set('analytics.default_mode', 'standard');

        $this->postEvents($this->validBatch())->assertNoContent();

        $this->assertCount(4, $this->pushedEvents());
    }

    public function test_light_mode_allows_only_started_and_submit_clicked(): void
    {
        config()->set('analytics.default_mode', 'light');

        $this->postEvents($this->validBatch())->assertNoContent();

        $this->assertCount(1, $this->pushedEvents('order_form_started'));
        $this->assertCount(1, $this->pushedEvents('order_form_submit_clicked'));
        $this->assertCount(0, $this->pushedEvents('order_form_section_interacted'));
        $this->assertCount(0, $this->pushedEvents('order_form_cta_clicked'));
    }

    public function test_internal_admin_traffic_is_skipped(): void
    {
        $cookieName = app(\App\Services\FunnelSkipService::class)->analyticsCookieName();

        // Cookie pne_skip_analytics jest na liście wyjątków szyfrowania (czytane jako plaintext).
        // postJson/withUnencryptedCookie nie przenoszą cookie w tej wersji frameworka,
        // więc używamy niskopoziomowego call() z surowym cookie.
        $this->call(
            'POST',
            route('analytics.client-events.store'),
            [],
            [$cookieName => '1'],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
            ],
            json_encode($this->validBatch(), JSON_THROW_ON_ERROR),
        )->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_bot_user_agent_is_skipped(): void
    {
        $this->postJson(route('analytics.client-events.store'), $this->validBatch(), [
            'User-Agent' => 'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)',
        ])->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_valid_client_uuid_is_namespaced_into_server_event_uuid(): void
    {
        $clientUuid = (string) Str::uuid();

        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_started', 'event_uuid' => $clientUuid],
            ],
        ]))->assertNoContent();

        $eventUuid = $this->pushedEvents('order_form_started')[0]->payload['event_uuid'] ?? null;

        // Klientowski UUID NIE jest finalnym event_uuid — serwer go namespacuje (UUIDv5, max 36 znaków).
        $this->assertTrue(Str::isUuid((string) $eventUuid));
        $this->assertNotSame($clientUuid, $eventUuid);
        $this->assertLessThanOrEqual(36, strlen((string) $eventUuid));
    }

    public function test_same_client_uuid_same_session_same_event_dedups_to_one_uuid(): void
    {
        $clientUuid = (string) Str::uuid();

        // Te same eventy w jednym batchu => ta sama sesja formularza => ten sam namespacowany event_uuid.
        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_started', 'event_uuid' => $clientUuid],
                ['event_name' => 'order_form_started', 'event_uuid' => $clientUuid],
            ],
        ]))->assertNoContent();

        $jobs = $this->pushedEvents('order_form_started');
        $this->assertCount(2, $jobs);
        $this->assertSame(
            $jobs[0]->payload['event_uuid'] ?? null,
            $jobs[1]->payload['event_uuid'] ?? null,
        );
    }

    public function test_same_client_uuid_different_session_does_not_collide(): void
    {
        $clientUuid = (string) Str::uuid();

        // Dwa osobne żądania => dwie różne sesje formularza (losowy order_form_session_id).
        $this->postEvents($this->validBatch([
            'events' => [['event_name' => 'order_form_started', 'event_uuid' => $clientUuid]],
        ]))->assertNoContent();

        $this->postEvents($this->validBatch([
            'events' => [['event_name' => 'order_form_started', 'event_uuid' => $clientUuid]],
        ]))->assertNoContent();

        $jobs = $this->pushedEvents('order_form_started');
        $this->assertCount(2, $jobs);
        $this->assertNotSame(
            $jobs[0]->payload['event_uuid'] ?? null,
            $jobs[1]->payload['event_uuid'] ?? null,
        );
    }

    public function test_same_client_uuid_different_event_name_does_not_collide(): void
    {
        $clientUuid = (string) Str::uuid();

        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_started', 'event_uuid' => $clientUuid],
                ['event_name' => 'order_form_submit_clicked', 'event_uuid' => $clientUuid],
            ],
        ]))->assertNoContent();

        $started = $this->pushedEvents('order_form_started')[0]->payload['event_uuid'] ?? null;
        $submit = $this->pushedEvents('order_form_submit_clicked')[0]->payload['event_uuid'] ?? null;

        $this->assertNotSame($started, $submit);
    }

    public function test_invalid_client_uuid_is_replaced_by_server_uuid(): void
    {
        $this->postEvents($this->validBatch([
            'events' => [
                ['event_name' => 'order_form_started', 'event_uuid' => 'not-a-valid-uuid'],
            ],
        ]))->assertNoContent();

        $eventUuid = $this->pushedEvents('order_form_started')[0]->payload['event_uuid'] ?? null;
        $this->assertTrue(Str::isUuid((string) $eventUuid));
        $this->assertNotSame('not-a-valid-uuid', $eventUuid);
    }

    private function appHost(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    public function test_request_with_matching_origin_is_tracked(): void
    {
        $this->postJson(route('analytics.client-events.store'), $this->validBatch([
            'events' => [['event_name' => 'order_form_submit_clicked']],
        ]), [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
            'Origin' => 'https://'.$this->appHost(),
        ])->assertNoContent();

        $this->assertCount(1, $this->pushedEvents('order_form_submit_clicked'));
    }

    public function test_request_with_matching_referer_is_tracked(): void
    {
        $this->postJson(route('analytics.client-events.store'), $this->validBatch([
            'events' => [['event_name' => 'order_form_started']],
        ]), [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
            'Referer' => 'https://'.$this->appHost().'/courses/'.self::COURSE_ID.'/order-form',
        ])->assertNoContent();

        $this->assertCount(1, $this->pushedEvents('order_form_started'));
    }

    public function test_request_with_foreign_origin_is_rejected(): void
    {
        $this->postJson(route('analytics.client-events.store'), $this->validBatch(), [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
            'Origin' => 'https://evil.example.test',
        ])->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_request_with_foreign_referer_is_rejected(): void
    {
        $this->postJson(route('analytics.client-events.store'), $this->validBatch(), [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
            'Referer' => 'https://evil.example.test/phishing',
        ])->assertNoContent();

        $this->assertCount(0, $this->pushedEvents());
    }

    public function test_request_without_origin_and_referer_still_works(): void
    {
        // postEvents nie ustawia Origin ani Referer — best-effort, nie blokujemy.
        $this->postEvents($this->validBatch([
            'events' => [['event_name' => 'order_form_started']],
        ]))->assertNoContent();

        $this->assertCount(1, $this->pushedEvents('order_form_started'));
    }
}
