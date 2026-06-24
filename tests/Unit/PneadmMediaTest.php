<?php

namespace Tests\Unit;

use App\Support\PneadmMedia;
use Tests\TestCase;

class PneadmMediaTest extends TestCase
{
    public function test_direct_url_when_proxy_disabled(): void
    {
        config([
            'services.pneadm.public_url' => 'https://adm.pnedu.pl',
            'services.pneadm.media_proxy' => false,
        ]);

        $this->assertSame(
            'https://adm.pnedu.pl/storage/courses/images/course_1_abc.png',
            PneadmMedia::url('courses/images/course_1_abc.png')
        );
    }

    public function test_proxy_url_when_enabled(): void
    {
        config([
            'app.url' => 'https://pnedu.pl',
            'services.pneadm.media_proxy' => true,
        ]);

        $url = PneadmMedia::url('courses/images/course_530_05fb37.png');

        $this->assertStringContainsString('/media/pneadm/courses/images/course_530_05fb37.png', $url);
    }

    public function test_allowed_paths(): void
    {
        $this->assertTrue(PneadmMedia::isAllowedPath('courses/images/foo.png'));
        $this->assertTrue(PneadmMedia::isAllowedPath('instructors/photo.jpg'));
        $this->assertFalse(PneadmMedia::isAllowedPath('../etc/passwd'));
        $this->assertFalse(PneadmMedia::isAllowedPath('certificates/logos/x.png'));
    }
}
