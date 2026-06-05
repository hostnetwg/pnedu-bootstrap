<?php

namespace Tests\Feature;

use App\Jobs\SubscribeCertificateRegistrationNewsletterJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CertificateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-registration-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pneadm.api_url' => 'https://pneadm.test',
            'services.pneadm.api_token' => 'test-api-token',
            'services.pneadm.timeout' => 5,
            'services.certificate_registration.status_cache_ttl' => 60,
        ]);

        Cache::flush();
    }

    /** @return array<string, mixed> */
    private function activeStatusPayload(): array
    {
        return [
            'active' => true,
            'course_title' => 'Webinar TIK',
            'course_start_display' => '26.05.2026 20:00',
            'certificate_registration_ends_at_display' => '05.06.2026 23:59',
            'instructor_name' => 'Jan Kowalski',
            'certificate_registration_collect_birth_data' => false,
            'certificate_registration_birth_data_required' => false,
        ];
    }

    private function fakeStatusAndRegister(): void
    {
        Http::fake([
            'https://pneadm.test/api/certificate-registration/status/*' => Http::response(
                $this->activeStatusPayload(),
                200,
            ),
            'https://pneadm.test/api/certificate-registration/register' => Http::response([
                'success' => true,
                'updated' => false,
            ], 200),
        ]);
    }

    public function test_show_caches_status_so_repeated_views_hit_api_once(): void
    {
        Http::fake([
            'https://pneadm.test/api/certificate-registration/status/*' => Http::response(
                $this->activeStatusPayload(),
                200,
            ),
        ]);

        $this->get(route('certificate-registration.show', self::TOKEN))->assertOk();
        $this->get(route('certificate-registration.show', self::TOKEN))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_submit_reuses_cached_status_without_second_api_call(): void
    {
        $this->fakeStatusAndRegister();

        $this->get(route('certificate-registration.show', self::TOKEN))->assertOk();

        $this->post(route('certificate-registration.submit', self::TOKEN), [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.com',
            'rodo_consent' => '1',
        ])->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu', false)
            ->assertSee('Rejestracja zaświadczenia', false);

        $statusCalls = 0;
        $registerCalls = 0;

        Http::assertSent(function ($request) use (&$statusCalls, &$registerCalls) {
            if (str_contains($request->url(), '/certificate-registration/status/')) {
                $statusCalls++;
            }
            if (str_contains($request->url(), '/certificate-registration/register')) {
                $registerCalls++;
            }

            return true;
        });

        $this->assertSame(1, $statusCalls);
        $this->assertSame(1, $registerCalls);
    }

    public function test_submit_dispatches_newsletter_job_when_consent_given(): void
    {
        Queue::fake();
        $this->fakeStatusAndRegister();

        $this->get(route('certificate-registration.show', self::TOKEN));

        $this->post(route('certificate-registration.submit', self::TOKEN), [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.com',
            'rodo_consent' => '1',
            'newsletter_consent' => '1',
        ])->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu', false);

        Queue::assertPushed(SubscribeCertificateRegistrationNewsletterJob::class, function (SubscribeCertificateRegistrationNewsletterJob $job) {
            return $job->email === 'anna@example.com'
                && $job->firstName === 'Anna'
                && $job->lastName === 'Nowak';
        });
    }

    public function test_submit_does_not_dispatch_newsletter_job_without_consent(): void
    {
        Queue::fake();
        $this->fakeStatusAndRegister();

        $this->get(route('certificate-registration.show', self::TOKEN));

        $this->post(route('certificate-registration.submit', self::TOKEN), [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.com',
            'rodo_consent' => '1',
        ])->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu', false)
            ->assertSee('Rejestracja zaświadczenia', false);

        Queue::assertNotPushed(SubscribeCertificateRegistrationNewsletterJob::class);
    }

    public function test_show_uses_lightweight_layout_without_heavy_assets(): void
    {
        Http::fake([
            'https://pneadm.test/api/certificate-registration/status/*' => Http::response(
                $this->activeStatusPayload(),
                200,
            ),
        ]);

        $response = $this->get(route('certificate-registration.show', self::TOKEN));

        $response->assertOk();
        $response->assertSee('LISTA OBECNOŚCI', false);
        $response->assertDontSee('swiper-bundle', false);
        $response->assertDontSee('aos.js', false);
        $response->assertDontSee('googletagmanager.com', false);
    }

    public function test_show_includes_cache_control_header_for_cdn(): void
    {
        Http::fake([
            'https://pneadm.test/api/certificate-registration/status/*' => Http::response(
                $this->activeStatusPayload(),
                200,
            ),
        ]);

        config(['services.certificate_registration.page_cache_max_age' => 30]);

        $response = $this->get(route('certificate-registration.show', self::TOKEN));

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=30', $cacheControl);
        $this->assertStringContainsString('stale-while-revalidate=60', $cacheControl);
    }

    public function test_show_omits_cache_control_when_page_cache_disabled(): void
    {
        Http::fake([
            'https://pneadm.test/api/certificate-registration/status/*' => Http::response(
                $this->activeStatusPayload(),
                200,
            ),
        ]);

        config(['services.certificate_registration.page_cache_max_age' => 0]);

        $response = $this->get(route('certificate-registration.show', self::TOKEN));

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertStringNotContainsString('stale-while-revalidate', $cacheControl);
    }
}
