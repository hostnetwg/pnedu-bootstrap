<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\TrainingOffer;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        if (config('seo.block_search_indexing')) {
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $base = rtrim((string) config('app.url'), '/');
            $body = "User-agent: *\nAllow: /\n\nSitemap: {$base}/sitemap.xml\n";
        }

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function sitemap(): Response
    {
        if (config('seo.block_search_indexing')) {
            abort(404);
        }

        $urls = array_merge($this->staticUrls(), $this->courseUrls(), $this->trainingOfferUrls(), $this->articleUrls());

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function llms(): Response
    {
        if (config('seo.block_search_indexing')) {
            abort(404);
        }

        $base = rtrim((string) config('app.url'), '/');
        $articles = Article::query()
            ->published()
            ->ordered()
            ->limit(20)
            ->get(['slug', 'title', 'excerpt', 'published_at']);

        $lines = [
            '# Platforma Nowoczesnej Edukacji',
            '',
            '> Akredytowany niepubliczny ośrodek doskonalenia nauczycieli. Serwis publikuje ofertę szkoleń, webinarów i artykuły dla nauczycieli, dyrektorów oraz szkół.',
            '',
            '## Najważniejsze adresy',
            '',
            '- Strona główna: '.$base.'/',
            '- Blog: '.route('blog.index'),
            '- Szkolenia indywidualne: '.route('courses.individual'),
            '- Szkolenia online LIVE: '.route('courses.online-live'),
            '- Szkolenia rad pedagogicznych: '.route('training-offers.pedagogical-councils.index'),
            '- Bezpłatne webinary TIK: '.route('courses.free'),
            '- Kontakt: '.$base.'/#kontakt',
            '- Sitemap XML: '.$base.'/sitemap.xml',
            '',
            '## Tematy serwisu',
            '',
            '- szkolenia online dla nauczycieli',
            '- szkolenia dla dyrektorów i rad pedagogicznych',
            '- TIK, AI w edukacji, Office 365, Canva',
            '- webinary i materiały edukacyjne',
            '- artykuły eksperckie o nowoczesnej edukacji',
            '',
            '## Najnowsze artykuły',
            '',
        ];

        if ($articles->isEmpty()) {
            $lines[] = '- Brak opublikowanych artykułów.';
        } else {
            foreach ($articles as $article) {
                $lines[] = '- ['.$article->plainTitle().']('.route('blog.show', $article->slug).')';
            }
        }

        $lines[] = '';
        $lines[] = '## Zasady cytowania';
        $lines[] = '';
        $lines[] = 'Przy cytowaniu treści podawaj nazwę „Platforma Nowoczesnej Edukacji” oraz link do właściwej podstrony.';

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
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
            $out[] = [
                'loc' => route($row['route']),
                'lastmod' => $now,
                'changefreq' => $row['changefreq'],
                'priority' => $row['priority'],
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function courseUrls(): array
    {
        $courses = Course::query()
            ->where('is_active', true)
            ->where('show_on_pnedu', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'updated_at']);

        return $courses->map(function (Course $course) {
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
        $offers = TrainingOffer::query()
            ->publiclyVisible()
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        return $offers->map(function (TrainingOffer $offer) {
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
        $articles = Article::query()
            ->published()
            ->ordered()
            ->get(['slug', 'updated_at']);

        return $articles->map(function (Article $article) {
            $lastmod = $article->updated_at?->toAtomString() ?? now()->toAtomString();

            return [
                'loc' => route('blog.show', $article->slug),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        })->all();
    }
}
