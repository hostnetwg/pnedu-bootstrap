<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class CourseController extends Controller
{
    /**
     * Display a listing of online live courses.
     *
     * @return \Illuminate\View\View
     */
    public function onlineLive()
    {
        try {
            $courses = Course::with('instructor')
                ->where('type', 'online')
                ->where('is_active', true)
                ->orderBy('start_date', 'desc')
                ->get();
            return view('courses.online-live', compact('courses'));
        } catch (Exception $e) {
            // Log the error for administrators
            Log::error('Error accessing courses: ' . $e->getMessage());
            
            // Return the view with an empty collection and error flag
            return view('courses.online-live', [
                'courses' => collect([]),
                'databaseError' => true
            ]);
        }
    }
}