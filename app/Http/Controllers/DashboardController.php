<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Panel główny konta — strona powitalna (lista szkoleń jest na /dashboard/szkolenia).
     */
    public function index()
    {
        return view('dashboard.index');
    }

    /**
     * Wyświetl widok z osadzonym wideo szkolenia
     */
    public function szkoleniaWideo(Participant $participant)
    {
        $userEmail = Auth::user()->email;

        if (strtolower(trim($participant->email)) !== strtolower(trim($userEmail))) {
            abort(403, 'Brak dostępu do tego nagrania.');
        }

        $participant->load(['course.instructor', 'course.videos', 'course.fileLinks']);
        $course = $participant->course;

        if (! $course || ($course->videos->isEmpty() && $course->fileLinks->isEmpty())) {
            abort(404, 'Brak materiałów dla tego szkolenia.');
        }

        if ($participant->hasExpiredAccess()) {
            abort(403, 'Dostęp do materiałów wygasł.');
        }

        $selectedVideo = null;
        if ($course->videos->isNotEmpty()) {
            $selectedVideoId = (int) request()->query('video', $course->videos->first()->id);
            $selectedVideo = $course->videos->firstWhere('id', $selectedVideoId) ?? $course->videos->first();
        }

        return view('dashboard.szkolenia-wideo', [
            'participant' => $participant,
            'course' => $course,
            'videos' => $course->videos,
            'selectedVideo' => $selectedVideo,
            'fileLinks' => $course->fileLinks,
        ]);
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
     * @return array{participants: LengthAwarePaginator, szkoleniaTyp: string}
     */
    private function participantsListingForDashboard(Request $request): array
    {
        $userEmail = Auth::user()->email;

        $typ = $request->query('typ', 'all');
        if (! in_array($typ, ['all', 'paid', 'free'], true)) {
            $typ = 'all';
        }

        // Wszyscy uczestnicy (wiersze w pneadm.participants) — także gdy kurs został usunięty (LEFT JOIN).
        // access_expires_at w participants decyduje o dostępie do nagrań/materiałów na pnedu.pl.
        $query = Participant::query()
            ->whereRaw('LOWER(TRIM(participants.email)) = ?', [strtolower(trim($userEmail))])
            ->leftJoin('courses', 'participants.course_id', '=', 'courses.id')
            ->select('participants.*')
            ->with(['course.instructor', 'course.videos', 'course.fileLinks'])
            ->orderByRaw('COALESCE(courses.start_date, participants.created_at) DESC')
            ->orderByDesc('participants.id');

        if ($typ === 'paid') {
            $query->whereNotNull('courses.id')->where('courses.is_paid', 1);
        } elseif ($typ === 'free') {
            $query->whereNotNull('courses.id')->where('courses.is_paid', 0);
        }

        return [
            'participants' => $query->paginate(15)->withQueryString(),
            'szkoleniaTyp' => $typ,
        ];
    }
}
