<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeUnverifiedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_deletes_unverified_accounts_older_than_grace_period(): void
    {
        config(['auth.unverified_account_grace_days' => 14]);

        $oldUnverified = User::factory()->unverified()->create([
            'created_at' => now()->subDays(15),
        ]);
        $recentUnverified = User::factory()->unverified()->create([
            'created_at' => now()->subDays(3),
        ]);
        $oldVerified = User::factory()->create([
            'created_at' => now()->subDays(20),
        ]);

        $this->artisan('users:purge-unverified')
            ->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $oldUnverified->id]);
        $this->assertDatabaseHas('users', [
            'id' => $recentUnverified->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $oldVerified->id,
            'deleted_at' => null,
        ]);
    }
}
