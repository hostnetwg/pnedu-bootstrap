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
        
        // Pobierz uczestników dla zalogowanego użytkownika (case-insensitive)
        $participants = Participant::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
            ->with(['course.instructor'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('dashboard.szkolenia', compact('participants'));
    }
}
