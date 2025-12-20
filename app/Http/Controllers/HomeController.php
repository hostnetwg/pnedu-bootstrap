<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

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

        // Pobierz statystyki z cache
        $statistics = $this->statisticsService->getStatistics();

        return view('welcome', compact('courses', 'statistics'));
    }
} 