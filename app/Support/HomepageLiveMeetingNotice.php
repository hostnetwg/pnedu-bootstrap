<?php

namespace App\Support;

use App\Models\Participant;
use App\Services\DashboardCourseLiveAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Dyskretny pasek „spotkanie na żywo” na stronie głównej (tylko zalogowany użytkownik).
 * Pokazuje najbliższy dzień ze szkoleniami live — wszystkie z tego dnia (jak lista na /dashboard/szkolenia).
 */
final class HomepageLiveMeetingNotice
{
    /**
     * @param  list<HomepageLiveMeetingItem>  $items
     */
    public function __construct(
        public readonly array $items,
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
                'course.onlineDetail:id,course_id,platform,meeting_link,meeting_password,clickmeeting_event_id,clickmeeting_join_enabled,embed_on_pnedu',
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

        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $nearestCourse = $visible[0][0]->course;
        if ($nearestCourse === null || $nearestCourse->start_date === null) {
            return null;
        }

        $nearestDayKey = Carbon::parse($nearestCourse->start_date)->timezone($tz)->toDateString();
        $sameDay = [];

        foreach ($visible as [$participant, $live]) {
            $course = $participant->course;
            if ($course === null || $course->start_date === null) {
                continue;
            }

            $dayKey = Carbon::parse($course->start_date)->timezone($tz)->toDateString();
            if ($dayKey === $nearestDayKey) {
                $sameDay[] = [$participant, $live];
            }
        }

        if ($sameDay === []) {
            return null;
        }

        $items = [];
        foreach ($sameDay as [$participant, $live]) {
            $course = $participant->course;
            if ($course === null) {
                continue;
            }

            $startAt = Carbon::parse($course->start_date)->timezone($tz)->locale('pl');
            $items[] = new HomepageLiveMeetingItem(
                courseTitle: (string) $course->title,
                startDateLabel: $startAt->format('d.m.Y G:i').' ('.Str::ucfirst($startAt->isoFormat('dddd')).')',
                live: $live,
            );
        }

        if ($items === []) {
            return null;
        }

        return new self(
            items: $items,
            hasMoreLiveCourses: count($visible) > count($items),
        );
    }
}
