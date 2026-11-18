<?php

namespace Tests\Unit;

use App\Notifications\OnlineCourseProductAccessGranted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OnlineCourseProductAccessGrantedUrlTest extends TestCase
{
    public function test_mail_action_url_uses_app_url_not_internal_docker_host(): void
    {
        config(['app.url' => 'http://localhost:8081']);
        URL::forceRootUrl('http://localhost:8081');

        $this->app->instance(
            'request',
            Request::create('http://pnedu-app/api/internal/product-orders/1/fulfill', 'POST')
        );

        $user = new class
        {
            public function getEmailForPasswordReset(): string
            {
                return 'araj@example.pl';
            }
        };

        $notification = new OnlineCourseProductAccessGranted(
            courseTitle: 'TIK w pracy NAUCZYCIELA',
            passwordResetToken: 'test-token-abc',
        );

        $mail = $notification->toMail($user);

        $this->assertNotNull($mail->actionUrl);
        $this->assertStringStartsWith('http://localhost:8081/ustaw-haslo/', $mail->actionUrl);
        $this->assertStringNotContainsString('pnedu-app', $mail->actionUrl);
    }
}
