<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_expired_signed_verification_link_still_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subDay(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1('wrong-email'),
        ]);

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('verification.notice', absolute: false));
        $response->assertSessionHas('error');
    }

    public function test_wrong_account_on_verification_link_redirects_to_login_with_email_hint(): void
    {
        $user = User::factory()->unverified()->create();
        $otherUser = User::factory()->unverified()->create();

        $verificationUrl = URL::route('verification.verify', [
            'id' => $otherUser->id,
            'hash' => sha1($otherUser->email),
        ]);

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertFalse($otherUser->fresh()->hasVerifiedEmail());
        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('email_verification_relogin', true);
        $response->assertSessionHas('login_email_hint', $otherUser->email);
        $response->assertSessionHas('url.intended', $verificationUrl);
    }

    public function test_user_can_verify_after_relogin_from_wrong_account_redirect(): void
    {
        $loggedInUser = User::factory()->unverified()->create();
        $targetUser = User::factory()->unverified()->create();

        $verificationUrl = URL::route('verification.verify', [
            'id' => $targetUser->id,
            'hash' => sha1($targetUser->email),
        ]);

        $this->actingAs($loggedInUser)->get($verificationUrl);

        Event::fake();

        $loginResponse = $this->post('/login', [
            'email' => $targetUser->email,
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect($verificationUrl);

        $verifyResponse = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($targetUser->fresh()->hasVerifiedEmail());
        $verifyResponse->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }
}
