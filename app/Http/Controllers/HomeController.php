<?php

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use App\Support\UpcomingPneduCourses;

class HomeController extends Controller
{
    public function __construct(
        protected StatisticsService $statisticsService,
    ) {}

    public function index()
    {
        $courses = UpcomingPneduCourses::forHomepage();
        $statistics = $this->statisticsService->getStatistics();

        return view('welcome', compact('courses', 'statistics'));
    }
}
