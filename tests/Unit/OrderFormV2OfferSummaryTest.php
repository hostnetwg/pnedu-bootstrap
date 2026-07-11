<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\CoursePriceVariant;
use App\Models\Instructor;
use App\Support\OrderFormV2OfferSummary;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderFormV2OfferSummaryTest extends TestCase
{
    #[Test]
    public function it_builds_compact_summary_with_available_course_fields(): void
    {
        $course = new Course([
            'title' => '<strong>Testowe szkolenie</strong>',
            'start_date' => '2026-08-15 10:00:00',
            'end_date' => '2026-08-15 12:30:00',
            'type' => 'online',
            'is_paid' => true,
        ]);
        $course->id = 531;
        $course->recording_access = '3 miesiące';
        $course->additional_info = 'Materiały PDF, certyfikat';
        $course->setRelation('instructor', new Instructor([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'photo' => 'instructors/anna.jpg',
        ]));
        $course->setRelation('onlineDetail', new CourseOnlineDetail([
            'platform' => 'clickmeeting',
        ]));
        $course->setRelation('priceVariants', collect([
            tap(new CoursePriceVariant, function (CoursePriceVariant $variant) {
                $variant->forceFill([
                    'is_active' => true,
                    'availability_after_course_end' => CoursePriceVariant::AVAILABILITY_ALWAYS,
                ]);
            }),
        ]));

        $summary = OrderFormV2OfferSummary::fromCourse($course, [
            'price' => 365.0,
            'original_price' => 449.0,
            'is_promotion' => true,
            'variant_name' => 'Podstawowy',
        ]);

        $this->assertSame('Testowe szkolenie', $summary['title']);
        $this->assertStringContainsString('15 sierpnia 2026 10:00', $summary['date_line']);
        $this->assertSame('2h 30min', $summary['duration']);
        $this->assertSame('Online', $summary['format_label']);
        $this->assertSame('Clickmeeting', $summary['platform_label']);
        $this->assertNull($summary['variant_label']);
        $this->assertStringContainsString('Materiały PDF', $summary['additional_line']);
        $this->assertStringContainsString('sesja pytań i odpowiedzi', $summary['additional_line']);
        $this->assertSame('3 miesiące', $summary['recording_line']);
        $this->assertSame(365.0, $summary['price_info']['price']);
    }

    #[Test]
    public function it_shows_variant_label_only_when_multiple_active_variants_exist(): void
    {
        $course = new Course(['title' => 'Test', 'is_paid' => true]);
        $course->id = 1;
        $course->setRelation('priceVariants', collect([
            tap(new CoursePriceVariant, function (CoursePriceVariant $variant) {
                $variant->forceFill([
                    'is_active' => true,
                    'availability_after_course_end' => CoursePriceVariant::AVAILABILITY_ALWAYS,
                ]);
            }),
            tap(new CoursePriceVariant, function (CoursePriceVariant $variant) {
                $variant->forceFill([
                    'is_active' => true,
                    'availability_after_course_end' => CoursePriceVariant::AVAILABILITY_ALWAYS,
                ]);
            }),
        ]));

        $summary = OrderFormV2OfferSummary::fromCourse($course, [
            'price' => 100.0,
            'variant_name' => 'Podstawowy',
        ]);

        $this->assertSame('Wariant: Podstawowy', $summary['variant_label']);
    }

    #[Test]
    public function it_hides_platform_for_stationary_courses(): void
    {
        $course = new Course([
            'title' => 'Szkolenie stacjonarne',
            'type' => 'stacjonarne',
            'is_paid' => true,
        ]);
        $course->id = 99;
        $course->setRelation('onlineDetail', new CourseOnlineDetail([
            'platform' => 'clickmeeting',
        ]));

        $summary = OrderFormV2OfferSummary::fromCourse($course, null);

        $this->assertSame('Stacjonarne', $summary['format_label']);
        $this->assertNull($summary['platform_label']);
    }

    #[Test]
    public function it_hides_variant_label_when_name_is_not_meaningful(): void
    {
        $course = new Course(['title' => 'Test', 'is_paid' => true]);
        $course->id = 1;
        $course->setRelation('priceVariants', collect([
            tap(new CoursePriceVariant, function (CoursePriceVariant $variant) {
                $variant->forceFill([
                    'is_active' => true,
                    'availability_after_course_end' => CoursePriceVariant::AVAILABILITY_ALWAYS,
                ]);
            }),
            tap(new CoursePriceVariant, function (CoursePriceVariant $variant) {
                $variant->forceFill([
                    'is_active' => true,
                    'availability_after_course_end' => CoursePriceVariant::AVAILABILITY_ALWAYS,
                ]);
            }),
        ]));

        $summary = OrderFormV2OfferSummary::fromCourse($course, [
            'price' => 100.0,
            'variant_name' => '#80',
        ]);

        $this->assertNull($summary['variant_label']);
    }
}
