<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Strona podziękowania po zakończeniu spotkania ClickMeeting
 * (redirect z ustawień wydarzenia CM: „Strona z podziękowaniem z własnym adresem URL”).
 */
class PostTrainingThankYouController extends Controller
{
    public function __invoke(Request $request): View
    {
        $course = $this->resolveCourse($request);
        $eventId = $this->normalizeEventId($request->query('event'));

        $courseTitle = $course?->plainTitle();
        $instructorLine = $this->instructorLineForCourse($course);
        $startDateTimeLine = $this->startDateTimeLineForCourse($course);

        $user = $request->user();

        return view('post-training.thank-you', [
            'courseTitle' => $courseTitle,
            'instructorLine' => $instructorLine,
            'startDateTimeLine' => $startDateTimeLine,
            'eventId' => $eventId,
            'isAuthenticated' => $user !== null,
            'dashboardUrl' => route('dashboard.szkolenia'),
            'loginUrl' => route('login'),
        ]);
    }

    private function resolveCourse(Request $request): ?Course
    {
        $courseId = $this->normalizeCourseId($request->query('course'));
        if ($courseId !== null) {
            return Course::query()
                ->with(['instructor:id,title,first_name,last_name,gender'])
                ->find($courseId);
        }

        $eventId = $this->normalizeEventId($request->query('event'));
        if ($eventId === null) {
            return null;
        }

        $online = CourseOnlineDetail::query()
            ->where('clickmeeting_event_id', $eventId)
            ->with([
                'course:id,title,instructor_id,start_date',
                'course.instructor:id,title,first_name,last_name,gender',
            ])
            ->first();

        return $online?->course;
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
