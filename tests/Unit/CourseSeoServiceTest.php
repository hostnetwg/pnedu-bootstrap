<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Services\Seo\CourseSeoService;
use Tests\TestCase;

class CourseSeoServiceTest extends TestCase
{
    private CourseSeoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseSeoService;
    }

    public function test_director_academy_listing_meta_matches_report(): void
    {
        $meta = $this->service->listingMeta('courses.director-academy');

        $this->assertSame('Akademia Dyrektora – bezpłatne webinary | PNE', $meta['title']);
        $this->assertStringContainsString('dyrektorów szkół i przedszkoli', $meta['description']);
        $this->assertLessThanOrEqual(160, mb_strlen($meta['description']));
    }

    public function test_course_540_override_from_report(): void
    {
        $course = new Course(['title' => 'Bardzo długi tytuł kursu 540']);
        $course->id = 540;

        $this->assertSame('Nowa podstawa programowa 2026 – szkolenie | PNE', $this->service->seoTitle($course));
        $this->assertStringContainsString('podstawie programowej 2026', $this->service->seoDescription($course));
    }

    public function test_course_548_override_from_report(): void
    {
        $course = new Course(['title' => 'Bardzo długi tytuł kursu 548']);
        $course->id = 548;

        $this->assertSame('Nowelizacja statutu szkoły 2026 – szkolenie | PNE', $this->service->seoTitle($course));
        $this->assertStringContainsString('statucie szkoły', $this->service->seoDescription($course));
    }

    public function test_generic_course_title_is_shortened_with_brand_suffix(): void
    {
        $course = new Course([
            'id' => 999,
            'title' => str_repeat('Szkolenie online dla nauczycieli o TIK i sztucznej inteligencji w pracy szkoły ', 3),
        ]);

        $title = $this->service->seoTitle($course);

        $this->assertStringEndsWith(' | PNE', $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
    }

    public function test_structured_data_contains_course_node(): void
    {
        $course = new Course([
            'title' => 'Nowa podstawa programowa 2026',
            'description' => 'Opis testowy kursu.',
            'is_paid' => false,
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeeks(2),
        ]);
        $course->id = 540;

        $graph = $this->service->structuredDataGraph($course);

        $this->assertSame('https://schema.org', $graph['@context']);
        $this->assertSame('Course', $graph['@graph'][0]['@type']);
        $this->assertSame('BreadcrumbList', $graph['@graph'][1]['@type']);
        $this->assertArrayHasKey('offers', $graph['@graph'][0]);
        $this->assertArrayHasKey('hasCourseInstance', $graph['@graph'][0]);
    }
}
