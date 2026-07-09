<?php

namespace Tests\Unit;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Models\Analytics\OrderFormAttribution;
use App\Services\Analytics\AnalyticsPayloadSanitizer;
use App\Services\Analytics\OrderFormAttributionService;
use App\Services\Analytics\TrafficChannelClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsTrafficChannelStage2FTest extends TestCase
{
    private TrafficChannelClassifier $classifier;

    private OrderFormAttributionService $attribution;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config()->set('analytics.enabled', true);
        config()->set('analytics.traffic.internal_hosts', ['pnedu.pl', 'localhost']);

        $this->classifier = new TrafficChannelClassifier;
        $this->attribution = app(OrderFormAttributionService::class);
    }

    public function test_newsletter_utm_classifies_as_newsletter(): void
    {
        $result = $this->classifier->classify([
            'utm_source' => 'sendy',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring-2026',
        ]);

        $this->assertSame('newsletter', $result['channel']);
        $this->assertSame('sendy', $result['source']);
        $this->assertSame('email', $result['medium']);
    }

    public function test_facebook_ads_utm_classifies_as_paid_social(): void
    {
        $result = $this->classifier->classify([
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'retargeting',
        ]);

        $this->assertSame('paid_social', $result['channel']);
    }

    public function test_facebook_fbclid_with_referrer_classifies_as_paid_social(): void
    {
        $result = $this->classifier->classify([
            'referrer_domain' => 'facebook.com',
            'fbclid_present' => true,
        ]);

        $this->assertSame('paid_social', $result['channel']);
    }

    public function test_google_organic_referrer_classifies_as_organic_search(): void
    {
        $result = $this->classifier->classify([
            'referrer_domain' => 'www.google.pl',
        ]);

        $this->assertSame('organic_search', $result['channel']);
        $this->assertSame('google', $result['source']);
        $this->assertSame('organic', $result['medium']);
    }

    public function test_bing_organic_referrer_classifies_as_organic_search(): void
    {
        $result = $this->classifier->classify([
            'referrer_domain' => 'www.bing.com',
        ]);

        $this->assertSame('organic_search', $result['channel']);
        $this->assertSame('bing', $result['source']);
    }

    public function test_no_utm_and_no_referrer_classifies_as_direct(): void
    {
        $result = $this->classifier->classify([]);

        $this->assertSame('direct', $result['channel']);
        $this->assertSame('direct', $result['source']);
        $this->assertSame('none', $result['medium']);
    }

    public function test_external_non_search_referrer_classifies_as_referral(): void
    {
        $result = $this->classifier->classify([
            'referrer_domain' => 'partner-school.example',
        ]);

        $this->assertSame('referral', $result['channel']);
        $this->assertSame('partner-school.example', $result['source']);
    }

    public function test_pnedu_referrer_classifies_as_internal_site(): void
    {
        $result = $this->classifier->classify([
            'referrer_domain' => 'pnedu.pl',
            'is_internal_referrer' => true,
        ]);

        $this->assertSame('internal_site', $result['channel']);
    }

    public function test_organic_social_without_paid_signals_classifies_as_organic_social(): void
    {
        $result = $this->classifier->classify([
            'referrer_domain' => 'l.facebook.com',
        ]);

        $this->assertSame('organic_social', $result['channel']);
        $this->assertSame('social', $result['medium']);
    }

    public function test_gclid_classifies_as_paid_search(): void
    {
        $result = $this->classifier->classify([
            'gclid_present' => true,
        ]);

        $this->assertSame('paid_search', $result['channel']);
    }

    public function test_first_touch_is_not_overwritten_by_later_direct(): void
    {
        $session = $this->createAttributionSession();

        $this->capture($session, $this->requestWithUtm('sendy', 'email', 'nl-01'));
        $first = $session->get(OrderFormAttributionService::SESSION_KEY)['first']['channel'] ?? null;
        $this->assertSame('newsletter', $first);

        $this->capture($session, Request::create('/courses/1/order-form', 'GET'));
        $state = $session->get(OrderFormAttributionService::SESSION_KEY);

        $this->assertSame('newsletter', $state['first']['channel']);
        $this->assertSame('direct', $state['current']['channel']);
    }

    public function test_first_touch_is_not_overwritten_by_internal_site(): void
    {
        $session = $this->createAttributionSession();

        $this->capture($session, $this->requestWithUtm('sendy', 'email', 'nl-01'));
        $this->capture($session, $this->internalRequest('/courses/1/order-form'));

        $state = $session->get(OrderFormAttributionService::SESSION_KEY);
        $this->assertSame('newsletter', $state['first']['channel']);
        $this->assertSame('internal_site', $state['current']['channel']);
    }

    public function test_last_external_touch_is_not_overwritten_by_internal_site(): void
    {
        $session = $this->createAttributionSession();

        $this->capture($session, $this->requestWithUtm('sendy', 'email', 'nl-01'));
        $this->capture($session, $this->internalRequest('/courses/1/order-form'));

        $state = $session->get(OrderFormAttributionService::SESSION_KEY);
        $this->assertSame('newsletter', $state['last_external']['channel']);
    }

    public function test_internal_touch_sets_internal_promo_touched(): void
    {
        $session = $this->createAttributionSession();
        $this->capture($session, $this->internalRequest('/courses/1/order-form'));

        $state = $session->get(OrderFormAttributionService::SESSION_KEY);
        $this->assertTrue($state['internal_promo_touched']);
        $this->assertSame('internal_site', $state['current']['channel']);
    }

    public function test_conversion_reporting_channel_uses_last_external_touch(): void
    {
        $session = $this->createAttributionSession();
        $this->capture($session, $this->requestWithUtm('sendy', 'email', 'nl-01'));
        $this->capture($session, $this->internalRequest('/courses/1/order-form'));

        $reporting = $session->get(OrderFormAttributionService::SESSION_KEY)['reporting'];
        $this->assertSame('newsletter', $reporting['conversion_reporting_channel']);
        $this->assertSame('newsletter', $reporting['traffic_channel']);
    }

    public function test_conversion_reporting_channel_falls_back_to_current_channel(): void
    {
        $session = $this->createAttributionSession();
        $this->capture($session, Request::create('/courses/1/order-form', 'GET'));

        $reporting = $session->get(OrderFormAttributionService::SESSION_KEY)['reporting'];
        $this->assertSame('direct', $reporting['conversion_reporting_channel']);
    }

    public function test_course_description_utm_survives_navigation_to_form_without_utm(): void
    {
        $session = $this->createAttributionSession();

        $courseShow = $this->requestWithUtm('sendy', 'email', 'nl-02', '/courses/10');
        $this->capture($session, $courseShow);
        app(\App\Services\MarketingAttributionService::class)->persist($courseShow, [
            'utm_source' => 'sendy',
            'utm_medium' => 'email',
            'campaign_code' => 'nl-02',
        ]);

        $formRequest = $this->internalRequest('/courses/10/order-form');
        $this->capture($session, $formRequest);

        $state = $session->get(OrderFormAttributionService::SESSION_KEY);
        $this->assertSame('newsletter', $state['first']['channel']);
        $this->assertSame('newsletter', $state['last_external']['channel']);
        $this->assertSame('newsletter', $state['reporting']['traffic_channel']);
    }

    public function test_sanitizer_does_not_store_full_fbclid_or_gclid(): void
    {
        $sanitizer = new AnalyticsPayloadSanitizer;
        $sanitized = $sanitizer->sanitize([
            'event_name' => 'order_form_viewed',
            'event_category' => 'order_form',
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->toIso8601String(),
            'metadata' => [
                'fbclid' => 'secret-click-id',
                'gclid' => 'secret-gclid',
                'fbclid_present' => true,
                'gclid_present' => false,
            ],
        ]);

        $this->assertArrayNotHasKey('fbclid', $sanitized['metadata'] ?? []);
        $this->assertArrayNotHasKey('gclid', $sanitized['metadata'] ?? []);
        $this->assertTrue($sanitized['metadata']['fbclid_present']);
    }

    public function test_sanitizer_removes_forbidden_form_field_properties(): void
    {
        $sanitizer = new AnalyticsPayloadSanitizer;
        $sanitized = $sanitizer->sanitize([
            'event_name' => 'form_visible',
            'event_category' => 'order_form',
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->toIso8601String(),
            'metadata' => [
                'traffic_channel' => 'newsletter',
                'buyer_nip' => '1234567890',
                'email' => 'secret@example.com',
            ],
        ]);

        $this->assertSame('newsletter', $sanitized['metadata']['traffic_channel'] ?? null);
        $this->assertArrayNotHasKey('buyer_nip', $sanitized['metadata'] ?? []);
        $this->assertArrayNotHasKey('email', $sanitized['metadata'] ?? []);
    }

    public function test_order_form_viewed_persists_attribution_snapshot(): void
    {
        if (! \Illuminate\Support\Facades\Schema::connection('analytics')->hasTable('order_form_attributions')) {
            $this->markTestSkipped('Brak tabeli order_form_attributions — uruchom migrację w pneadm.');
        }

        $session = $this->createAttributionSession();
        $request = $this->requestWithUtm('sendy', 'email', 'nl-persist', '/courses/1/order-form');
        $request->setLaravelSession($session);
        app(\App\Services\MarketingAttributionService::class)->persist($request, [
            'utm_source' => 'sendy',
            'utm_medium' => 'email',
            'campaign_code' => 'nl-persist',
        ]);

        $formSessionId = (string) Str::uuid();
        $record = $this->attribution->persistForFormSession($request, $formSessionId, 1);

        $this->assertInstanceOf(OrderFormAttribution::class, $record);
        $this->assertSame('newsletter', $record->traffic_channel);
        $this->assertSame('newsletter', $record->first_touch_channel);
        $this->assertSame('newsletter', $record->conversion_reporting_channel);
    }

    public function test_client_form_events_include_traffic_metadata(): void
    {
        $session = $this->createAttributionSession();
        $this->capture($session, $this->requestWithUtm('facebook', 'cpc', 'fb-ads'));
        $this->withSession($session->all());

        $this->postJson(route('analytics.client-events.store'), [
            'course_id' => 1,
            'form_session_id' => (string) Str::uuid(),
            'events' => [[
                'event_name' => 'form_visible',
                'event_uuid' => (string) Str::uuid(),
            ]],
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
        ])->assertNoContent();

        $jobs = Queue::pushed(StoreAnalyticsEventJob::class);
        $this->assertNotEmpty($jobs);
        $metadata = $jobs[0]->payload['metadata'] ?? [];
        $this->assertSame('paid_social', $metadata['traffic_channel'] ?? null);
    }

    public function test_gus_event_still_accepted_with_traffic_metadata(): void
    {
        $session = $this->createAttributionSession();
        $this->withSession($session->all());

        $this->postJson(route('analytics.client-events.store'), [
            'course_id' => 1,
            'form_session_id' => (string) Str::uuid(),
            'events' => [[
                'event_name' => 'gus_lookup_clicked',
                'event_uuid' => (string) Str::uuid(),
                'target' => 'buyer',
                'section_key' => 'invoice_buyer',
                'nip_present' => true,
                'nip_format_valid_client' => true,
            ]],
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
        ])->assertNoContent();

        $jobs = Queue::pushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === 'gus_lookup_clicked');
        $this->assertCount(1, $jobs);
    }

    public function test_legacy_order_form_started_still_accepted_with_traffic_metadata(): void
    {
        $session = $this->createAttributionSession();
        $this->capture($session, $this->requestWithUtm('sendy', 'email', 'legacy-nl'));
        $this->withSession($session->all());

        $this->postJson(route('analytics.client-events.store'), [
            'course_id' => 1,
            'form_session_id' => (string) Str::uuid(),
            'events' => [[
                'event_name' => 'order_form_started',
                'event_uuid' => (string) Str::uuid(),
                'trigger' => 'first_interaction',
                'first_interaction_type' => 'focus',
                'first_section_key' => 'contact',
            ]],
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0 Safari/537.36',
        ])->assertNoContent();

        $jobs = Queue::pushed(StoreAnalyticsEventJob::class, fn (StoreAnalyticsEventJob $job): bool => ($job->payload['event_name'] ?? null) === 'order_form_started');
        $this->assertCount(1, $jobs);
        $this->assertSame('newsletter', $jobs[0]->payload['metadata']['traffic_channel'] ?? null);
    }

    public function test_order_created_snapshot_reads_persisted_attribution(): void
    {
        if (! \Illuminate\Support\Facades\Schema::connection('analytics')->hasTable('order_form_attributions')) {
            $this->markTestSkipped('Brak tabeli order_form_attributions.');
        }

        $formSessionId = (string) Str::uuid();
        OrderFormAttribution::query()->create([
            'form_session_id' => $formSessionId,
            'course_id' => 1,
            'traffic_channel' => 'newsletter',
            'traffic_source' => 'sendy',
            'traffic_medium' => 'email',
            'traffic_campaign' => 'nl-db',
            'attribution_source' => 'utm',
            'conversion_reporting_channel' => 'newsletter',
            'first_touch_channel' => 'newsletter',
            'last_external_touch_channel' => 'newsletter',
            'internal_promo_touched' => true,
            'tracking_schema_version' => 2,
        ]);

        $snapshot = $this->attribution->orderCreatedSnapshot($formSessionId, Request::create('/'));

        $this->assertSame('newsletter', $snapshot['traffic_channel']);
        $this->assertSame('newsletter', $snapshot['conversion_reporting_channel']);
        $this->assertTrue($snapshot['internal_promo_touched']);
    }

    public function test_msclkid_value_is_stripped_by_sanitizer(): void
    {
        $sanitizer = new AnalyticsPayloadSanitizer;
        $sanitized = $sanitizer->sanitize([
            'event_name' => 'order_form_viewed',
            'event_category' => 'order_form',
            'event_uuid' => (string) Str::uuid(),
            'occurred_at' => now()->toIso8601String(),
            'metadata' => [
                'msclkid' => 'secret-msclkid',
                'msclkid_present' => true,
            ],
        ]);

        $this->assertArrayNotHasKey('msclkid', $sanitized['metadata'] ?? []);
        $this->assertTrue($sanitized['metadata']['msclkid_present']);
    }

    protected function createAttributionSession(): \Illuminate\Session\Store
    {
        $session = app('session.store');
        $session->start();

        return $session;
    }

    private function capture(\Illuminate\Session\Store $session, Request $request): void
    {
        $request->setLaravelSession($session);
        $this->attribution->captureFromRequest($request);
    }

    private function requestWithUtm(string $source, string $medium, string $campaign, string $path = '/courses/1/order-form'): Request
    {
        return Request::create($path, 'GET', [
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
        ]);
    }

    private function internalRequest(string $path): Request
    {
        return Request::create($path, 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://pnedu.pl/courses/1',
        ]);
    }
}
