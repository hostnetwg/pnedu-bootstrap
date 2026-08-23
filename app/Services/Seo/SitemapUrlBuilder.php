<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\Course;
use App\Models\TrainingOffer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SitemapUrlBuilder
{
    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public function build(): array
    {
        return array_merge(
            $this->staticUrls(),
            $this->safeSection('courses', fn (): array => $this->courseUrls()),
            $this->safeSection('training_offers', fn (): array => $this->trainingOfferUrls()),
            $this->safeSection('articles', fn (): array => $this->articleUrls()),
        );
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function staticUrls(): array
    {
        $now = now()->toAtomString();

        $routes = [
            ['route' => 'home', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['route' => 'rodo', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['route' => 'regulamin', 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['route' => 'polityka-prywatnosci', 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['route' => 'blog.index', 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['route' => 'about.team', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['route' => 'about.accreditation', 'changefreq' => 'yearly', 'priority' => '0.5'],
            ['route' => 'courses.individual', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['route' => 'training-offers.pedagogical-councils.index', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['route' => 'courses.free', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['route' => 'courses.office365', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['route' => 'courses.parent-academy', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['route' => 'courses.director-academy', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ];

        $out = [];

        foreach ($routes as $row) {
            if (! Route::has($row['route'])) {
                Log::warning('Sitemap: pominięto brakującą trasę.', ['route' => $row['route']]);

                continue;
            }

            try {
                $out[] = [
                    'loc' => route($row['route']),
                    'lastmod' => $now,
                    'changefreq' => $row['changefreq'],
                    'priority' => $row['priority'],
                ];
            } catch (Throwable $exception) {
                Log::warning('Sitemap: nie udało się zbudować URL trasy.', [
                    'route' => $row['route'],
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function courseUrls(): array
    {
        if (! $this->pneadmHasTable('courses') || ! $this->pneadmHasColumn('courses', 'show_on_pnedu')) {
            return [];
        }

        if (! Route::has('courses.show')) {
            return [];
        }

        $courses = Course::query()
            ->where('is_active', true)
            ->where('show_on_pnedu', true)
            ->when(
                $this->pneadmHasColumn('courses', 'deleted_at'),
                fn ($query) => $query->whereNull('deleted_at'),
            )
            ->orderBy('id')
            ->get(['id', 'updated_at']);

        return $courses->map(function (Course $course): array {
            $lastmod = $course->updated_at?->toAtomString() ?? now()->toAtomString();

            return [
                'loc' => route('courses.show', $course->id),
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        })->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function trainingOfferUrls(): array
    {
        if (! $this->pneadmHasTable('training_offers') || ! Route::has('training-offers.pedagogical-councils.show')) {
            return [];
        }

        $query = TrainingOffer::query()
            ->where('is_active', true)
            ->where('show_on_pnedu', true);

        if ($this->pneadmHasColumn('training_offers', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $offers = $query->orderBy('slug')->get(['slug', 'updated_at']);

        return $offers->map(function (TrainingOffer $offer): array {
            $lastmod = $offer->updated_at?->toAtomString() ?? now()->toAtomString();

            return [
                'loc' => route('training-offers.pedagogical-councils.show', $offer->slug),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        })->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function articleUrls(): array
    {
        if (! $this->pneadmHasTable('articles') || ! Route::has('blog.show')) {
            return [];
        }

        $query = Article::query()
            ->where('status', Article::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        if ($this->pneadmHasColumn('articles', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($this->pneadmHasColumn('articles', 'sort_order')) {
            $query->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at');
        } else {
            $query->orderByDesc('published_at')->orderByDesc('created_at');
        }

        $articles = $query->get(['slug', 'updated_at']);

        return $articles->map(function (Article $article): array {
            $lastmod = $article->updated_at?->toAtomString() ?? now()->toAtomString();

            return [
                'loc' => route('blog.show', $article->slug),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        })->all();
    }

    /**
     * @param  callable(): array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>  $builder
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function safeSection(string $section, callable $builder): array
    {
        try {
            return $builder();
        } catch (Throwable $exception) {
            Log::warning('Sitemap: pominięto sekcję dynamiczną.', [
                'section' => $section,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function pneadmHasTable(string $table): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function pneadmHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('pneadm')->hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }
}
