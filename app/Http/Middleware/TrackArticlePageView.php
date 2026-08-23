<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Services\Analytics\AnalyticsSessionService;
use App\Services\ArticlePageViewTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackArticlePageView
{
    public function __construct(
        private readonly ArticlePageViewTracker $tracker,
        private readonly AnalyticsSessionService $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful()) {
            return $response;
        }

        $slug = $request->route('slug');
        if (! is_string($slug) || $slug === '') {
            return $response;
        }

        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($article) {
            $this->tracker->track($request, $article);
            $this->sessions->appendCookie($response, $request);
        }

        return $response;
    }
}
