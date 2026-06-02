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
}
