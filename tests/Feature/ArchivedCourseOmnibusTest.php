<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePriceVariant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArchivedCourseOmnibusTest extends TestCase
{
    public function test_archived_course_cards_show_omnibus_when_promotion_active(): void
    {
        try {
            if (! Schema::connection('pneadm')->hasTable('courses')
                || ! Schema::connection('pneadm')->hasTable('course_price_variants')) {
                $this->markTestSkipped('Brak tabel courses / course_price_variants w bazie pneadm.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z testową bazą pneadm.');
        }

        Cache::forget('courses.individual.has_archived.v1');

        $marker = 'OmnibusArchived '.uniqid();
        $course = Course::query()->create([
            'title' => $marker,
            'description' => 'Opis testowy Omnibus na zakończonym szkoleniu',
            'start_date' => Carbon::parse('2026-09-10 10:00:00', 'Europe/Warsaw'),
            'end_date' => Carbon::parse('2026-09-10 12:00:00', 'Europe/Warsaw'),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'show_on_pnedu' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        CoursePriceVariant::query()->forceCreate([
            'course_id' => $course->id,
            'name' => 'Podstawowy',
            'is_active' => true,
            'price' => 449.00,
            'is_promotion' => true,
            'promotion_price' => 365.00,
            'promotion_type' => 'time_limited',
            'promotion_start' => now()->subDays(7),
            'promotion_end' => now()->addDays(7),
            'availability_after_course_end' => CoursePriceVariant::AVAILABILITY_ALWAYS,
            'access_type' => '3',
            'access_duration_value' => 3,
            'access_duration_unit' => 'months',
        ]);

        $this->get(route('courses.individual', ['q' => $marker]).'#szkolenia-zakonczone')
            ->assertOk()
            ->assertSee($marker, false)
            ->assertSee('365,00 PLN')
            ->assertSee('449,00 PLN')
            ->assertSee('Najniższa cena z 30 dni przed obniżką');

        $this->get(route('courses.show', $course->id))
            ->assertOk()
            ->assertSee('Najniższa cena z 30 dni przed obniżką')
            ->assertSee('365,00 PLN');

        $course->priceVariants()->each(fn (CoursePriceVariant $variant) => $variant->forceDelete());
        $course->forceDelete();
        Cache::forget('courses.individual.has_archived.v1');
    }
}
