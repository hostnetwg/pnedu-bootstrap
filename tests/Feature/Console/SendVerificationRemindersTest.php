<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Notifications\EmailVerificationReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendVerificationRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_day_3_reminder_to_unverified_user(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'teacher@gmail.com',
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('users:send-verification-reminders')
            ->assertSuccessful();

        Notification::assertSentTo($user, EmailVerificationReminder::class);

        $this->assertNotNull($user->fresh()->verification_reminder_3d_sent_at);
    }

    public function test_does_not_resend_day_3_reminder_when_already_sent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'created_at' => now()->subDays(10),
            'verification_reminder_3d_sent_at' => now()->subDays(7),
        ]);

        $this->artisan('users:send-verification-reminders')
            ->assertSuccessful();

        Notification::assertNothingSentTo($user);
    }
}
