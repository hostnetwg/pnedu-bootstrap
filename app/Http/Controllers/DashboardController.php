<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        if (!$course || $course->videos->isEmpty()) {
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
        
        // Pobierz uczestników dla zalogowanego użytkownika (case-insensitive) z paginacją
        // Sortuj po dacie szkolenia (start_date) od najnowszych
        $participants = Participant::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
            ->with(['course.instructor', 'course.videos'])
            ->join('courses', 'participants.course_id', '=', 'courses.id')
            ->select('participants.*')
            ->orderBy('courses.start_date', 'desc')
            ->orderBy('participants.created_at', 'desc') // Dodatkowe sortowanie dla szkoleń bez daty
            ->paginate(15);
        
        return view('dashboard.szkolenia', compact('participants'));
    }
}
