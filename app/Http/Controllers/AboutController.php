<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Wyświetl stronę "Zespół".
     *
     * @return \Illuminate\View\View
     */
    public function team()
    {
        // Pobierz dyrektora (instruktor ID = 1) tylko jeśli ma statut aktywny
        $director = Instructor::where('id', 1)->where('is_active', true)->first();
        
        // Pobierz wszystkich aktywnych trenerów (oprócz dyrektora) posortowanych według ID
        $instructors = Instructor::where('is_active', true)
            ->where('id', '!=', 1)
            ->orderBy('id')
            ->get();
        
        return view('about.team', compact('director', 'instructors'));
    }
}

