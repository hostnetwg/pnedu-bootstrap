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
    public function onlineLive(Request $request)
    {
        try {
            $sort = $request->query('sort', 'desc');
            $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
            $instructorId = $request->query('instructor');
            $dateFilter = $request->query('date_filter', 'all');
            $paidFilter = $request->query('paid_filter');
            $typeFilter = $request->query('type_filter');
            $categoryFilter = $request->query('category_filter');
            $searchQuery = $request->query('q');

            // Get instructors who have online courses
            $instructors = \App\Models\Instructor::whereHas('courses', function($q) {
                $q->where('type', 'online')->where('is_active', true);
            })->orderBy('last_name')->get();

            $coursesQuery = Course::with('instructor')
                ->where('is_active', true);

            if ($typeFilter === 'online' || $typeFilter === 'offline') {
                $coursesQuery->where('type', $typeFilter);
            } else {
                $coursesQuery->where('type', 'online'); // default
            }

            if ($instructorId) {
                $coursesQuery->where('instructor_id', $instructorId);
            }

            if ($dateFilter === 'upcoming') {
                $coursesQuery->where('start_date', '>', now());
            } elseif ($dateFilter === 'archived') {
                $coursesQuery->whereNotNull('end_date')->where('end_date', '<', now());
            } elseif ($dateFilter === 'ongoing') {
                $coursesQuery->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    });
            }

            if ($paidFilter === 'paid') {
                $coursesQuery->where('is_paid', 1);
            } elseif ($paidFilter === 'free') {
                $coursesQuery->where('is_paid', 0);
            }

            if ($categoryFilter === 'otwarte') {
                $coursesQuery->where('category', 'open');
            } elseif ($categoryFilter === 'zamknięte') {
                $coursesQuery->where('category', 'closed');
            }

            if (!empty($searchQuery)) {
                $coursesQuery->where(function($q) use ($searchQuery) {
                    $q->where('title', 'like', '%' . $searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $searchQuery . '%');
                });
            }

            $courses = $coursesQuery
                ->orderBy('start_date', $sort)
                ->paginate(20)
                ->appends([
                    'sort' => $sort,
                    'instructor' => $instructorId,
                    'date_filter' => $dateFilter,
                    'paid_filter' => $paidFilter,
                    'type_filter' => $typeFilter,
                    'category_filter' => $categoryFilter,
                    'q' => $searchQuery
                ]);

            return view('courses.online-live', compact('courses', 'sort', 'instructors', 'instructorId', 'dateFilter', 'paidFilter', 'typeFilter', 'categoryFilter', 'searchQuery'));
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

    /**
     * Wyświetl szczegóły szkolenia.
     */
    public function show($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        
        // Debug: sprawdź czy pole offer_description_html istnieje
        \Log::info('Course data:', [
            'id' => $course->id,
            'title' => $course->title,
            'offer_description_html' => $course->offer_description_html ?? 'NULL',
            'has_offer_description' => !empty($course->offer_description_html),
            'trainer' => $course->trainer,
            'trainer_title' => $course->trainer_title,
            'instructor_id' => $course->instructor_id,
            'instructor_title' => $course->instructor->title ?? 'NULL',
            'instructor_full_name' => $course->instructor->full_name ?? 'NULL',
            'instructor_gender' => $course->instructor->gender ?? 'NULL'
        ]);
        
        return view('courses.show', compact('course'));
    }

    /**
     * Wyświetl stronę płatności online.
     */
    public function payOnline($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        return view('courses.pay-online', compact('course'));
    }

    /**
     * Wyświetl formularz zamówienia z odroczonym terminem płatności.
     */
    public function deferredOrder($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        return view('courses.deferred-order', compact('course'));
    }
}