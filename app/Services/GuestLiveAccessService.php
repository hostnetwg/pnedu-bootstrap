<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sekretny /live/{token} — gość bez konta, tylko zamknięte + osadzony pokój.
 */
class GuestLiveAccessService
{
    public function __construct(
        private readonly DashboardCourseLiveAccessService $liveAccess,
    ) {}

    public function findCourseByToken(string $token): ?Course
    {
        $token = trim($token);
        if (! preg_match('/^[A-Za-z0-9]{32,64}$/', $token)) {
            return null;
        }

        $details = CourseOnlineDetail::query()
            ->where('guest_live_token', $token)
            ->with('course')
            ->first();

        $course = $details?->course;

        return $course instanceof Course ? $course : null;
    }

    public function entryError(Course $course): ?string
    {
        if (($course->category ?? '') !== 'closed') {
            return 'Ten link jest nieaktywny.';
        }

        $course->loadMissing('onlineDetail');
        $online = $course->onlineDetail;
        if ($online === null || ! (bool) ($online->embed_on_pnedu ?? false)) {
            return 'Osadzony pokój nie jest włączony dla tego szkolenia.';
        }

        $platform = strtolower(trim((string) ($online->platform ?? '')));
        $eventId = trim((string) ($online->clickmeeting_event_id ?? ''));
        if ($platform !== 'clickmeeting' || $eventId === '') {
            return 'Brak konfiguracji ClickMeeting dla tego szkolenia.';
        }

        if (! $this->liveAccess->isLiveWindowOpen($course)) {
            return 'Okno spotkania na żywo jest zamknięte.';
        }

        $gate = $this->liveAccess->resolveJoinGate($course);
        if (! ($gate['unlocked'] ?? false)) {
            return (string) ($gate['hint'] ?? 'Link do spotkania zostanie aktywowany 2 godziny przed rozpoczęciem szkolenia.');
        }

        return null;
    }

    /**
     * @return array{participant_id: int, email: string, first_name: string, last_name: string, nickname: string}|null
     */
    public function registeredIdentity(Request $request, Course $course): ?array
    {
        $stored = $request->session()->get($this->sessionKey($course));
        if (! is_array($stored)) {
            return null;
        }

        $participantId = (int) ($stored['participant_id'] ?? 0);
        $email = strtolower(trim((string) ($stored['email'] ?? '')));
        $firstName = trim((string) ($stored['first_name'] ?? ''));
        $lastName = trim((string) ($stored['last_name'] ?? ''));
        if ($participantId <= 0 || $email === '' || ! str_contains($email, '@') || $firstName === '' || $lastName === '') {
            return null;
        }

        $participant = Participant::query()
            ->where('course_id', $course->id)
            ->whereKey($participantId)
            ->first();
        if (! $participant instanceof Participant) {
            $request->session()->forget($this->sessionKey($course));

            return null;
        }

        return [
            'participant_id' => (int) $participant->id,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'nickname' => $this->clickMeetingNickname($firstName, $lastName),
        ];
    }

    /**
     * Zapis / aktualizacja wiersza uczestnika (ten sam e-mail na kursie = jeden rekord).
     */
    public function registerOrUpdate(Course $course, string $firstName, string $lastName, string $email): Participant
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $emailNormalized = Participant::normalizeEmail($email);
        if ($emailNormalized === null) {
            throw new \InvalidArgumentException('E-mail jest wymagany.');
        }

        return DB::connection('pneadm')->transaction(function () use ($course, $firstName, $lastName, $email, $emailNormalized) {
            $lockedCourse = Course::query()->lockForUpdate()->find($course->id);
            if (! $lockedCourse instanceof Course) {
                throw new \RuntimeException('Szkolenie nie istnieje.');
            }

            $existing = Participant::withTrashed()
                ->where('course_id', $lockedCourse->id)
                ->forNormalizedEmail($emailNormalized)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $expiresAt = $lockedCourse->end_date
                ? $lockedCourse->end_date->copy()->addMonths(2)
                : ($lockedCourse->start_date?->copy()->addMonths(2));

            $attributes = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => trim($email),
                'email_normalized' => $emailNormalized,
                'access_expires_at' => $expiresAt,
            ];

            if ($existing instanceof Participant) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->forceFill($attributes)->save();

                return $existing->fresh() ?? $existing;
            }

            $order = (int) ($lockedCourse->next_participant_order ?: 1);
            $lockedCourse->increment('next_participant_order');

            return Participant::query()->create(array_merge($attributes, [
                'course_id' => $lockedCourse->id,
                'order' => $order,
            ]));
        });
    }

    public function remember(Request $request, Course $course, Participant $participant): void
    {
        $request->session()->put($this->sessionKey($course), [
            'participant_id' => (int) $participant->id,
            'email' => strtolower(trim((string) $participant->email)),
            'first_name' => trim((string) $participant->first_name),
            'last_name' => trim((string) $participant->last_name),
        ]);
    }

    public function clickMeetingNickname(string $firstName, string $lastName): string
    {
        $full = trim($firstName.' '.$lastName);
        if (mb_strlen($full) >= 3) {
            return mb_substr($full, 0, 80);
        }

        return 'Uczestnik';
    }

    private function sessionKey(Course $course): string
    {
        return 'guest_live_registered_'.$course->id;
    }
}
