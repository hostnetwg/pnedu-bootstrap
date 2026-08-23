<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Services\DashboardCourseLiveAccessService;
use App\Support\ClickMeetingConferenceEndedCache;
use App\Support\PostTrainingThankYouPhase;
use App\Support\PostTrainingThankYouResources;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Strona podziękowania po zakończeniu spotkania ClickMeeting
 * (redirect z ustawień wydarzenia CM: „Strona z podziękowaniem z własnym adresem URL”).
 */
class PostTrainingThankYouController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardCourseLiveAccessService $liveAccessService,
    ): View {
        $course = $this->resolveCourse($request);
        $eventId = $this->resolveClickMeetingEventId($request, $course);
        $cmEndedByHost = $this->wasConferenceEndedByHost($eventId);

        $user = $request->user();
        $participant = $this->resolveParticipantForUser($user, $course);

        $phase = PostTrainingThankYouPhase::resolve(
            $course,
            $cmEndedByHost,
            $participant,
            $liveAccessService,
        );

        $resources = PostTrainingThankYouResources::forCourse($course);

        return view('post-training.thank-you', [
            'courseTitle' => $course?->plainTitle(),
            'instructorLine' => $this->instructorLineForCourse($course),
            'startDateTimeLine' => $this->startDateTimeLineForCourse($course),
            'eventId' => $eventId,
            'isAuthenticated' => $user !== null,
            'dashboardUrl' => route('dashboard.szkolenia'),
            'loginUrl' => route('login'),
            'resources' => $resources,
            'phase' => $phase,
        ]);
    }

    private function resolveCourse(Request $request): ?Course
    {
        $courseId = $this->normalizeCourseId($request->query('course'));
        if ($courseId !== null) {
            return Course::query()
                ->with([
                    'instructor:id,title,first_name,last_name,gender',
                    'onlineDetail:id,course_id,clickmeeting_event_id',
                ])
                ->find($courseId);
        }

        $eventId = $this->normalizeEventId($request->query('event'));
        if ($eventId === null) {
            return null;
        }

        $online = CourseOnlineDetail::query()
            ->where('clickmeeting_event_id', $eventId)
            ->with([
                'course:id,title,instructor_id,start_date,end_date,certificate_download_status',
                'course.instructor:id,title,first_name,last_name,gender',
                'course.onlineDetail:id,course_id,clickmeeting_event_id',
            ])
            ->first();

        return $online?->course;
    }

    private function resolveClickMeetingEventId(Request $request, ?Course $course): ?string
    {
        $eventId = $this->normalizeEventId($request->query('event'));
        if ($eventId !== null) {
            return $eventId;
        }

        $fromCourse = trim((string) ($course?->onlineDetail?->clickmeeting_event_id ?? ''));

        return $fromCourse !== '' ? $fromCourse : null;
    }

    private function wasConferenceEndedByHost(?string $eventId): bool
    {
        if ($eventId === null) {
            return false;
        }

        return ClickMeetingConferenceEndedCache::wasEndedByHost($eventId);
    }

    private function resolveParticipantForUser(mixed $user, ?Course $course): ?Participant
    {
        if ($user === null || $course === null) {
            return null;
        }

        $emailNormalized = Participant::normalizeEmail($user->email ?? null);
        if ($emailNormalized === null) {
            return null;
        }

        return Participant::query()
            ->forNormalizedEmail($emailNormalized)
            ->where('course_id', $course->id)
            ->first();
    }

    private function normalizeCourseId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }

        $courseId = trim((string) $raw);
        if ($courseId === '' || ! preg_match('/^\d{1,20}$/', $courseId)) {
            return null;
        }

        return (int) $courseId;
    }

    private function normalizeEventId(mixed $raw): ?string
    {
        $eventId = trim((string) ($raw ?? ''));

        if ($eventId === '' || ! preg_match('/^\d{1,20}$/', $eventId)) {
            return null;
        }

        return $eventId;
    }

    private function instructorLineForCourse(?Course $course): ?string
    {
        $instructor = $course?->instructor;
        if ($instructor === null) {
            return null;
        }

        $display = trim((string) ($instructor->full_name_with_title ?? ''));
        if ($display === '') {
            return null;
        }

        return trim((string) ($instructor->gender_title ?? 'Prowadzący')).': '.$display;
    }

    private function startDateTimeLineForCourse(?Course $course): ?string
    {
        if (! $course?->start_date instanceof CarbonInterface) {
            return null;
        }

        return $course->start_date
            ->copy()
            ->timezone((string) config('app.timezone', 'Europe/Warsaw'))
            ->format('d.m.Y G:i');
    }
}
