<?php

namespace App\Http\Controllers;

use App\Models\CourseFileLink;
use App\Models\Participant;
use App\Models\PneadmCourseSurveyLink;
use App\Support\DashboardResourceCounts;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        if ($participant->hasExpiredAccess()) {
            abort(403, 'Dostęp do materiałów wygasł.');
        }

        $tz = config('app.timezone');
        $courseEnded = $course->end_date
            && Carbon::parse($course->end_date)->timezone($tz)->isPast();

        $hasVideos = $course->videos->isNotEmpty();
        $hasFileLinks = $course->fileLinks->isNotEmpty();

        if (! $hasVideos && $hasFileLinks && ! $courseEnded) {
            abort(403, 'Materiały do pobrania będą dostępne po zakończeniu szkolenia.');
        }

        $this->markTrainingPageOpened($participant);

        $selectedVideo = null;
        if ($course->videos->isNotEmpty()) {
            $selectedVideoId = (int) request()->query('video', $course->videos->first()->id);
            $selectedVideo = $course->videos->firstWhere('id', $selectedVideoId) ?? $course->videos->first();
        }

        $accessibleSurveyLinks = PneadmCourseSurveyLink::query()
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
            ->values();

        return view('dashboard.szkolenia-wideo', [
            'participant' => $participant,
            'course' => $course,
            'videos' => $course->videos,
            'selectedVideo' => $selectedVideo,
            'fileLinks' => $courseEnded ? $course->fileLinks : collect(),
            'courseEnded' => $courseEnded,
            'accessibleSurveyLinks' => $accessibleSurveyLinks,
        ]);
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
        return view('dashboard.szkolenia', $this->participantsListingForDashboard($request));
    }

    /**
     * @return array{participants: LengthAwarePaginator, szkoleniaTyp: string, szkoleniaCounts: array{all: int, paid: int, free: int}}
     */
    private function participantsListingForDashboard(Request $request): array
    {
        $userEmail = Auth::user()->email;
        $emailNormalized = Participant::normalizeEmail($userEmail) ?? '';

        $typ = $request->query('typ', 'all');
        if (! in_array($typ, ['all', 'paid', 'free'], true)) {
            $typ = 'all';
        }

        $szkoleniaCounts = DashboardResourceCounts::szkoleniaFilterCountsForEmail($emailNormalized);

        // Wszyscy uczestnicy (wiersze w pneadm.participants) — także gdy kurs został usunięty (LEFT JOIN).
        // access_expires_at w participants decyduje o dostępie do nagrań/materiałów na pnedu.pl.
        $query = Participant::query()
            ->forNormalizedEmail($emailNormalized)
            ->leftJoin('courses', 'participants.course_id', '=', 'courses.id')
            ->select('participants.*')
            ->with([
                'course' => function ($courseQuery) {
                    $courseQuery->select([
                        'id',
                        'title',
                        'start_date',
                        'end_date',
                        'is_paid',
                        'instructor_id',
                        'certificate_download_status',
                    ])->withCount(['videos', 'fileLinks']);
                },
                'course.instructor:id,title,first_name,last_name,gender',
            ])
            ->orderByRaw('COALESCE(courses.start_date, participants.created_at) DESC')
            ->orderByDesc('participants.id');

        if ($typ === 'paid') {
            $query->whereNotNull('courses.id')->where('courses.is_paid', 1);
        } elseif ($typ === 'free') {
            $query->whereNotNull('courses.id')->where('courses.is_paid', 0);
        }

        $participants = $query->paginate(15)->withQueryString();
        $this->hydrateEndedCourseFileLinksForListing($participants);

        return [
            'participants' => $participants,
            'szkoleniaTyp' => $typ,
            'szkoleniaCounts' => $szkoleniaCounts,
        ];
    }

    /**
     * Materiały do pobrania tylko dla zakończonych szkoleń na bieżącej stronie listy (Faza 4).
     */
    private function hydrateEndedCourseFileLinksForListing(LengthAwarePaginator $participants): void
    {
        $tz = config('app.timezone');

        foreach ($participants->getCollection() as $participant) {
            if ($participant->course) {
                $participant->course->setRelation('fileLinks', collect());
            }
        }

        $courseIds = $participants->getCollection()
            ->filter(function (Participant $participant) use ($tz) {
                $course = $participant->course;
                if (! $course || ! $course->end_date || ! $participant->hasActiveAccess()) {
                    return false;
                }

                return Carbon::parse($course->end_date)->timezone($tz)->isPast()
                    && ($course->file_links_count ?? 0) > 0;
            })
            ->pluck('course_id')
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return;
        }

        $linksByCourseId = CourseFileLink::query()
            ->select(['id', 'course_id', 'title', 'url', 'order'])
            ->whereIn('course_id', $courseIds)
            ->orderBy('order')
            ->get()
            ->groupBy('course_id');

        foreach ($participants->getCollection() as $participant) {
            $course = $participant->course;
            if (! $course || ! $linksByCourseId->has($course->id)) {
                continue;
            }

            $course->setRelation('fileLinks', $linksByCourseId->get($course->id));
        }
    }
}
