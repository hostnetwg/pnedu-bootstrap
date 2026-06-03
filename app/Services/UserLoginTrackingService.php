<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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
    }
}
