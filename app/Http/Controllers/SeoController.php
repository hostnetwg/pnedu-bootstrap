<?php

namespace App\Http\Controllers;

use App\Models\Course;
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
        ]);
    }

    public function sitemap(): Response
    {
        if (config('seo.block_search_indexing')) {
            abort(404);
        }

        $urls = array_merge($this->staticUrls(), $this->courseUrls());

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
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
            ['route' => 'blog.sztuczna-inteligencja-w-edukacji', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['route' => 'blog.wykorzystanie-aplikacji-canva', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['route' => 'about.team', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['route' => 'courses.online-live', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['route' => 'courses.individual', 'changefreq' => 'weekly', 'priority' => '0.8'],
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
}
