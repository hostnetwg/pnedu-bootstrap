<?php

namespace App\Jobs;

use App\Services\SendyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SubscribeCertificateRegistrationNewsletterJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}

    public function handle(): void
    {
        $sendy = SendyService::fromConfig();
        if ($sendy === null) {
            return;
        }

        try {
            $sendy->subscribeCertificateRegistrationNewsletter(
                $this->email,
                $this->firstName,
                $this->lastName,
            );
        } catch (\Throwable $e) {
            Log::warning('CertificateRegistration: Sendy subscribe failed', [
                'email' => $this->email,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
