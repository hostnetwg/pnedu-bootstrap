<?php

namespace Tests\Feature\Webhooks;

use App\Models\User;
use App\Services\SesNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesNotificationWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_user_on_permanent_bounce_notification(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'bad@gmail.com',
        ]);

        $payload = [
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bouncedRecipients' => [
                    ['emailAddress' => 'bad@gmail.com'],
                ],
            ],
        ];

        app(SesNotificationService::class)->handleSesEvent($payload);

        $user->refresh();
        $this->assertNotNull($user->email_undeliverable_at);
        $this->assertSame('permanent_bounce', $user->email_undeliverable_reason);
    }

    public function test_marks_user_on_configuration_set_bounce_with_event_type(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'bad@gmail.com',
        ]);

        app(SesNotificationService::class)->handleSesEvent([
            'eventType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bouncedRecipients' => [
                    ['emailAddress' => 'bad@gmail.com'],
                ],
            ],
            'mail' => [
                'destination' => ['bad@gmail.com'],
            ],
        ]);

        $user->refresh();
        $this->assertNotNull($user->email_undeliverable_at);
        $this->assertSame('permanent_bounce', $user->email_undeliverable_reason);
    }

    public function test_ignores_transient_bounce(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'busy@gmail.com',
        ]);

        app(SesNotificationService::class)->handleSesEvent([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Transient',
                'bouncedRecipients' => [
                    ['emailAddress' => 'busy@gmail.com'],
                ],
            ],
        ]);

        $this->assertNull($user->fresh()->email_undeliverable_at);
    }

    public function test_profile_email_change_clears_undeliverable_flag(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'bad@gmail.com',
            'email_undeliverable_at' => now(),
            'email_undeliverable_reason' => 'permanent_bounce',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'fixed@gmail.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->assertSame('fixed@gmail.com', $user->email);
        $this->assertNull($user->email_undeliverable_at);
        $this->assertNull($user->email_undeliverable_reason);
    }

    public function test_bounce_notification_uses_trusted_fallback_when_signature_invalid(): void
    {
        config([
            'services.ses.sns_topic_arn' => 'arn:aws:sns:eu-central-1:388786438877:ses-pne-system-events',
        ]);

        $user = User::factory()->unverified()->create([
            'email' => 'bounce@simulator.amazonses.com',
        ]);

        $sesPayload = [
            'eventType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bouncedRecipients' => [
                    ['emailAddress' => 'bounce@simulator.amazonses.com'],
                ],
            ],
            'mail' => [
                'destination' => ['bounce@simulator.amazonses.com'],
            ],
        ];

        $payload = [
            'Type' => 'Notification',
            'MessageId' => 'test-notification-id',
            'TopicArn' => 'arn:aws:sns:eu-central-1:388786438877:ses-pne-system-events',
            'Message' => json_encode($sesPayload, JSON_UNESCAPED_SLASHES),
            'Timestamp' => '2026-06-02T21:00:00.000Z',
            'SignatureVersion' => '1',
            'Signature' => 'invalid-signature-on-purpose',
            'SigningCertURL' => 'https://sns.eu-central-1.amazonaws.com/SimpleNotificationService-123.pem',
        ];

        $response = $this->call(
            'POST',
            route('webhooks.ses.notifications'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain; charset=UTF-8'],
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        $response->assertOk();
        $response->assertSee('OK');
        $user->refresh();
        $this->assertNotNull($user->email_undeliverable_at);
        $this->assertSame('permanent_bounce', $user->email_undeliverable_reason);
    }
}
