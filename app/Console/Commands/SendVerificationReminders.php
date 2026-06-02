<?php

namespace App\Console\Commands;

use App\Services\UnverifiedAccountService;
use Illuminate\Console\Command;

class SendVerificationReminders extends Command
{
    protected $signature = 'users:send-verification-reminders';

    protected $description = 'Wysyła zaplanowane przypomnienia o weryfikacji e-mail (3., 83. i 89. dzień)';

    public function handle(UnverifiedAccountService $service): int
    {
        $sent = $service->sendDueVerificationReminders();

        $this->info('Wysłano '.$sent.' przypomnień o weryfikacji e-mail.');

        return self::SUCCESS;
    }
}
