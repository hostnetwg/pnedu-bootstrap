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
            ->assertSee('Materiały szkoleniowe są już dostępne na Twoim koncie')
            ->assertSee('Nagranie i zaświadczenie pojawią się wkrótce')
            ->assertSee('możesz też zajrzeć później na swoje konto na pnedu.pl')
            ->assertSee('Zaloguj się na pnedu.pl')
            ->assertSee('window.top.location.replace', false);
    }

    public function test_thank_you_page_for_authenticated_user_uses_app_layout_and_dashboard_cta(): void
    {
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('post-training.thank-you', ['course' => 1]))
            ->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu')
            ->assertSee('Przejdź do Twoich zasobów')
            ->assertSee('Strona główna')
            ->assertDontSee('Zaloguj się na pnedu.pl')
            ->assertSee('dashboard', false);
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
            ->assertSee('Data: 15.09.2026 10:30')
            ->assertSee('Zaloguj się na pnedu.pl');

        if ($instructor !== null) {
            $response->assertSee('Prowadzący: dr hab. Jan Kowalski')
                ->assertSee('|');
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
            ->assertSee('Data: 01.10.2026 14:00')
            ->assertSee('Materiały szkoleniowe —')
            ->assertSee('już dostępne');
    }

    public function test_thank_you_page_shows_survey_cta_when_active_link_exists(): void
    {
        if (! $this->tablesReady()
            || ! \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('course_survey_links')) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');

        $course = Course::query()->create([
            'title' => 'KURS Z ANKIETĄ',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $start->copy()->addHours(2),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        \App\Models\PneadmCourseSurveyLink::query()->create([
            'course_id' => $course->id,
            'public_token' => 'tok'.bin2hex(random_bytes(8)),
            'title' => 'Ankieta testowa po szkoleniu',
            'is_active' => true,
            'order' => 1,
            'channel' => 'native',
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Wypełnij ankietę')
            ->assertSee('Ankieta testowa po szkoleniu')
            ->assertSee('A jeśli masz jeszcze minutę');
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
