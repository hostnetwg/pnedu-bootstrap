<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Seo\SitemapUrlBuilder;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function __construct(
        private readonly SitemapUrlBuilder $sitemapUrlBuilder,
    ) {}

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

        $urls = $this->sitemapUrlBuilder->build();

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
}
