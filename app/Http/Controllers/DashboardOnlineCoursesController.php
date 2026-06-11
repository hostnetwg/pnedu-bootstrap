<?php

namespace App\Http\Controllers;

use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\OnlineCourseLesson;
use App\Models\OnlineCourseLessonCompletion;
use App\Models\OnlineCourseLessonNote;
use App\Services\OnlineCourseLessonCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardOnlineCoursesController extends Controller
{
    public function index(): View
    {
        $email = OnlineCourseEnrollment::normalizeEmail(Auth::user()->email);
        $enrollments = collect();
        $lessonProgressByEnrollment = [];
        if ($email) {
            $enrollments = OnlineCourseEnrollment::query()
                ->where('email', $email)
                ->whereHas('onlineCourse', function ($q) {
                    $q->where('is_active', true)->where('visible_in_dashboard', true);
                })
                ->with([
                    'onlineCourse.instructor',
                    'onlineCourse.modulesWithPublishedLessons',
                    'lessonCompletions',
                ])
                ->orderByDesc('id')
                ->get();

            foreach ($enrollments as $enrollment) {
                $progress = $this->progressCounts($enrollment, $enrollment->onlineCourse);
                $lessonProgressByEnrollment[$enrollment->id] = $progress;
            }
        }

        return view('dashboard.online-courses.index', compact('enrollments', 'lessonProgressByEnrollment'));
    }

    public function show(OnlineCourseEnrollment $enrollment): View|RedirectResponse
    {
        $this->assertEnrollmentAccess($enrollment);
        $course = $enrollment->onlineCourse;
        abort_unless($course->is_active && $course->visible_in_dashboard, 404);

        $course->load('modulesWithPublishedLessons');

        foreach ($course->modulesWithPublishedLessons as $module) {
            foreach ($module->lessons as $publishedLesson) {
                return redirect()->route('dashboard.online-courses.lesson', [$enrollment, $publishedLesson]);
            }
        }

        return view('dashboard.online-courses.show', [
            'enrollment' => $enrollment,
            'course' => $course,
        ]);
    }

    public function lesson(OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson, OnlineCourseLessonCertificateService $certificateService): View
    {
        $this->assertEnrollmentAccess($enrollment);
        $course = $enrollment->onlineCourse;
        $this->assertAccessibleLesson($enrollment, $lesson);

        $lesson->load(['embeds', 'resourceLinks', 'module', 'linkedCourse.videos']);
        $course->load('modulesWithPublishedLessons');

        $enrollment->loadMissing('lessonCompletions');
        $completedLessonIds = $enrollment->lessonCompletions->pluck('online_course_lesson_id')->map(fn ($id) => (int) $id)->all();
        $currentLessonCompleted = in_array((int) $lesson->id, $completedLessonIds, true);
        $lessonProgress = $this->progressCounts($enrollment, $course);
        $adjacent = $this->adjacentPublishedLessons($course, $lesson);

        $linkedCourseLiveVideos = collect();
        if ($lesson->linkedCourse) {
            $linkedCourseLiveVideos = $lesson->linkedCourse->videos
                ->filter(fn ($video) => trim((string) ($video->video_url ?? '')) !== '')
                ->values();
        }

        $lessonNote = OnlineCourseLessonNote::query()
            ->where('online_course_enrollment_id', $enrollment->id)
            ->where('online_course_lesson_id', $lesson->id)
            ->first();

        $lessonNotesForSidebar = $this->lessonNotesBodiesForSidebar($enrollment, $course);
        $certificateContext = $certificateService->lessonCertificateContext($enrollment, $lesson);

        return view('dashboard.online-courses.lesson', [
            'enrollment' => $enrollment,
            'course' => $course,
            'lesson' => $lesson,
            'completedLessonIds' => $completedLessonIds,
            'currentLessonCompleted' => $currentLessonCompleted,
            'lessonProgress' => $lessonProgress,
            'previousLesson' => $adjacent['previous'],
            'nextLesson' => $adjacent['next'],
            'lessonNote' => $lessonNote,
            'lessonNotesForSidebar' => $lessonNotesForSidebar,
            'certificateContext' => $certificateContext,
            'linkedCourseLiveVideos' => $linkedCourseLiveVideos,
        ]);
    }

    public function toggleLessonCompletion(Request $request, OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): RedirectResponse|JsonResponse
    {
        $this->assertEnrollmentAccess($enrollment);
        $this->assertAccessibleLesson($enrollment, $lesson);

        $course = $enrollment->onlineCourse;

        $completion = OnlineCourseLessonCompletion::query()
            ->where('online_course_enrollment_id', $enrollment->id)
            ->where('online_course_lesson_id', $lesson->id)
            ->first();

        if ($completion) {
            $completion->delete();
        } else {
            OnlineCourseLessonCompletion::query()->create([
                'online_course_enrollment_id' => $enrollment->id,
                'online_course_lesson_id' => $lesson->id,
                'completed_at' => now(),
            ]);
        }

        $enrollment->unsetRelation('lessonCompletions');
        $enrollment->load('lessonCompletions');
        $lessonCompleted = $enrollment->lessonCompletions->where('online_course_lesson_id', $lesson->id)->isNotEmpty();
        $progress = $this->progressCounts($enrollment, $course);

        if ($request->expectsJson()) {
            return response()->json([
                'lesson_completed' => $lessonCompleted,
                'progress' => $progress,
            ]);
        }

        return redirect()->back();
    }

    public function saveLessonNote(Request $request, OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): RedirectResponse|JsonResponse
    {
        $this->assertEnrollmentAccess($enrollment);
        $this->assertAccessibleLesson($enrollment, $lesson);

        $validated = $request->validate([
            'lesson_note_body' => ['nullable', 'string', 'max:65535'],
        ]);

        $raw = $validated['lesson_note_body'] ?? '';
        $isEmpty = trim($raw) === '';

        $query = OnlineCourseLessonNote::query()
            ->where('online_course_enrollment_id', $enrollment->id)
            ->where('online_course_lesson_id', $lesson->id);

        if ($isEmpty) {
            $deleted = $query->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'lesson_id' => (int) $lesson->id,
                    'deleted' => $deleted > 0,
                    'message' => $deleted > 0
                        ? 'Notatka do tej lekcji została usunięta.'
                        : 'Brak zapisanego tekstu — nie zmieniono notatki.',
                ]);
            }

            return redirect()->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
                ->with($deleted > 0 ? 'success' : 'info', $deleted > 0
                    ? 'Notatka do tej lekcji została usunięta.'
                    : 'Brak zapisanego tekstu — nie zmieniono notatki.');
        }

        OnlineCourseLessonNote::query()->updateOrCreate(
            [
                'online_course_enrollment_id' => $enrollment->id,
                'online_course_lesson_id' => $lesson->id,
            ],
            ['body' => $raw]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'lesson_id' => (int) $lesson->id,
                'saved' => true,
                'body' => $raw,
                'message' => 'Twoja notatka została zapisana.',
            ]);
        }

        return redirect()->route('dashboard.online-courses.lesson', [$enrollment, $lesson])
            ->with('success', 'Twoja notatka została zapisana.');
    }

    private function adjacentPublishedLessons(OnlineCourse $course, OnlineCourseLesson $lesson): array
    {
        $course->loadMissing('modulesWithPublishedLessons');
        $ordered = collect();
        foreach ($course->modulesWithPublishedLessons as $mod) {
            foreach ($mod->lessons as $l) {
                $ordered->push($l);
            }
        }

        $idx = $ordered->search(fn (OnlineCourseLesson $l) => (int) $l->id === (int) $lesson->id);
        if ($idx === false) {
            return ['previous' => null, 'next' => null];
        }

        $previous = $idx > 0 ? $ordered[$idx - 1] : null;
        $next = $idx < $ordered->count() - 1 ? $ordered[$idx + 1] : null;

        return ['previous' => $previous, 'next' => $next];
    }

    private function progressCounts(OnlineCourseEnrollment $enrollment, OnlineCourse $course): array
    {
        $course->loadMissing('modulesWithPublishedLessons');
        $total = 0;
        foreach ($course->modulesWithPublishedLessons as $mod) {
            $total += $mod->lessons->count();
        }
        $enrollment->loadMissing('lessonCompletions');

        return [
            'completed' => $enrollment->lessonCompletions->count(),
            'total' => $total,
        ];
    }

    private function assertAccessibleLesson(OnlineCourseEnrollment $enrollment, OnlineCourseLesson $lesson): void
    {
        $course = $enrollment->onlineCourse;
        abort_unless($course->is_active && $course->visible_in_dashboard, 404);
        abort_unless((int) $lesson->module->online_course_id === (int) $course->id, 404);
        abort_unless($lesson->is_published, 404);
    }

    private function assertEnrollmentAccess(OnlineCourseEnrollment $enrollment): void
    {
        abort_unless($enrollment->emailMatchesUser(Auth::user()->email ?? ''), 403);
        abort_if($enrollment->hasExpiredAccess(), 403, 'Dostęp do tego kursu wygasł.');
    }

    /**
     * Treści notatek do podglądu w drzewku lekcji: klucz = ID lekcji (string).
     *
     * @return array<string, string>
     */
    private function lessonNotesBodiesForSidebar(OnlineCourseEnrollment $enrollment, OnlineCourse $course): array
    {
        $course->loadMissing('modulesWithPublishedLessons');
        $lessonIds = [];
        foreach ($course->modulesWithPublishedLessons as $module) {
            foreach ($module->lessons as $l) {
                $lessonIds[] = (int) $l->id;
            }
        }
        if ($lessonIds === []) {
            return [];
        }

        $out = [];
        $rows = OnlineCourseLessonNote::query()
            ->where('online_course_enrollment_id', $enrollment->id)
            ->whereIn('online_course_lesson_id', $lessonIds)
            ->get(['online_course_lesson_id', 'body']);

        foreach ($rows as $row) {
            $out[(string) (int) $row->online_course_lesson_id] = $row->body;
        }

        return $out;
    }
}
