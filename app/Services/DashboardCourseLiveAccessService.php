<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use App\Support\DashboardCourseLiveAccess;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DashboardCourseLiveAccessService
{
    public function forParticipant(Participant $participant): DashboardCourseLiveAccess
    {
        $course = $participant->course;
        if (! $course instanceof Course) {
            return DashboardCourseLiveAccess::hidden();
        }

        if (! $this->isLiveWindowOpen($course)) {
            return DashboardCourseLiveAccess::hidden();
        }

        $course->loadMissing('onlineDetail');
        $participant->loadMissing('liveAccess');

        $online = $course->onlineDetail;
        $liveAccess = $participant->liveAccess;

        $platform = strtolower(trim((string) ($online?->platform ?? $liveAccess?->platform ?? '')));
        $meetingLink = trim((string) ($online?->meeting_link ?? ''));
        $meetingPassword = trim((string) ($online?->meeting_password ?? ''));

        $joinUrl = $this->resolveJoinUrl($liveAccess, $meetingLink);
        if ($joinUrl === null) {
            return DashboardCourseLiveAccess::hidden();
        }

        $password = $meetingPassword !== '' ? $meetingPassword : null;
        $countdown = $this->resolveCountdown($course);

        return new DashboardCourseLiveAccess(
            show: true,
            joinUrl: $joinUrl,
            password: $password,
            platformLabel: $this->platformLabel($platform),
            countdownPhase: $countdown['phase'],
            countdownTargetIso: $countdown['target_iso'],
            countdownLabel: $countdown['label'],
        );
    }

    /**
     * Szkolenie jeszcze się nie skończyło (przed startem lub w trakcie).
     * Jak reguła maili provision: po end_date ukryj; bez end_date — ukryj gdy start_date już minął.
     */
    public function isLiveWindowOpen(Course $course): bool
    {
        if (! $course->start_date) {
            return false;
        }

        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $start = Carbon::parse($course->start_date)->timezone($tz);
        $end = $course->end_date ? Carbon::parse($course->end_date)->timezone($tz) : null;

        if ($end instanceof CarbonInterface && $end->isPast()) {
            return false;
        }

        if ($end === null && $start->isPast()) {
            return false;
        }

        return true;
    }

    private function resolveJoinUrl(?ParticipantLiveAccess $liveAccess, string $meetingLinkFallback): ?string
    {
        if ($liveAccess instanceof ParticipantLiveAccess && $liveAccess->isSuccessful()) {
            $roomUrl = trim((string) $liveAccess->room_url);
            if ($roomUrl === '') {
                $roomUrl = $meetingLinkFallback;
            }

            if ($roomUrl !== '') {
                return $this->buildJoinUrl($roomUrl, $liveAccess->token);
            }
        }

        if ($meetingLinkFallback !== '') {
            return $meetingLinkFallback;
        }

        return null;
    }

    public function buildJoinUrl(string $roomUrl, ?string $token = null): string
    {
        $roomUrl = rtrim(trim($roomUrl), '/');
        $token = trim((string) $token);

        if ($token === '') {
            return $roomUrl;
        }

        return $roomUrl.'/'.$token;
    }

    /**
     * @return array{phase: string|null, target_iso: string|null, label: string|null}
     */
    private function resolveCountdown(Course $course): array
    {
        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz);
        $start = Carbon::parse($course->start_date)->timezone($tz);
        $end = $course->end_date ? Carbon::parse($course->end_date)->timezone($tz) : null;

        if ($start->isFuture()) {
            return [
                'phase' => 'until_start',
                'target_iso' => $start->toIso8601String(),
                'label' => 'Do rozpoczęcia szkolenia',
            ];
        }

        if ($end instanceof CarbonInterface && $end->isFuture()) {
            return [
                'phase' => 'until_end',
                'target_iso' => $end->toIso8601String(),
                'label' => 'Do zakończenia szkolenia',
            ];
        }

        return [
            'phase' => null,
            'target_iso' => null,
            'label' => null,
        ];
    }

    private function platformLabel(string $platform): string
    {
        return match ($platform) {
            'clickmeeting' => 'ClickMeeting',
            'youtube' => 'YouTube',
            'google meet', 'googlemeet', 'meet' => 'Google Meet',
            'zoom' => 'Zoom',
            default => $platform !== '' ? ucfirst($platform) : 'Spotkanie online',
        };
    }
}
