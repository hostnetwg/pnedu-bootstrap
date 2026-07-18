<?php

namespace App\Support;

use App\Models\Participant;
use App\Services\DashboardCourseLiveAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Dyskretny pasek „spotkanie na żywo” na stronie głównej (tylko zalogowany użytkownik).
 */
final class HomepageLiveMeetingNotice
{
    public function __construct(
        public readonly string $courseTitle,
        public readonly string $startDateLabel,
        public readonly DashboardCourseLiveAccess $live,
        public readonly bool $hasMoreLiveCourses,
    ) {}

    public static function forCurrentUser(): ?self
    {
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        $emailNormalized = Participant::normalizeEmail($user->email) ?? '';
        if ($emailNormalized === '') {
            return null;
        }

        $participants = Participant::query()
            ->forNormalizedEmail($emailNormalized)
            ->whereHas('course', function ($courseQuery) {
                $courseQuery->whereNotNull('start_date');
            })
            ->with([
                'course:id,title,start_date,end_date',
                'course.onlineDetail:id,course_id,platform,meeting_link,meeting_password',
                'liveAccess',
            ])
            ->get()
            ->sortBy(fn (Participant $participant) => $participant->course?->start_date)
            ->values();

        if ($participants->isEmpty()) {
            return null;
        }

        $service = app(DashboardCourseLiveAccessService::class);
        $visible = [];

        foreach ($participants as $participant) {
            $live = $service->forParticipant($participant);
            if ($live->show) {
                $visible[] = [$participant, $live];
            }
        }

        if ($visible === []) {
            return null;
        }

        /** @var Participant $participant */
        /** @var DashboardCourseLiveAccess $live */
        [$participant, $live] = $visible[0];
        $course = $participant->course;
        if ($course === null) {
            return null;
        }

        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $startAt = Carbon::parse($course->start_date)->timezone($tz)->locale('pl');
        $startDateLabel = $startAt->format('d.m.Y G:i').' ('.\Illuminate\Support\Str::ucfirst($startAt->isoFormat('dddd')).')';

        return new self(
            courseTitle: (string) $course->title,
            startDateLabel: $startDateLabel,
            live: $live,
            hasMoreLiveCourses: count($visible) > 1,
        );
    }
}
