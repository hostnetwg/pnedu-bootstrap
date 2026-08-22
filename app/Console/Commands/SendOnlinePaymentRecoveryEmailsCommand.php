<?php

namespace App\Console\Commands;

use App\Services\FormOrderOnlinePaymentRecoveryService;
use Illuminate\Console\Command;

class SendOnlinePaymentRecoveryEmailsCommand extends Command
{
    protected $signature = 'form-orders:send-online-payment-recovery-emails
                            {--dry-run : Pokaż liczbę kandydatów bez wysyłki}';

    protected $description = 'Wysyła e-maile recovery dla porzuconych / nieudanych płatności online (Etap 3)';

    public function handle(FormOrderOnlinePaymentRecoveryService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Recovery e-mail wyłączony (ORDER_FORM_ONLINE_RECOVERY_ENABLED=false).');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $count = $service->countAutomaticRecoveryCandidates();
            $this->info('Kandydaci do recovery e-mail: '.$count);

            return self::SUCCESS;
        }

        $sent = $service->sendDueAutomaticRecoveries();
        $this->info('Wysłano '.$sent.' recovery e-maili płatności online.');

        return self::SUCCESS;
    }
}
