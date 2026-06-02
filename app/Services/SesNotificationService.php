<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class SesNotificationService
{
    public function handleSesEvent(array $sesMessage): void
    {
        $notificationType = $sesMessage['notificationType'] ?? null;

        if ($notificationType === 'Bounce') {
            $this->handleBounce($sesMessage);

            return;
        }

        if ($notificationType === 'Complaint') {
            $this->handleComplaint($sesMessage);
        }
    }

    private function handleBounce(array $sesMessage): void
    {
        $bounceType = $sesMessage['bounce']['bounceType'] ?? null;
        if ($bounceType !== 'Permanent') {
            Log::info('SES bounce ignored (non-permanent)', [
                'bounce_type' => $bounceType,
                'sub_type' => $sesMessage['bounce']['bounceSubType'] ?? null,
            ]);

            return;
        }

        foreach ($this->recipientEmails($sesMessage) as $email) {
            $this->markUndeliverable($email, 'permanent_bounce');
        }
    }

    private function handleComplaint(array $sesMessage): void
    {
        foreach ($this->recipientEmails($sesMessage) as $email) {
            $this->markUndeliverable($email, 'complaint');
        }
    }

    /**
     * @return list<string>
     */
    private function recipientEmails(array $sesMessage): array
    {
        $emails = [];

        foreach ($sesMessage['bounce']['bouncedRecipients'] ?? [] as $recipient) {
            if (! empty($recipient['emailAddress'])) {
                $emails[] = strtolower(trim((string) $recipient['emailAddress']));
            }
        }

        foreach ($sesMessage['complaint']['complainedRecipients'] ?? [] as $recipient) {
            if (! empty($recipient['emailAddress'])) {
                $emails[] = strtolower(trim((string) $recipient['emailAddress']));
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }

    private function markUndeliverable(string $email, string $reason): void
    {
        $users = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->get();

        if ($users->isEmpty()) {
            Log::info('SES undeliverable: no matching user', ['email' => $email, 'reason' => $reason]);

            return;
        }

        foreach ($users as $user) {
            if ($user->email_undeliverable_at !== null) {
                continue;
            }

            $user->forceFill([
                'email_undeliverable_at' => now(),
                'email_undeliverable_reason' => $reason,
            ])->save();

            Log::info('SES marked user email as undeliverable', [
                'user_id' => $user->id,
                'email' => $email,
                'reason' => $reason,
            ]);
        }
    }
}
