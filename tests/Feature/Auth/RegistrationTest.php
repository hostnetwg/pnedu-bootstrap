<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\SystemVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rodo_consent' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();

        Notification::assertSentToTimes($user, SystemVerifyEmail::class, 1);
    }

    public function test_existing_email_cannot_register_again(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->from('/register')->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rodo_consent' => '1',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_soft_deleted_email_can_register_again(): void
    {
        Notification::fake();

        $deletedUser = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $deletedUser->delete();

        $deletedUser = User::withTrashed()->findOrFail($deletedUser->id);
        $this->assertNotSame('test@example.com', $deletedUser->email_unique_slot);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rodo_consent' => '1',
        ]);

        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertAuthenticated();

        $newUser = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertNotSame($deletedUser->id, $newUser->id);
        Notification::assertSentToTimes($newUser, SystemVerifyEmail::class, 1);
    }
}
