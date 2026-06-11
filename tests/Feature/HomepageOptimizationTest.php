<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_homepage_has_short_cache_control_when_enabled(): void
    {
        config([
            'seo.homepage.page_cache_max_age' => 60,
            'seo.homepage.page_cache_stale_while_revalidate' => 120,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=60', $cacheControl);
        $this->assertStringContainsString('stale-while-revalidate=120', $cacheControl);
    }

    public function test_authenticated_homepage_omits_public_cache_control(): void
    {
        config(['seo.homepage.page_cache_max_age' => 60]);

        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertStringNotContainsString('max-age=60', $cacheControl);
    }

    public function test_homepage_carousel_uses_lazy_loading_for_secondary_slides(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('loading="lazy"', false);
        $response->assertSee('fetchpriority="high"', false);
    }
}
