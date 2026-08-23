<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoSitemapTest extends TestCase
{
    public function test_sitemap_returns_xml_with_urlset(): void
    {
        $response = $this->get('/sitemap.xml');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $body = (string) $response->getContent();
        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringContainsString('<loc>'.route('home').'</loc>', $body);
    }

    public function test_robots_txt_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response
            ->assertOk()
            ->assertSee('Allow: /')
            ->assertSee('Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml');
    }
}
