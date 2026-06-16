<?php

namespace App\Services;

use App\Models\CoursePageStatsDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoursePageViewTracker
{
    public function __construct(
        private readonly MarketingAttributionService $attribution,
        private readonly FunnelSkipService $funnelSkip,
    ) {}

    public function shouldTrack(Request $request): bool
    {
        if ($this->funnelSkip->shouldSkipTracking($request)) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->prefetch() || $request->header('Purpose') === 'prefetch') {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua === '' || str_contains($ua, 'bot') || str_contains($ua, 'spider') || str_contains($ua, 'crawl')) {
            return false;
        }

        return true;
    }

    public function trackCourseShow(Request $request, int $courseId): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        $this->incrementOncePerVisitor($request, $courseId, 'course_show', 'views_course_show');
    }

    public function trackOrderForm(Request $request, int $courseId): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        $this->incrementOncePerVisitor($request, $courseId, 'order_form', 'views_order_form');
    }

    private function incrementOncePerVisitor(Request $request, int $courseId, string $eventType, string $column): void
    {
        $sid = $this->visitorSid($request);
        $date = now()->toDateString();
        $cacheKey = "funnel:view:{$sid}:{$courseId}:{$eventType}:{$date}";

        if (! Cache::add($cacheKey, 1, now()->endOfDay())) {
            return;
        }

        $stat = CoursePageStatsDaily::query()->firstOrCreate(
            ['course_id' => $courseId, 'stat_date' => $date],
            ['views_course_show' => 0, 'views_order_form' => 0],
        );

        $stat->increment($column);
    }

    private function visitorSid(Request $request): string
    {
        $cookieName = (string) config('marketing.funnel_session_cookie', 'pne_funnel_sid');
        $sid = $request->cookie($cookieName);
        if (is_string($sid) && $sid !== '') {
            return $sid;
        }

        return 'ip:'.hash('sha256', (string) $request->ip().'|'.(string) $request->userAgent());
    }

    public function funnelSessionCookie(Request $request): ?\Symfony\Component\HttpFoundation\Cookie
    {
        $cookieName = (string) config('marketing.funnel_session_cookie', 'pne_funnel_sid');
        if ($request->cookie($cookieName)) {
            return null;
        }

        $minutes = max(1, (int) config('marketing.attribution_days', 7)) * 24 * 60;

        return cookie($cookieName, (string) Str::uuid(), $minutes, '/', null, app()->environment('production'), true, false, 'Lax');
    }
}
