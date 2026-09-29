<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class HomepageLiveStatisticsTest extends TestCase
{
    public function test_homepage_html_contains_server_rendered_statistics(): void
    {
        $content = $this->renderHomepage([
            'trained_teachers' => 12345,
            'trained_teachers_display' => '12 345',
            'courses_this_year' => 210,
            'courses_this_year_display' => '210',
            'average_rating' => 4.86,
            'average_rating_display' => '4.9',
            'nps' => 72.5,
            'nps_display' => '72.5',
            'last_updated' => Carbon::parse('2026-09-28 12:00:00'),
        ]);

        $this->assertStringContainsString('id="live-stats-title"', $content);
        $this->assertStringContainsString('Dane na żywo', $content);
        $this->assertStringContainsString('>12 345</strong>', $content);
        $this->assertStringContainsString('>210</strong>', $content);
        $this->assertStringContainsString('>4.9</strong>', $content);
        $this->assertStringContainsString('>72.5</strong>', $content);
        $this->assertStringContainsString('Przeszkolonych nauczycieli', $content);
        $this->assertStringContainsString('Dane aktualizowane raz dziennie na podstawie systemu PNE.', $content);
        $this->assertStringNotContainsString('data-target=', $content);
        $this->assertStringNotContainsString('class="counter">0<', $content);
        $this->assertStringNotContainsString('>0</strong>', $content);
        $this->assertStringNotContainsString('0/5', $content);
    }

    public function test_homepage_hides_statistics_when_no_trustworthy_value_exists(): void
    {
        $content = $this->renderHomepage([
            'trained_teachers' => null,
            'trained_teachers_display' => null,
            'courses_this_year' => null,
            'courses_this_year_display' => null,
            'average_rating' => null,
            'average_rating_display' => null,
            'nps' => null,
            'nps_display' => null,
            'last_updated' => null,
        ]);

        $this->assertStringNotContainsString('Dane na żywo', $content);
        $this->assertStringNotContainsString('id="live-stats-title"', $content);
        $this->assertStringNotContainsString('0/5', $content);
        $this->assertStringNotContainsString('Przeszkolonych nauczycieli', $content);
    }

    /**
     * @param  array<string, mixed>  $statistics
     */
    private function renderHomepage(array $statistics): string
    {
        return view('welcome', [
            'courses' => new Collection,
            'statistics' => $statistics,
            'homepageLiveNotice' => null,
            'featuredTrainingOffers' => new Collection,
            'featuredTrainingOffersTotal' => 0,
            'homepageTestimonials' => new Collection,
            'homepageTestimonialsTotal' => 0,
            'showTestimonialDate' => false,
            'errors' => new ViewErrorBag,
        ])->render();
    }
}
