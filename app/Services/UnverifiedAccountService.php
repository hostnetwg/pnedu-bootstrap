<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use App\Notifications\EmailVerificationReminder;
use Illuminate\Support\Collection;

class UnverifiedAccountService
{
    /**
     * @return array<int, string>
     */
    public function reminderDayColumnMap(): array
    {
        return (array) config('auth.verification_reminder_days', [
            3 => 'verification_reminder_3d_sent_at',
            83 => 'verification_reminder_83d_sent_at',
            89 => 'verification_reminder_89d_sent_at',
        ]);
    }

    public function hasPaidCourseEnrollment(User $user): bool
    {
        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return false;
        }

        return Participant::query()
            ->whereRaw('LOWER(TRIM(participants.email)) = ?', [$email])
            ->whereHas('course', fn ($query) => $query->where('is_paid', 1))
            ->exists();
    }

    public function isProtectedFromUnverifiedPurge(User $user): bool
    {
        return $this->hasPaidCourseEnrollment($user);
    }

    public function sendDueVerificationReminders(): int
    {
        $sent = 0;

        foreach ($this->reminderDayColumnMap() as $daysAfterRegistration => $sentAtColumn) {
            $sent += $this->sendRemindersForDay((int) $daysAfterRegistration, (string) $sentAtColumn);
        }

        return $sent;
    }

    public function purgeExpiredUnverifiedAccounts(): int
    {
        $days = (int) config('auth.unverified_account_grace_days', 90);
        $cutoff = now()->subDays($days);

        $purged = 0;

        User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function (Collection $users) use (&$purged) {
                foreach ($users as $user) {
                    if ($this->isProtectedFromUnverifiedPurge($user)) {
                        continue;
                    }

                    $user->delete();
                    $purged++;
                }
            });

        return $purged;
    }

    private function sendRemindersForDay(int $daysAfterRegistration, string $sentAtColumn): int
    {
        $sent = 0;
        $threshold = now()->subDays($daysAfterRegistration);

        User::query()
            ->whereNull('email_verified_at')
            ->whereNull($sentAtColumn)
            ->where('created_at', '<=', $threshold)
            ->orderBy('id')
            ->chunkById(100, function (Collection $users) use ($daysAfterRegistration, $sentAtColumn, &$sent) {
                foreach ($users as $user) {
                    $user->notify(new EmailVerificationReminder($daysAfterRegistration));
                    $user->forceFill([$sentAtColumn => now()])->save();
                    $sent++;
                }
            });

        return $sent;
    }
}
