<?php

namespace App\Http\Middleware;

use App\Services\CoursePageViewTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackCoursePageView
{
    public function __construct(
        private readonly CoursePageViewTracker $tracker,
    ) {}

    public function handle(Request $request, Closure $next, string $pageType = 'course_show'): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful()) {
            return $response;
        }

        $courseId = (int) $request->route('id');
        if ($courseId <= 0) {
            return $response;
        }

        if ($pageType === 'order_form') {
            $this->tracker->trackOrderForm($request, $courseId);
        } else {
            $this->tracker->trackCourseShow($request, $courseId);
        }

        $funnelCookie = $this->tracker->funnelSessionCookie($request);
        if ($funnelCookie) {
            $response->headers->setCookie($funnelCookie);
        }

        return $response;
    }
}
