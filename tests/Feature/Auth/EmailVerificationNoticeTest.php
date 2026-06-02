<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_sees_verification_notice_on_homepage(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'niezweryfikowany@gmail.com',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Zweryfikuj swój adres e-mail', false);
        $response->assertSee('niezweryfikowany@gmail.com', false);
        $response->assertSee('zostaną usunięte', false);
        $response->assertSee('Wyślij link ponownie', false);
    }

    public function test_verified_user_does_not_see_verification_notice(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Zweryfikuj swój adres e-mail', false);
    }

    public function test_unverified_user_sees_notice_on_verify_email_page(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertSee('Weryfikacja adresu e-mail', false);
        $response->assertSee('zostaną usunięte', false);
    }
}
