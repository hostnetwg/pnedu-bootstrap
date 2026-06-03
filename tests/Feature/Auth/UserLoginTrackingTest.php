<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_one_entry_per_authenticated_session(): void
    {
        $user = User::factory()->create([
            'login_count' => 0,
            'last_login_at' => null,
        ]);

        $this->actingAs($user)->get(route('dashboard'));
        $user->refresh();

        $this->assertSame(1, $user->login_count);
        $this->assertNotNull($user->last_login_at);

        $this->actingAs($user)->get(route('dashboard'));
        $user->refresh();

        $this->assertSame(1, $user->login_count);
    }

    public function test_guest_requests_do_not_increment_login_count(): void
    {
        $user = User::factory()->create([
            'login_count' => 0,
        ]);

        $this->get(route('home'));
        $user->refresh();

        $this->assertSame(0, $user->login_count);
    }
}
