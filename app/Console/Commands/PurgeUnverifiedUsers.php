<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PurgeUnverifiedUsers extends Command
{
    protected $signature = 'users:purge-unverified';

    protected $description = 'Usuwa (soft delete) konta bez weryfikacji e-mail po upływie okresu karencji';

    public function handle(): int
    {
        $days = (int) config('auth.unverified_account_grace_days', 14);
        $cutoff = now()->subDays($days);

        $users = User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($users as $user) {
            $user->delete();
        }

        $this->info('Usunięto '.$users->count().' niezweryfikowanych kont (starszych niż '.$days.' dni).');

        return self::SUCCESS;
    }
}
