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

            // Get instructors who have online courses
            $instructors = \App\Models\Instructor::whereHas('courses', function($q) {
                $q->where('type', 'online')->where('is_active', true);
            })->orderBy('last_name')->get();

            $coursesQuery = Course::with('instructor')
                ->where('type', 'online')
                ->where('is_active', true);

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

            $courses = $coursesQuery
                ->orderBy('start_date', $sort)
                ->paginate(20)
                ->appends([
                    'sort' => $sort,
                    'instructor' => $instructorId,
                    'date_filter' => $dateFilter,
                    'paid_filter' => $paidFilter
                ]);

            return view('courses.online-live', compact('courses', 'sort', 'instructors', 'instructorId', 'dateFilter', 'paidFilter'));
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