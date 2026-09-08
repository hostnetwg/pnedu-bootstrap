<?php

namespace Tests\Feature;

use App\Jobs\Analytics\StoreAnalyticsEventJob;
use App\Services\Analytics\AnalyticsConsentService;
use App\Services\Analytics\AnalyticsSessionService;
use App\Services\Analytics\OrderFormSessionService;
use App\Services\MarketingAttributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AnalyticsConsentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        config([
            'consent.required' => true,
            'consent.cookie.name' => 'pne_cookie_consent',
            'consent.cookie.days' => 180,
            'analytics.enabled' => true,
            'analytics.default_mode' => 'standard',
            'analytics.sample_rate' => 100,
            'services.google_analytics.id' => 'G-CONSENTTEST',
            'services.google_tag_manager.id' => 'GTM-CONSENTTEST',
        ]);
    }

    public function test_google_tags_are_not_activated_before_consent_and_all_flags_default_to_denied(): void
    {
        $html = $this->renderAnalyticsHeadInProduction();

        $this->assertStringContainsString("analytics_storage: analyticsGranted ? 'granted' : 'denied'", $html);
        $this->assertStringContainsString("ad_storage: 'denied'", $html);
        $this->assertStringContainsString("ad_user_data: 'denied'", $html);
        $this->assertStringContainsString("ad_personalization: 'denied'", $html);
        $this->assertStringContainsString("window.gtag('consent', 'default', consentState(false));", $html);
        $this->assertStringNotContainsString('window.pneSetAnalyticsConsent(true);', $html);
        $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com/', $html);

        $body = $this->renderGtmBodyInProduction();
        $this->assertStringNotContainsString('googletagmanager.com/ns.html', $body);
    }

    public function test_saved_analytics_consent_activates_google_with_ad_flags_still_denied(): void
    {
        $cookies = ['pne_cookie_consent' => AnalyticsConsentService::ANALYTICS];

        $head = $this->renderAnalyticsHeadInProduction($cookies);
        $body = $this->renderGtmBodyInProduction($cookies);

        $this->assertStringContainsString('window.pneSetAnalyticsConsent(true);', $head);
        $this->assertStringContainsString("ad_storage: 'denied'", $head);
        $this->assertStringContainsString('GTM-CONSENTTEST', $head);
        $this->assertStringContainsString('G-CONSENTTEST', $head);
        $this->assertStringContainsString('googletagmanager.com/ns.html?id=GTM-CONSENTTEST', $body);
    }

    public function test_banner_has_binary_choices_cookie_settings_link_and_secure_cookie_policy(): void
    {
        $html = Blade::render("@include('layouts.footer') @include('layouts.cookie-consent') @stack('scripts')");

        $this->assertStringContainsString('Akceptuję analityczne', $html);
        $this->assertStringContainsString('Tylko niezbędne', $html);
        $this->assertStringContainsString('Ustawienia cookies', $html);
        $this->assertStringContainsString('data-cookie-settings', $html);
        $this->assertStringContainsString("'; Path=/; Max-Age='", $html);
        $this->assertStringContainsString("'; SameSite=Lax'", $html);
        $this->assertStringContainsString("cookie += '; Secure'", $html);
        $this->assertStringNotContainsString("localStorage.getItem('cookie_consent')", $html);
    }

    public function test_client_event_endpoint_is_silent_and_creates_no_identifiers_without_consent(): void
    {
        $response = $this->postJson(route('analytics.client-events.store'), $this->validClientBatch(), [
            'User-Agent' => $this->browserUserAgent(),
        ]);

        $response->assertNoContent();
        Queue::assertNotPushed(StoreAnalyticsEventJob::class);
        $response->assertCookieMissing(config('analytics.session.cookie'));
        $response->assertCookieMissing(config('analytics.order_form_session.cookie_prefix').'_123');
    }

    public function test_client_event_endpoint_tracks_after_analytics_consent(): void
    {
        $this->call(
            'POST',
            route('analytics.client-events.store'),
            $this->validClientBatch(),
            ['pne_cookie_consent' => AnalyticsConsentService::ANALYTICS],
            [],
            ['HTTP_USER_AGENT' => $this->browserUserAgent(), 'HTTP_ACCEPT' => 'application/json'],
        )->assertNoContent();

        Queue::assertPushed(StoreAnalyticsEventJob::class, function (StoreAnalyticsEventJob $job): bool {
            return ($job->payload['event_name'] ?? null) === 'order_form_started'
                && Str::isUuid($job->payload['analytics_session_id'] ?? '')
                && Str::isUuid($job->payload['order_form_session_id'] ?? '');
        });
    }

    public function test_first_party_analytics_and_marketing_ids_are_not_created_without_consent(): void
    {
        $request = Request::create('/courses/123?utm_source=newsletter&utm_campaign=autumn', 'GET');

        $this->assertNull(app(AnalyticsSessionService::class)->id($request));
        $this->assertNull(app(OrderFormSessionService::class)->id($request, 123));
        $this->assertSame([], app(MarketingAttributionService::class)->captureFromRequest($request));

        $response = new Response;
        app(AnalyticsSessionService::class)->appendCookie($response, $request);
        app(OrderFormSessionService::class)->appendCookie($response, $request, 123);

        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_order_form_collector_gates_ids_and_endpoint_calls_before_consent(): void
    {
        $source = file_get_contents(resource_path('views/courses/partials/order-form-client-tracking.blade.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("if (!analyticsAllowed()) { return; }\n                var ev =", $source);
        $this->assertStringContainsString("if (!analyticsAllowed()) {\n                    queue = [];", $source);
        $this->assertStringContainsString("typeof window.pneHasAnalyticsConsent === 'function'", $source);
    }

    /**
     * @param  array<string, string>  $cookies
     */
    private function renderAnalyticsHeadInProduction(array $cookies = []): string
    {
        $this->app['env'] = 'production';
        $request = request()->duplicate(cookies: $cookies);
        $this->app->instance('request', $request);

        return View::make('layouts.analytics-head')
            ->with('skipMarketingAnalytics', false)
            ->render();
    }

    /**
     * @param  array<string, string>  $cookies
     */
    private function renderGtmBodyInProduction(array $cookies = []): string
    {
        $this->app['env'] = 'production';
        $request = request()->duplicate(cookies: $cookies);
        $this->app->instance('request', $request);

        return View::make('layouts.google-tag-manager-body')
            ->with('skipMarketingAnalytics', false)
            ->render();
    }

    /**
     * @return array<string, mixed>
     */
    private function validClientBatch(): array
    {
        return [
            'course_id' => 123,
            'events' => [
                ['event_name' => 'order_form_started', 'trigger' => 'first_interaction'],
            ],
        ];
    }

    private function browserUserAgent(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36';
    }
}
