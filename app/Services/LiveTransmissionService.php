<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use Illuminate\Support\Facades\Log;

class LiveTransmissionService
{
    public function __construct(
        private readonly ClickMeetingService $clickMeeting,
        private readonly DashboardCourseLiveAccessService $liveAccessService,
    ) {}

    /**
     * @return array{
     *   ok: bool,
     *   error?: string,
     *   course_title?: string,
     *   iframe_src?: string|null,
     *   room_autologin_url?: string|null,
     *   room_token_url?: string|null,
     *   token_rotated?: bool
     * }
     */
    public function buildForParticipant(Participant $participant, bool $forceNewToken = false): array
    {
        $participant->loadMissing(['course.onlineDetail', 'course.instructor', 'liveAccess']);
        $course = $participant->course;
        $online = $course?->onlineDetail;

        if ($course === null || $online === null) {
            return ['ok' => false, 'error' => 'Brak danych szkolenia online.'];
        }

        if (! $this->liveAccessService->isLiveWindowOpen($course)) {
            return ['ok' => false, 'error' => 'Okno spotkania na żywo jest zamknięte.'];
        }

        if (! (bool) ($online->embed_on_pnedu ?? false)) {
            return ['ok' => false, 'error' => 'Osadzony pokój nie jest włączony dla tego szkolenia.'];
        }

        $platform = strtolower(trim((string) ($online->platform ?? '')));
        $eventId = trim((string) ($online->clickmeeting_event_id
            ?: $participant->liveAccess?->clickmeeting_event_id
            ?? ''));

        if ($platform !== 'clickmeeting' || $eventId === '') {
            return ['ok' => false, 'error' => 'Brak konfiguracji ClickMeeting dla tego szkolenia.'];
        }

        $email = strtolower(trim((string) $participant->email));
        $nickname = trim((string) $participant->first_name);
        if ($nickname === '') {
            $nickname = 'Uczestnik';
        }

        $conferenceResult = $this->clickMeeting->getConference($eventId);
        if (! ($conferenceResult['success'] ?? false)) {
            return [
                'ok' => false,
                'error' => (string) ($conferenceResult['error'] ?? 'Nie udało się pobrać wydarzenia ClickMeeting.'),
            ];
        }

        $conference = $conferenceResult['conference'] ?? [];
        $accessType = isset($conferenceResult['access_type']) ? (int) $conferenceResult['access_type'] : null;
        $roomUrl = $this->clickMeeting->extractRoomUrl($conference)
            ?: trim((string) ($participant->liveAccess?->room_url ?? ''))
            ?: trim((string) ($online->meeting_link ?? ''));
        $roomPin = $this->clickMeeting->extractRoomPin($conference);

        if ($roomUrl === '' || $roomPin === null) {
            return ['ok' => false, 'error' => 'Brak room_url lub room_pin z ClickMeeting.'];
        }

        $token = null;
        $tokenRotated = false;
        if ($accessType === ClickMeetingService::ACCESS_TYPE_TOKEN) {
            $resolved = $this->resolveSingleActiveToken(
                $participant,
                $eventId,
                $email,
                $roomUrl,
                $forceNewToken
            );
            if (! ($resolved['success'] ?? false)) {
                return [
                    'ok' => false,
                    'error' => (string) ($resolved['error'] ?? 'Brak tokenu ClickMeeting dla transmisji osadzonej.'),
                ];
            }
            $token = (string) ($resolved['token'] ?? '');
            $tokenRotated = (bool) ($resolved['rotated'] ?? false);
        }

        $password = null;
        if ($accessType === ClickMeetingService::ACCESS_TYPE_PASSWORD) {
            $password = trim((string) ($online->meeting_password ?? ''));
        }

        $hashResult = $this->clickMeeting->generateAutologinHash(
            $eventId,
            $email,
            $nickname,
            'listener',
            $token,
            $password
        );

        if (! ($hashResult['success'] ?? false)) {
            return [
                'ok' => false,
                'error' => (string) ($hashResult['error'] ?? 'Nie udało się wygenerować auto-login.'),
            ];
        }

        $hash = (string) $hashResult['autologin_hash'];
        $pinEmbed = $this->clickMeeting->buildPinEmbedUrl($roomUrl, $roomPin);
        $iframeSrc = $pinEmbed !== null
            ? $this->clickMeeting->buildAutologinUrl($pinEmbed, $hash)
            : null;
        $roomAutologinUrl = $this->clickMeeting->buildAutologinUrl($roomUrl, $hash);
        $roomTokenUrl = $token !== null && $token !== ''
            ? rtrim($roomUrl, '/').'/'.$token
            : $roomUrl;

        if ($token !== null && $token !== '') {
            $this->markTokenConsumed($participant);
        }

        return [
            'ok' => true,
            'course_title' => (string) $course->title,
            'iframe_src' => $iframeSrc,
            'room_autologin_url' => $roomAutologinUrl,
            'room_token_url' => $roomTokenUrl,
            'token_rotated' => $tokenRotated,
        ];
    }

    public function isEmbedEnabledForParticipant(Participant $participant): bool
    {
        if (! $this->liveAccessService->viewerMayUseEmbed()) {
            return false;
        }

        $participant->loadMissing(['course.onlineDetail', 'liveAccess']);
        $online = $participant->course?->onlineDetail;
        if ($online === null || ! (bool) ($online->embed_on_pnedu ?? false)) {
            return false;
        }

        $platform = strtolower(trim((string) ($online->platform
            ?? $participant->liveAccess?->platform
            ?? '')));
        $eventId = trim((string) ($online->clickmeeting_event_id
            ?: $participant->liveAccess?->clickmeeting_event_id
            ?? ''));

        return $platform === 'clickmeeting' && $eventId !== '';
    }

    /**
     * Jeden aktywny token na uczestnika: ten sam z provision, rotacja = nowy + DELETE starego w CM.
     *
     * @return array{success: bool, error?: string, token?: string, rotated?: bool}
     */
    private function resolveSingleActiveToken(
        Participant $participant,
        string $eventId,
        string $email,
        string $roomUrl,
        bool $forceNewToken
    ): array {
        $existing = trim((string) ($participant->liveAccess?->token ?? ''));

        if ($existing === '') {
            $fromApi = $this->clickMeeting->getAccessTokenForEmail($eventId, $email);
            if ($fromApi['success'] ?? false) {
                $existing = trim((string) ($fromApi['token'] ?? ''));
            }
        }

        $needsNew = $forceNewToken;
        if (! $needsNew && $participant->liveAccess?->embed_token_consumed_at !== null) {
            $needsNew = true;
        }

        if (! $needsNew && $existing !== '') {
            $meta = $this->clickMeeting->findAccessTokenMeta($eventId, $existing, $email);
            if (($meta['success'] ?? false) && ($meta['first_use_date'] ?? null) !== null) {
                $needsNew = true;
            }
        }

        if (! $needsNew && $existing !== '') {
            return [
                'success' => true,
                'token' => $existing,
                'rotated' => false,
            ];
        }

        $generated = $this->clickMeeting->generateAccessToken($eventId);
        if (! ($generated['success'] ?? false)) {
            if ($existing !== '') {
                Log::warning('LiveTransmissionService: nie udało się wygenerować nowego tokenu, używam istniejącego', [
                    'participant_id' => $participant->id,
                    'event_id' => $eventId,
                    'error' => $generated['error'] ?? null,
                ]);

                return [
                    'success' => true,
                    'token' => $existing,
                    'rotated' => false,
                ];
            }

            return [
                'success' => false,
                'error' => (string) ($generated['error'] ?? 'Nie udało się wygenerować tokenu ClickMeeting.'),
            ];
        }

        $newToken = trim((string) ($generated['token'] ?? ''));
        if ($newToken === '') {
            return [
                'success' => false,
                'error' => 'ClickMeeting nie zwrócił nowego tokenu.',
            ];
        }

        if ($existing !== '' && $existing !== $newToken) {
            $deactivated = $this->clickMeeting->deactivateTokens($eventId, [$existing]);
            if (! ($deactivated['success'] ?? false)) {
                Log::warning('LiveTransmissionService: nie udało się unieważnić starego tokenu po rotacji', [
                    'participant_id' => $participant->id,
                    'event_id' => $eventId,
                    'old_token_suffix' => substr($existing, -2),
                    'error' => $deactivated['error'] ?? null,
                ]);
            }
        }

        $this->persistToken($participant, $eventId, $roomUrl, $newToken);

        return [
            'success' => true,
            'token' => $newToken,
            'rotated' => true,
        ];
    }

    private function markTokenConsumed(Participant $participant): void
    {
        $liveAccess = $participant->liveAccess;
        if (! $liveAccess instanceof ParticipantLiveAccess) {
            $participant->load('liveAccess');
            $liveAccess = $participant->liveAccess;
        }

        if (! $liveAccess instanceof ParticipantLiveAccess) {
            return;
        }

        $liveAccess->forceFill([
            'embed_token_consumed_at' => now(),
        ])->save();

        $participant->setRelation('liveAccess', $liveAccess->fresh());
    }

    private function persistToken(
        Participant $participant,
        string $eventId,
        string $roomUrl,
        string $token
    ): void {
        $liveAccess = $participant->liveAccess;
        if ($liveAccess instanceof ParticipantLiveAccess) {
            $liveAccess->forceFill([
                'token' => $token,
                'embed_token_consumed_at' => null,
                'room_url' => $roomUrl !== '' ? $roomUrl : $liveAccess->room_url,
                'clickmeeting_event_id' => $eventId,
                'access_type' => ClickMeetingService::ACCESS_TYPE_TOKEN,
                'status' => 'success',
                'message' => 'Token odświeżony przy ponownym wejściu (stary unieważniony w CM).',
                'synced_at' => now(),
            ])->save();

            $participant->setRelation('liveAccess', $liveAccess->fresh());

            return;
        }

        $created = ParticipantLiveAccess::query()->updateOrCreate(
            ['participant_id' => $participant->id],
            [
                'course_id' => $participant->course_id,
                'platform' => 'clickmeeting',
                'clickmeeting_event_id' => $eventId,
                'access_type' => ClickMeetingService::ACCESS_TYPE_TOKEN,
                'room_url' => $roomUrl !== '' ? $roomUrl : null,
                'token' => $token,
                'embed_token_consumed_at' => null,
                'status' => 'success',
                'message' => 'Token utworzony przy wejściu do transmisji.',
                'synced_at' => now(),
            ]
        );

        $participant->setRelation('liveAccess', $created);
    }
}
