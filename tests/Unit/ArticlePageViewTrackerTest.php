<?php

namespace Tests\Unit;

use App\Enums\Analytics\AnalyticsEventName;
use App\Models\AnalyticsSetting;
use App\Models\Article;
use App\Services\Analytics\AnalyticsModeResolver;
use App\Services\ArticlePageViewTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArticlePageViewTrackerTest extends TestCase
{
    private ?int $articleId = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('analytics.enabled', true);
        config()->set('analytics.sample_rate', 100);
        config()->set('analytics.default_mode', 'standard');
        AnalyticsSetting::forgetSettingsCache();
        $this->primeOverride(null, null);

        if (! Schema::connection('pneadm')->hasTable('articles')) {
            $this->markTestSkipped('Brak tabeli articles w testowej bazie pneadm.');
        }

        $this->articleId = (int) Article::query()->insertGetId([
            'title' => 'Test artykuł '.Str::random(6),
            'slug' => 'test-artykul-'.Str::lower(Str::random(8)),
            'excerpt' => 'Skrót testowy',
            'content_html' => '<p>Treść testowa</p>',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
            'sort_order' => 9999,
            'view_count' => 0,
            'comments_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->articleId !== null) {
            Article::query()->whereKey($this->articleId)->forceDelete();
        }

        AnalyticsSetting::forgetSettingsCache();

        parent::tearDown();
    }

    public function test_analytics_disabled_does_not_increment(): void
    {
        config()->set('analytics.enabled', false);

        $article = Article::query()->findOrFail($this->articleId);
        $request = $this->browserRequest('/blog/'.$article->slug);

        app(ArticlePageViewTracker::class)->track($request, $article);

        $this->assertSame(0, (int) $article->fresh()->view_count);
    }

    public function test_analytics_opt_out_cookie_does_not_increment(): void
    {
        $article = Article::query()->findOrFail($this->articleId);
        $request = $this->browserRequest('/blog/'.$article->slug);
        $request->cookies->set('pne_skip_analytics', '1');

        app(ArticlePageViewTracker::class)->track($request, $article);

        $this->assertSame(0, (int) $article->fresh()->view_count);
    }

    public function test_bot_user_agent_does_not_increment(): void
    {
        $article = Article::query()->findOrFail($this->articleId);
        $request = Request::create('/blog/'.$article->slug, 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)',
        ]);

        app(ArticlePageViewTracker::class)->track($request, $article);

        $this->assertSame(0, (int) $article->fresh()->view_count);
    }

    public function test_runtime_override_off_does_not_increment(): void
    {
        $this->primeOverride(false, 'standard');

        $article = Article::query()->findOrFail($this->articleId);
        $request = $this->browserRequest('/blog/'.$article->slug);

        app(ArticlePageViewTracker::class)->track($request, $article);

        $this->assertSame(0, (int) $article->fresh()->view_count);
    }

    public function test_increments_once_per_analytics_session(): void
    {
        $article = Article::query()->findOrFail($this->articleId);
        $sessionId = (string) Str::uuid();
        $request = $this->browserRequest('/blog/'.$article->slug, $sessionId);

        $tracker = app(ArticlePageViewTracker::class);
        $tracker->track($request, $article);
        $tracker->track($request, $article->fresh());

        $this->assertSame(1, (int) $article->fresh()->view_count);
    }

    public function test_light_mode_allows_article_view_event(): void
    {
        $this->primeOverride(null, 'light');

        $resolver = new AnalyticsModeResolver;

        $this->assertTrue($resolver->shouldTrack(
            AnalyticsEventName::ArticleViewed,
            null,
            (string) Str::uuid(),
        ));
    }

    private function browserRequest(string $uri, ?string $analyticsSessionId = null): Request
    {
        $request = Request::create($uri, 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        if ($analyticsSessionId !== null) {
            $request->cookies->set((string) config('analytics.session.cookie', 'pne_analytics_sid'), $analyticsSessionId);
        }

        return $request;
    }

    private function primeOverride(?bool $enabled, ?string $mode): void
    {
        $model = new AnalyticsSetting;
        $model->enabled_override = $enabled;
        $model->default_mode_override = $mode;

        Cache::put(AnalyticsSetting::SETTINGS_CACHE_KEY, $model, 60);
    }
}
