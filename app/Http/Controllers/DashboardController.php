<?php

namespace App\Http\Controllers;

use App\Models\CourseVideo;
use App\Models\Participant;
use App\Models\ParticipantTrainingVideoNote;
use App\Models\PneadmCourseSurveyLink;
use App\Support\DashboardParticipantsListing;
use App\Support\DashboardResourceCounts;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Panel główny konta — przekierowanie do szkoleń / kursów online, gdy użytkownik ma zasoby.
     */
    public function index(): RedirectResponse|\Illuminate\View\View
    {
        $counts = DashboardResourceCounts::forUser(Auth::user());

        if ($counts['szkolenia'] > 0) {
            return redirect()->route('dashboard.szkolenia');
        }

        if ($counts['online_courses'] > 0) {
            return redirect()->route('dashboard.online-courses.index');
        }

        return view('dashboard.index');
    }

    /**
     * Wyświetl widok z osadzonym wideo szkolenia
     */
    public function szkoleniaWideo(Request $request, Participant $participant)
    {
        if ($redirect = $this->redirectToLoginWhenTrainingEmailMismatch($request, $participant)) {
            return $redirect;
        }

        $participant->load(['course.instructor', 'course.videos', 'course.fileLinks']);
        $course = $participant->course;

        if (! $course || ($course->videos->isEmpty() && $course->fileLinks->isEmpty())) {
            abort(404, 'Brak materiałów dla tego szkolenia.');
        }

        $tz = config('app.timezone');
        $courseEnded = $course->end_date
            && Carbon::parse($course->end_date)->timezone($tz)->isPast();

        $hasVideos = $course->videos->isNotEmpty();
        $hasFileLinks = $course->fileLinks->isNotEmpty();
        $materialsAccessActive = $participant->hasActiveAccess();

        if (! $hasVideos && $hasFileLinks && ! $courseEnded) {
            abort(403, 'Materiały do pobrania będą dostępne po zakończeniu szkolenia.');
        }

        if (! $materialsAccessActive && ! $hasVideos) {
            abort(403, 'Dostęp do materiałów wygasł.');
        }

        if ($materialsAccessActive) {
            $this->markTrainingPageOpened($participant);
        }

        $selectedVideo = null;
        if ($course->videos->isNotEmpty()) {
            $selectedVideoId = (int) request()->query('video', $course->videos->first()->id);
            $selectedVideo = $course->videos->firstWhere('id', $selectedVideoId) ?? $course->videos->first();
        }

        $videoNote = null;
        $videoNotesForList = [];
        if ($selectedVideo) {
            $videoNote = ParticipantTrainingVideoNote::query()
                ->where('participant_id', $participant->id)
                ->where('course_video_id', $selectedVideo->id)
                ->first();
        }
        $videoNotesForList = $this->trainingVideoNotesBodiesForList($participant, $course->videos);

        $accessibleSurveyLinks = $materialsAccessActive
            ? PneadmCourseSurveyLink::query()
                ->where('course_id', $course->id)
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->filter(fn (PneadmCourseSurveyLink $link) => $link->isAvailableNow())
                ->map(function (PneadmCourseSurveyLink $link) {
                    $gateUrl = $link->gateAbsoluteUrl();
                    if ($gateUrl === null) {
                        return null;
                    }

                    $title = trim((string) ($link->title ?? ''));

                    return [
                        'title' => $title !== '' ? $title : 'Ankieta poszkoleniowa',
                        'url' => $gateUrl,
                    ];
                })
                ->filter()
                ->values()
            : collect();

        return view('dashboard.szkolenia-wideo', [
            'participant' => $participant,
            'course' => $course,
            'videos' => $course->videos,
            'selectedVideo' => $selectedVideo,
            'fileLinks' => ($courseEnded && $materialsAccessActive) ? $course->fileLinks : collect(),
            'courseEnded' => $courseEnded,
            'materialsAccessActive' => $materialsAccessActive,
            'accessibleSurveyLinks' => $accessibleSurveyLinks,
            'videoNote' => $videoNote,
            'videoNotesForList' => $videoNotesForList,
        ]);
    }

    public function saveTrainingVideoNote(Request $request, Participant $participant, CourseVideo $video): RedirectResponse|JsonResponse
    {
        if ($redirect = $this->redirectToLoginWhenTrainingEmailMismatch($request, $participant)) {
            return $redirect;
        }

        $this->assertParticipantBelongsToUser($participant);

        abort_unless((int) $video->course_id === (int) $participant->course_id, 404);

        $validated = $request->validate([
            'training_video_note_body' => ['nullable', 'string', 'max:65535'],
        ]);

        $raw = $validated['training_video_note_body'] ?? '';
        $isEmpty = trim($raw) === '';

        $query = ParticipantTrainingVideoNote::query()
            ->where('participant_id', $participant->id)
            ->where('course_video_id', $video->id);

        if ($isEmpty) {
            $deleted = $query->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'video_id' => (int) $video->id,
                    'deleted' => $deleted > 0,
                    'message' => $deleted > 0
                        ? 'Notatka do tego nagrania została usunięta.'
                        : 'Brak zapisanego tekstu — nie zmieniono notatki.',
                ]);
            }

            return redirect()->route('dashboard.szkolenia.wideo', [
                'participant' => $participant,
                'video' => $video->id,
            ])->with($deleted > 0 ? 'success' : 'info', $deleted > 0
                ? 'Notatka do tego nagrania została usunięta.'
                : 'Brak zapisanego tekstu — nie zmieniono notatki.');
        }

        ParticipantTrainingVideoNote::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'course_video_id' => $video->id,
            ],
            ['body' => $raw]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'video_id' => (int) $video->id,
                'saved' => true,
                'body' => $raw,
                'message' => 'Twoja notatka została zapisana.',
            ]);
        }

        return redirect()->route('dashboard.szkolenia.wideo', [
            'participant' => $participant,
            'video' => $video->id,
        ])->with('success', 'Twoja notatka została zapisana.');
    }

    /**
     * Link z e-maila dotyczy konkretnego uczestnika (adres e-mail). Inne konto → wyloguj i logowanie.
     */
    private function redirectToLoginWhenTrainingEmailMismatch(Request $request, Participant $participant): ?RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $userNorm = strtolower(trim((string) $user->email));
        $participantNorm = strtolower(trim((string) ($participant->email ?? '')));

        if ($participantNorm === '' || $userNorm === $participantNorm) {
            return null;
        }

        $intended = route('dashboard.szkolenia.wideo', $participant);
        $query = $request->query();
        if ($query !== []) {
            $intended .= '?'.http_build_query($query);
        }

        Auth::guard('web')->logout();

        $request->session()->put('url.intended', $intended);
        $request->session()->flash('training_access_relogin', true);
        $request->session()->flash('login_email_hint', $participant->email);

        return redirect()->route('login');
    }

    private function assertParticipantBelongsToUser(Participant $participant): void
    {
        $userNorm = strtolower(trim((string) (Auth::user()->email ?? '')));
        $participantNorm = strtolower(trim((string) ($participant->email ?? '')));

        abort_unless($participantNorm !== '' && $userNorm === $participantNorm, 403);
    }

    /**
     * Treści notatek do podglądu na liście nagrań: klucz = ID wideo (string).
     *
     * @param  \Illuminate\Support\Collection<int, CourseVideo>  $videos
     * @return array<string, string>
     */
    private function trainingVideoNotesBodiesForList(Participant $participant, $videos): array
    {
        $videoIds = $videos->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($videoIds === []) {
            return [];
        }

        $out = [];
        $rows = ParticipantTrainingVideoNote::query()
            ->where('participant_id', $participant->id)
            ->whereIn('course_video_id', $videoIds)
            ->get(['course_video_id', 'body']);

        foreach ($rows as $row) {
            $out[(string) (int) $row->course_video_id] = $row->body;
        }

        return $out;
    }

    private function markTrainingPageOpened(Participant $participant): void
    {
        try {
            $now = now();
            $participantId = (int) $participant->id;
            $courseId = (int) $participant->course_id;

            DB::connection('pneadm')->statement(
                'INSERT INTO participant_training_page_views (participant_id, course_id, open_count, first_opened_at, last_opened_at, created_at, updated_at)
                 VALUES (?, ?, 1, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    open_count = open_count + 1,
                    last_opened_at = VALUES(last_opened_at),
                    first_opened_at = COALESCE(first_opened_at, VALUES(first_opened_at)),
                    updated_at = VALUES(updated_at)',
                [$participantId, $courseId, $now, $now, $now, $now]
            );
        } catch (\Throwable $e) {
            // Best-effort tracking – nie blokuj użytkownika.
        }
    }

    /**
     * Wyświetl listę szkoleń użytkownika
     *
     * @param  Request  $request  Query: typ=all|paid|free (wg courses.is_paid w pneadm)
     */
    public function szkolenia(Request $request)
    {
        return view('dashboard.szkolenia', DashboardParticipantsListing::forAuthenticatedUser($request));
    }
}
