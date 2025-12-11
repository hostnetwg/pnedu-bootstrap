<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Participant;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Wyświetl listę szkoleń użytkownika
     */
    public function szkolenia()
    {
        $userEmail = Auth::user()->email;
        
        // Pobierz uczestników dla zalogowanego użytkownika (case-insensitive) z paginacją
        // Sortuj po dacie szkolenia (start_date) od najnowszych
        $participants = Participant::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
            ->with(['course.instructor'])
            ->join('courses', 'participants.course_id', '=', 'courses.id')
            ->select('participants.*')
            ->orderBy('courses.start_date', 'desc')
            ->orderBy('participants.created_at', 'desc') // Dodatkowe sortowanie dla szkoleń bez daty
            ->paginate(15);
        
        return view('dashboard.szkolenia', compact('participants'));
    }
}
