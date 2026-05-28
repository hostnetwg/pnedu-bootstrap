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
            ->where('show_on_pnedu', true)
            ->where('type', 'online')
            ->where(function ($query) {
                $query->where('end_date', '>', now())
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery->whereNull('end_date')
                            ->where('start_date', '>', now());
                    });
            })
            ->whereNull('deleted_at')
            ->orderBy('start_date', 'asc')
            ->take(6)
            ->get();

        // Pobierz statystyki z cache
        $statistics = $this->statisticsService->getStatistics();

        return view('welcome', compact('courses', 'statistics'));
    }
} 