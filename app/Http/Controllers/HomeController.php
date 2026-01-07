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
            ->where('start_date', '>', now())
            ->whereNull('deleted_at')
            ->where(function($query) {
                $query->where('source_id_old', 'certgen_Publigo')
                      ->orWhere('source_id_old', 'BD:Certgen-education');
            })
            ->orderBy('start_date', 'asc')
            ->take(6)
            ->get();

        // Pobierz statystyki z cache
        $statistics = $this->statisticsService->getStatistics();

        return view('welcome', compact('courses', 'statistics'));
    }
} 