<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Strona szkoły z zaświadczeniami jednego zakończonego szkolenia online.
 * Link jest tajny (token sprawy). Nie używa osobistego tokenu uczestnika.
 */
class ClosedTrainingSchoolCertificatePage
{
    /**
     * @return array{
     *     token: string,
     *     course_id: int,
     *     course_title: string,
     *     schedule_label: string,
     *     valid_until: string|null,
     *     download_enabled: bool,
     *     participants: list<array{
     *         id: int,
     *         name: string,
     *         email: string,
     *         link_sent: bool,
     *         downloaded: bool,
     *         birth_missing: bool
     *     }>
     * }|null
     */
    public function find(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        $case = DB::connection('pneadm')
            ->table('closed_training_cases')
            ->where('school_certificate_token', $token)
            ->first(['id', 'school_certificate_token_expires_at']);
        if ($case === null || ! $this->tokenIsActive($case->school_certificate_token_expires_at)) {
            return null;
        }

        $course = DB::connection('pneadm')
            ->table('courses')
            ->where('closed_training_case_id', $case->id)
            ->whereNull('deleted_at')
            ->first(['id', 'title', 'type', 'start_date', 'end_date', 'certificate_download_status']);
        if ($course === null || strtolower(trim((string) $course->type)) !== 'online') {
            return null;
        }

        if (! $this->hasEnded($course->end_date)) {
            return null;
        }

        $participants = Participant::query()
            ->where('course_id', $course->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get(['id', 'first_name', 'last_name', 'email', 'birth_date', 'birth_place']);

        $ids = $participants->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $sent = $this->sentParticipantIds((int) $course->id, $ids);
        $downloaded = $this->downloadedParticipantIds((int) $course->id, $ids);

        $rows = [];
        foreach ($participants as $participant) {
            $id = (int) $participant->id;
            $name = trim((string) $participant->first_name.' '.(string) $participant->last_name);
            $birthPlace = trim((string) $participant->birth_place);
            $rows[] = [
                'id' => $id,
                'name' => $name !== '' ? $name : 'Uczestnik',
                'email' => trim((string) $participant->email),
                'link_sent' => isset($sent[$id]),
                'downloaded' => isset($downloaded[$id]),
                'birth_missing' => $participant->birth_date === null || $birthPlace === '',
            ];
        }

        $title = trim(html_entity_decode(strip_tags((string) $course->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return [
            'token' => $token,
            'course_id' => (int) $course->id,
            'course_title' => $title !== '' ? $title : 'Szkolenie',
            'schedule_label' => $this->scheduleLabel($course->start_date, $course->end_date),
            'valid_until' => $case->school_certificate_token_expires_at
                ? Carbon::parse($case->school_certificate_token_expires_at)->timezone('Europe/Warsaw')->format('d.m.Y')
                : null,
            'download_enabled' => (string) ($course->certificate_download_status ?? '') === 'download_enabled',
            'participants' => $rows,
        ];
    }

    public function participantBelongsToToken(string $token, int $participantId): bool
    {
        $page = $this->find($token);
        if ($page === null) {
            return false;
        }

        foreach ($page['participants'] as $row) {
            if ($row['id'] === $participantId) {
                return $page['download_enabled'];
            }
        }

        return false;
    }

    public function revoke(string $token): bool
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return false;
        }

        $case = DB::connection('pneadm')
            ->table('closed_training_cases')
            ->where('school_certificate_token', $token)
            ->first(['id', 'school_certificate_token_expires_at']);
        if ($case === null || ! $this->tokenIsActive($case->school_certificate_token_expires_at)) {
            return false;
        }

        $updated = DB::connection('pneadm')
            ->table('closed_training_cases')
            ->where('id', $case->id)
            ->where('school_certificate_token', $token)
            ->update([
                'school_certificate_token' => null,
                'school_certificate_token_expires_at' => null,
                'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            return false;
        }

        DB::connection('pneadm')->table('closed_training_case_events')->insert([
            'closed_training_case_id' => $case->id,
            'user_id' => null,
            'event_type' => 'updated',
            'body' => 'Szkoła unieważniła link do listy zaświadczeń ze strony pobierania.',
            'happened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * @param  list<int>  $participantIds
     * @return array<int, true>
     */
    private function sentParticipantIds(int $courseId, array $participantIds): array
    {
        if ($participantIds === []) {
            return [];
        }

        $ids = DB::connection('pneadm')
            ->table('certificate_email_logs')
            ->where('course_id', $courseId)
            ->whereIn('participant_id', $participantIds)
            ->whereIn('type', ['list_link', 'single_certificate'])
            ->where('status', 'sent')
            ->pluck('participant_id');

        $sent = [];
        foreach ($ids as $id) {
            $sent[(int) $id] = true;
        }

        return $sent;
    }

    /**
     * @param  list<int>  $participantIds
     * @return array<int, true>
     */
    private function downloadedParticipantIds(int $courseId, array $participantIds): array
    {
        if ($participantIds === []) {
            return [];
        }

        $certificates = Certificate::query()
            ->where('course_id', $courseId)
            ->whereIn('participant_id', $participantIds)
            ->get(['participant_id', 'download_count', 'first_downloaded_at']);

        $downloaded = [];
        foreach ($certificates as $certificate) {
            $count = (int) ($certificate->download_count ?? 0);
            $first = $certificate->first_downloaded_at ?? null;
            if ($count > 0 || ($first !== null && $first !== '')) {
                $downloaded[(int) $certificate->participant_id] = true;
            }
        }

        return $downloaded;
    }

    private function hasEnded(mixed $endDate): bool
    {
        if ($endDate === null || $endDate === '') {
            return false;
        }

        return Carbon::parse($endDate)->isPast();
    }

    private function tokenIsActive(mixed $expiresAt): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return true;
        }

        return Carbon::parse($expiresAt)->isFuture();
    }

    private function scheduleLabel(mixed $startDate, mixed $endDate): string
    {
        if ($startDate === null || $startDate === '') {
            return '';
        }

        $tz = 'Europe/Warsaw';
        $start = Carbon::parse($startDate)->timezone($tz);
        $label = $start->format('d.m.Y').' r., godz. '.$start->format('H:i');

        if ($endDate !== null && $endDate !== '') {
            $end = Carbon::parse($endDate)->timezone($tz);
            if ($start->isSameDay($end) && $end->format('H:i') !== $start->format('H:i')) {
                $label .= '–'.$end->format('H:i');
            } elseif (! $start->isSameDay($end)) {
                $label .= ' – '.$end->format('d.m.Y').' r., godz. '.$end->format('H:i');
            }
        }

        return $label;
    }
}
