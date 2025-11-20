<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $courses = Course::with('priceVariants')
            ->where('is_active', true)
            ->where('type', 'online')
            ->where('is_paid', 1)
            ->where('start_date', '>', now())
            ->where('source_id_old', 'certgen_Publigo')
            ->orderBy('start_date', 'asc')
            ->take(6)
            ->get();
        return view('welcome', compact('courses'));
    }
} 