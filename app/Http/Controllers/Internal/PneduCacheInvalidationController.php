<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Support\UpcomingPneduCourses;
use Illuminate\Http\JsonResponse;

class PneduCacheInvalidationController extends Controller
{
    public function forgetUpcomingCourses(): JsonResponse
    {
        UpcomingPneduCourses::forgetCache();

        return response()->json(['ok' => true]);
    }
}
