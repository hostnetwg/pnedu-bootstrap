<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Services\UnverifiedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeUnverifiedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_deletes_unverified_accounts_older_than_grace_period(): void
    {
        config(['auth.unverified_account_grace_days' => 90]);

        $oldUnverified = User::factory()->unverified()->create([
            'created_at' => now()->subDays(91),
        ]);
        $recentUnverified = User::factory()->unverified()->create([
            'created_at' => now()->subDays(10),
        ]);
        $oldVerified = User::factory()->create([
            'created_at' => now()->subDays(100),
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

    public function test_purge_skips_users_with_paid_course_enrollment(): void
    {
        config(['auth.unverified_account_grace_days' => 90]);

        $user = User::factory()->unverified()->create([
            'created_at' => now()->subDays(91),
        ]);

        $service = new class extends UnverifiedAccountService
        {
            public function isProtectedFromUnverifiedPurge(User $user): bool
            {
                return true;
            }
        };

        $this->instance(UnverifiedAccountService::class, $service);

        $this->artisan('users:purge-unverified')
            ->assertSuccessful();

        $this->assertNull($user->fresh()->deleted_at);
    }
}
