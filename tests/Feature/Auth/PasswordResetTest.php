<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\SystemResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_initial_password_setup_screen_uses_set_password_copy(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'last_login_at' => null,
            'login_count' => 0,
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class, function ($notification) {
            $response = $this->get('/ustaw-haslo/'.$notification->token);

            $response->assertOk();
            $response->assertSee('Ustaw hasło', false);
            $response->assertSee('Konto zostało utworzone po zapisie na szkolenie', false);
            $response->assertDontSee('Resetowanie hasła', false);

            return true;
        });
    }

    public function test_legacy_reset_link_for_never_logged_in_user_uses_set_password_copy(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'last_login_at' => null,
            'login_count' => 0,
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class, function ($notification) use ($user) {
            $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));

            $response->assertOk();
            $response->assertSee('Ustaw hasło', false);
            $response->assertDontSee('Resetowanie hasła', false);

            return true;
        });
    }

    public function test_reset_password_screen_for_returning_user_keeps_reset_copy(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'last_login_at' => now(),
            'login_count' => 2,
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class, function ($notification) use ($user) {
            $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));

            $response->assertOk();
            $response->assertSee('Resetowanie hasła', false);
            $response->assertDontSee('Konto zostało utworzone po zapisie na szkolenie', false);

            return true;
        });
    }

    public function test_initial_password_setup_success_message_does_not_say_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'last_login_at' => null,
            'login_count' => 0,
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, SystemResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
                'intent' => 'set',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', 'Hasło zostało ustawione. Możesz się zalogować.');

            return true;
        });
    }
}
