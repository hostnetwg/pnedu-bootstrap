<?php

namespace App\Services;

use App\Enums\Analytics\AnalyticsEventName;
use App\Models\Article;
use App\Services\Analytics\AnalyticsModeResolver;
use App\Services\Analytics\AnalyticsSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ArticlePageViewTracker
{
    public function __construct(
        private readonly AnalyticsModeResolver $modeResolver,
        private readonly AnalyticsSessionService $sessions,
        private readonly FunnelSkipService $funnelSkip,
        private readonly MarketingBotDetector $botDetector,
    ) {}

    public function track(Request $request, Article $article): void
    {
        try {
            if (! $this->shouldTrack($request)) {
                return;
            }

            $analyticsSessionId = $this->sessions->id($request);
            if ($analyticsSessionId === null) {
                return;
            }

            $cacheKey = 'blog:article_view:'.$analyticsSessionId.':'.$article->id;
            $cacheMinutes = max(1, (int) config('analytics.session.days', 30)) * 24 * 60;

            if (! Cache::add($cacheKey, 1, now()->addMinutes($cacheMinutes))) {
                return;
            }

            Article::query()->whereKey($article->id)->increment('view_count');
        } catch (Throwable) {
            // Licznik artykułu nie może psuć publicznego bloga.
        }
    }

    public function shouldTrack(Request $request): bool
    {
        if (! config('analytics.enabled', true)) {
            return false;
        }

        if ($this->funnelSkip->shouldSkipTracking($request) || $this->funnelSkip->shouldSkipAnalytics($request)) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->prefetch() || $request->header('Purpose') === 'prefetch') {
            return false;
        }

        if ($this->botDetector->isBotOrPreviewCrawler($request)) {
            return false;
        }

        $analyticsSessionId = $this->sessions->id($request);
        if ($analyticsSessionId === null) {
            return false;
        }

        return $this->modeResolver->shouldTrack(
            AnalyticsEventName::ArticleViewed,
            null,
            $analyticsSessionId,
        );
    }
}
