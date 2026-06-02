<?php

namespace App\Console\Commands;

use App\Services\UnverifiedAccountService;
use Illuminate\Console\Command;

class PurgeUnverifiedUsers extends Command
{
    protected $signature = 'users:purge-unverified';

    protected $description = 'Usuwa (soft delete) niezweryfikowane konta po okresie karencji, z wyłączeniem zapisów na płatne szkolenia';

    public function handle(UnverifiedAccountService $service): int
    {
        $days = (int) config('auth.unverified_account_grace_days', 90);
        $purged = $service->purgeExpiredUnverifiedAccounts();

        $this->info('Usunięto '.$purged.' niezweryfikowanych kont (starszych niż '.$days.' dni, bez płatnych szkoleń).');

        return self::SUCCESS;
    }
}
