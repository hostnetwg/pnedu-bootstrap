<?php

namespace App\Services;

use App\Mail\TrainerDeclineMail;
use App\Mail\TrainerScheduleSetMail;
use App\Models\ClosedTrainingCase;
use App\Models\ClosedTrainingCaseEvent;
use App\Models\Instructor;
use App\Models\TrainerPortalNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class TrainerScheduleService
{
    /**
     * @return array{date_changed: bool, note_changed: bool}
     */
    public function record(ClosedTrainingCase $case, Instructor $instructor, Carbon $start, ?string $note, bool $test = false): array
    {
        if ($this->linkedToCourse($case)) {
            throw ValidationException::withMessages([
                'start_at' => 'Termin tej sprawy jest już przy szkoleniu. Zmianę robi administrator.',
            ]);
        }

        $note = $this->normalizeNote($note);
        $previousNote = $this->normalizeNote($case->trainer_note);
        $noteChanged = $note !== $previousNote;
        $previous = $case->start_date?->format('Y-m-d H:i');
        $wasTest = (bool) $case->trainer_schedule_is_test;

        $case->start_date = $start;
        $case->schedule_time_known = true;
        $case->trainer_schedule_is_test = $test;
        if ($case->end_date && $case->end_date->lessThanOrEqualTo($start)) {
            $case->end_date = null;
        }
        if ($noteChanged) {
            $case->trainer_note = $note;
        }
        $case->save();

        if ($noteChanged) {
            $this->recordNote($case, $instructor, $note, $test);
        }

        $when = $start->timezone(config('app.timezone'))->format('d.m.Y H:i');
        $dateChanged = $previous !== $start->format('Y-m-d H:i');
        $markChanged = $wasTest !== $test;
        if (! $dateChanged && ! $markChanged) {
            return ['date_changed' => false, 'note_changed' => $noteChanged];
        }

        $body = 'Trener '.$instructor->full_name.' wpisał termin: '.$when.'.';
        if (! $dateChanged && $wasTest && ! $test) {
            $body = 'Trener '.$instructor->full_name.' zdjął oznaczenie testu. Termin zostaje: '.$when.'.';
        }

        ClosedTrainingCaseEvent::query()->create([
            'closed_training_case_id' => $case->id,
            'user_id' => null,
            'event_type' => 'updated',
            'body' => $body,
            'note_channel' => $test ? 'test' : null,
            'note_party' => $test ? 'instructor' : null,
            'note_party_name' => $test ? $instructor->full_name : null,
            'happened_at' => now(),
        ]);

        if ($dateChanged) {
            $this->notifyAdmins($case, $instructor->full_name, $when, $note ?? '');
        }

        return ['date_changed' => $dateChanged, 'note_changed' => $noteChanged];
    }

    public function decline(ClosedTrainingCase $case, Instructor $instructor, bool $declined, ?string $reason): string
    {
        $reason = $this->normalizeNote($reason);
        $wasDeclined = $case->trainer_declined_at !== null;
        $previousReason = $this->normalizeNote($case->trainer_decline_reason);

        if (! $declined) {
            if (! $wasDeclined) {
                return 'unchanged';
            }

            $case->trainer_declined_at = null;
            $case->trainer_decline_reason = null;
            $case->save();
            $this->recordNote($case, $instructor, 'Trener '.$instructor->full_name.' wycofał rezygnację z prowadzenia.');
            $this->notifyDecline($case, $instructor->full_name, '', true);

            return 'withdrawn';
        }

        if ($reason === null) {
            throw ValidationException::withMessages([
                'trainer_decline_reason' => 'Napisz krótko, dlaczego rezygnujesz.',
            ]);
        }

        if ($wasDeclined && $previousReason === $reason) {
            return 'unchanged';
        }

        $case->trainer_declined_at = $case->trainer_declined_at ?? now();
        $case->trainer_decline_reason = $reason;
        $case->save();

        $this->recordNote(
            $case,
            $instructor,
            'Trener '.$instructor->full_name.' rezygnuje z prowadzenia. '.$reason,
        );
        $this->notifyDecline($case, $instructor->full_name, (string) $reason, false);

        return 'declined';
    }

    private function normalizeNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }

    private function recordNote(ClosedTrainingCase $case, Instructor $instructor, ?string $note, bool $test = false): void
    {
        $body = $note ?? ('Trener '.$instructor->full_name.' usunął notatkę.');

        ClosedTrainingCaseEvent::query()->create([
            'closed_training_case_id' => $case->id,
            'user_id' => null,
            'event_type' => 'note',
            'body' => $body,
            'note_channel' => $test ? 'test' : null,
            'note_party' => 'instructor',
            'note_party_name' => $instructor->full_name,
            'happened_at' => now(),
        ]);
    }

    private function linkedToCourse(ClosedTrainingCase $case): bool
    {
        return DB::connection('pneadm')
            ->table('courses')
            ->where('closed_training_case_id', $case->id)
            ->exists();
    }

    private function notifyDecline(ClosedTrainingCase $case, string $trainerName, string $reason, bool $withdrawn): void
    {
        $caseUrl = rtrim((string) config('services.pneadm.public_url'), '/')
            .'/closed-training-cases/'.$case->id;
        $topic = trim((string) $case->topic);
        $title = $withdrawn ? 'Trener wycofał rezygnację' : 'Trener rezygnuje z prowadzenia';
        $body = $withdrawn
            ? 'Trener '.$trainerName.' wycofał rezygnację'.($topic !== '' ? ' dla: '.$topic.'.' : '.')
            : 'Trener '.$trainerName.' nie poprowadzi szkolenia'.($topic !== '' ? ': '.$topic.'.' : '.').' '.$reason;

        foreach ($this->recipientIds($case) as $userId) {
            $notification = TrainerPortalNotification::query()->create([
                'audience' => TrainerPortalNotification::AUDIENCE_ADMIN,
                'pneadm_user_id' => $userId,
                'closed_training_case_id' => $case->id,
                'kind' => TrainerPortalNotification::KIND_TRAINER_DECLINED,
                'title' => $title,
                'body' => $body,
            ]);

            $email = DB::connection('pneadm')->table('users')->where('id', $userId)->value('email');
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                Mail::to($email)->send(new TrainerDeclineMail($case, $trainerName, $reason, $caseUrl, $withdrawn));
                $notification->email_sent_at = now();
                $notification->save();
            } catch (Throwable $exception) {
                Log::warning('Trainer decline mail failed', [
                    'case_id' => $case->id,
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    private function notifyAdmins(ClosedTrainingCase $case, string $trainerName, string $when, string $note): void
    {
        $recipientIds = $this->recipientIds($case);
        $caseUrl = rtrim((string) config('services.pneadm.public_url'), '/')
            .'/closed-training-cases/'.$case->id;
        $topic = trim((string) $case->topic);
        $body = 'Trener '.$trainerName.' wpisał termin '.$when
            .($topic !== '' ? ' dla: '.$topic.'.' : '.');

        foreach ($recipientIds as $userId) {
            $notification = TrainerPortalNotification::query()->create([
                'audience' => TrainerPortalNotification::AUDIENCE_ADMIN,
                'pneadm_user_id' => $userId,
                'closed_training_case_id' => $case->id,
                'kind' => TrainerPortalNotification::KIND_SCHEDULE_SET,
                'title' => 'Trener podał termin',
                'body' => $body,
            ]);

            $email = DB::connection('pneadm')->table('users')->where('id', $userId)->value('email');
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                Mail::to($email)->send(new TrainerScheduleSetMail($case, $trainerName, $when, $caseUrl, $note));
                $notification->email_sent_at = now();
                $notification->save();
            } catch (Throwable $exception) {
                Log::warning('Trainer schedule mail failed', [
                    'case_id' => $case->id,
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    /**
     * @return list<int>
     */
    private function recipientIds(ClosedTrainingCase $case): array
    {
        $connection = DB::connection('pneadm');
        $assigneeId = $case->assignee_user_id ? (int) $case->assignee_user_id : null;
        if ($assigneeId) {
            $active = $connection->table('users')
                ->where('id', $assigneeId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->exists();
            if ($active) {
                return [$assigneeId];
            }
        }

        $adminIds = $connection->table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', ['admin', 'super_admin'])
            ->where('users.is_active', 1)
            ->whereNull('users.deleted_at')
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($adminIds !== []) {
            return $adminIds;
        }

        return $connection->table('users')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
