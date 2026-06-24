<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PneadmMediaProxyTest extends TestCase
{
    public function test_proxy_serves_image_from_adm_storage(): void
    {
        config([
            'services.pneadm.public_url' => 'https://adm.pnedu.pl',
            'services.pneadm.media_proxy' => true,
        ]);

        Http::fake([
            'https://adm.pnedu.pl/storage/courses/images/course_530_test.png' => Http::response('png-bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $response = $this->get('/media/pneadm/courses/images/course_530_test.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame('png-bytes', $response->getContent());
    }

    public function test_proxy_rejects_disallowed_paths(): void
    {
        $this->get('/media/pneadm/certificates/logos/secret.png')->assertNotFound();
    }
}
