<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Wyświetl widok z osadzonym wideo szkolenia
     */
    public function szkoleniaWideo(Participant $participant)
    {
        $userEmail = Auth::user()->email;

        if (strtolower(trim($participant->email)) !== strtolower(trim($userEmail))) {
            abort(403, 'Brak dostępu do tego nagrania.');
        }

        $participant->load(['course.instructor', 'course.videos']);
        $course = $participant->course;

        if (! $course || $course->videos->isEmpty()) {
            abort(404, 'Brak nagrania dla tego szkolenia.');
        }

        if ($participant->hasExpiredAccess()) {
            abort(403, 'Dostęp do nagrania wygasł.');
        }

        $selectedVideoId = (int) request()->query('video', $course->videos->first()->id);
        $selectedVideo = $course->videos->firstWhere('id', $selectedVideoId) ?? $course->videos->first();

        return view('dashboard.szkolenia-wideo', [
            'participant' => $participant,
            'course' => $course,
            'videos' => $course->videos,
            'selectedVideo' => $selectedVideo,
        ]);
    }

    /**
     * Wyświetl listę szkoleń użytkownika
     */
    public function szkolenia()
    {
        $userEmail = Auth::user()->email;

        // Wszyscy uczestnicy (wiersze w pneadm.participants) — także gdy kurs został usunięty (LEFT JOIN).
        // access_expires_at w participants decyduje o dostępie do nagrań/materiałów na pnedu.pl.
        $participants = Participant::query()
            ->whereRaw('LOWER(TRIM(participants.email)) = ?', [strtolower(trim($userEmail))])
            ->leftJoin('courses', 'participants.course_id', '=', 'courses.id')
            ->select('participants.*')
            ->with(['course.instructor', 'course.videos'])
            ->orderByRaw('COALESCE(courses.start_date, participants.created_at) DESC')
            ->orderByDesc('participants.id')
            ->paginate(15);

        return view('dashboard.szkolenia', compact('participants'));
    }
}
