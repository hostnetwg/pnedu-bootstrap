<?php

namespace Tests\Unit;

use App\Services\CertificateRegistrationStatusService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CertificateRegistrationStatusServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pneadm.api_url' => 'https://pneadm.test',
            'services.pneadm.api_token' => 'secret',
            'services.certificate_registration.status_cache_ttl' => 120,
        ]);

        Cache::flush();
    }

    public function test_does_not_cache_null_when_api_is_unavailable(): void
    {
        config([
            'services.pneadm.api_url' => '',
            'services.pneadm.api_token' => '',
        ]);

        $service = new CertificateRegistrationStatusService;

        $this->assertNull($service->getStatus('token-a'));
        $this->assertNull($service->getStatus('token-a'));

        Http::assertNothingSent();
        $this->assertFalse(Cache::has('cert_reg:status:token-a'));
    }

    public function test_forget_removes_cached_status(): void
    {
        Http::fake([
            'https://pneadm.test/api/certificate-registration/status/*' => Http::response(['active' => true], 200),
        ]);

        $service = new CertificateRegistrationStatusService;
        $service->getStatus('token-b');
        $this->assertTrue(Cache::has('cert_reg:status:token-b'));

        $service->forget('token-b');
        $this->assertFalse(Cache::has('cert_reg:status:token-b'));

        $service->getStatus('token-b');
        Http::assertSentCount(2);
    }
}
