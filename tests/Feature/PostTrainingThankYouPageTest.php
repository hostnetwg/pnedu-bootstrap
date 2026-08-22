<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Instructor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTrainingThankYouPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_thank_you_page_renders_for_guests(): void
    {
        $this->get(route('post-training.thank-you'))
            ->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu')
            ->assertSee('Zaloguj się na pnedu.pl');
    }

    public function test_thank_you_page_shows_course_title_for_clickmeeting_event(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $eventId = '999'.random_int(100000, 999999);
        $start = Carbon::parse('2026-09-15 10:30:00', 'Europe/Warsaw');

        $instructor = null;
        if (\Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('instructors')) {
            $instructor = Instructor::query()->create([
                'title' => 'dr hab.',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'email' => 'jan.kowalski.'.uniqid('', true).'@example.test',
                'gender' => 'male',
                'is_active' => true,
            ]);
        }

        $course = Course::query()->create([
            'title' => 'TESTOWE SZKOLENIE CM',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $start->copy()->addHours(2),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
            'instructor_id' => $instructor?->id,
        ]);

        CourseOnlineDetail::query()->create([
            'course_id' => $course->id,
            'platform' => 'clickmeeting',
            'clickmeeting_event_id' => $eventId,
        ]);

        $response = $this->get(route('post-training.thank-you', ['event' => $eventId]));

        $response->assertOk()
            ->assertSee('TESTOWE SZKOLENIE CM')
            ->assertSee('Data rozpoczęcia: 15.09.2026 10:30')
            ->assertSee('Zaloguj się na pnedu.pl');

        if ($instructor !== null) {
            $response->assertSee('Prowadzący: dr hab. Jan Kowalski');
        }
    }

    public function test_invalid_event_query_is_ignored(): void
    {
        $this->get(route('post-training.thank-you', ['event' => 'not-numeric']))
            ->assertOk()
            ->assertDontSee('not-numeric');
    }

    public function test_thank_you_page_shows_course_title_for_course_query_param(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');

        $course = Course::query()->create([
            'title' => 'KURS PO PARAMETRZE COURSE',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $start->copy()->addHours(2),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('KURS PO PARAMETRZE COURSE')
            ->assertSee('Data rozpoczęcia: 01.10.2026 14:00');
    }

    public function test_invalid_course_query_is_ignored(): void
    {
        $this->get(route('post-training.thank-you', ['course' => 'abc']))
            ->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu');
    }

    private function tablesReady(): bool
    {
        return \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('courses')
            && \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('course_online_details');
    }
}
