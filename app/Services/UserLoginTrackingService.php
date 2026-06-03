<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserLoginTrackingService
{
    public function recordAuthenticatedSession(User $user): void
    {
        User::query()
            ->whereKey($user->getKey())
            ->update([
                'last_login_at' => now(),
                'login_count' => DB::raw('login_count + 1'),
            ]);

        if (Schema::hasTable('user_login_sessions')) {
            UserLoginSession::query()->create([
                'user_id' => $user->getKey(),
                'logged_in_at' => now(),
            ]);
        }
    }
}
