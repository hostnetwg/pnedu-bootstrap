<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Instructor;
use App\Support\ClickMeetingConferenceEndedCache;
use App\Support\PostTrainingThankYouPhase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTrainingThankYouPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_thank_you_page_renders_for_guests(): void
    {
        $this->get(route('post-training.thank-you'))
            ->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu')
            ->assertSee('Materiały szkoleniowe, nagranie i zaświadczenie pojawią się wkrótce')
            ->assertSee('Materiały szkoleniowe —')
            ->assertSee('wkrótce')
            ->assertSee('możesz też zajrzeć później na swoje konto na pnedu.pl')
            ->assertDontSee('Materiały szkoleniowe są już dostępne na Twoim koncie')
            ->assertSee('Zaloguj się na pnedu.pl')
            ->assertSee('window.top.location.replace', false);
    }

    public function test_thank_you_page_without_course_shows_materials_for_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('post-training.thank-you'))
            ->assertOk()
            ->assertSee('Materiały szkoleniowe —')
            ->assertSee('Nagranie szkolenia —')
            ->assertSee('Zaświadczenie ukończenia —')
            ->assertSee('Przejdź do Twoich zasobów');
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

    public function test_thank_you_page_shows_early_copy_before_course_start(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($start->copy()->subMinutes(30));

        $course = Course::query()->create([
            'title' => 'KURS PRZED STARTEM',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Szkolenie jeszcze się nie rozpoczęło')
            ->assertDontSee('Materiały szkoleniowe —')
            ->assertDontSee('Wypełnij ankietę');
    }

    public function test_thank_you_page_shows_early_copy_during_course_before_closing_window(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($start->copy()->addMinutes(15));

        $course = Course::query()->create([
            'title' => 'KURS W TRAKCIE',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Wyszedłeś/aś ze spotkania')
            ->assertSee('Szkolenie nadal trwa')
            ->assertDontSee('Nagranie szkolenia —')
            ->assertDontSee('Wypełnij ankietę');
    }

    public function test_thank_you_page_shows_course_title_for_clickmeeting_event(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $eventId = '999'.random_int(100000, 999999);
        $start = Carbon::parse('2026-09-15 10:30:00', 'Europe/Warsaw');
        Carbon::setTestNow($this->duringClosingPhase($start, $start->copy()->addHours(2)));

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
        Carbon::setTestNow($start->copy()->addMinutes(20));

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
            ->assertDontSee('Materiały szkoleniowe —')
            ->assertDontSee('Materiały szkoleniowe są już dostępne');
    }

    public function test_thank_you_page_shows_materials_when_file_link_exists(): void
    {
        if (! $this->tablesReady()
            || ! \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('course_file_links')) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($this->duringClosingPhase($start, $end));

        $course = Course::query()->create([
            'title' => 'KURS Z MATERIAŁAMI',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        \App\Models\CourseFileLink::query()->create([
            'course_id' => $course->id,
            'url' => 'https://drive.google.com/example',
            'title' => 'Pakiet materiałów',
            'order' => 1,
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Materiały szkoleniowe są już dostępne na Twoim koncie')
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
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($this->duringClosingPhase($start, $end));

        $course = Course::query()->create([
            'title' => 'KURS Z ANKIETĄ',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        \App\Models\PneadmCourseSurveyLink::query()->create([
            'course_id' => $course->id,
            'public_token' => 'tok'.bin2hex(random_bytes(8)),
            'title' => 'ANKIETA: TESTOWE SZKOLENIE 3 (2026-08-21)',
            'is_active' => true,
            'order' => 1,
            'channel' => 'native',
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Wypełnij ankietę')
            ->assertSee('A jeśli masz jeszcze minutę')
            ->assertDontSee('ANKIETA: TESTOWE SZKOLENIE 3 (2026-08-21)');
    }

    public function test_thank_you_page_uses_cm_ended_cache_for_early_closing_phase(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($start->copy()->addMinutes(20));
        $eventId = '888'.random_int(100000, 999999);

        $course = Course::query()->create([
            'title' => 'KURS Z WCZEŚNYM CM END',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        CourseOnlineDetail::query()->create([
            'course_id' => $course->id,
            'platform' => 'clickmeeting',
            'clickmeeting_event_id' => $eventId,
        ]);

        ClickMeetingConferenceEndedCache::mark($eventId, $end->copy()->addDay());

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Nagranie szkolenia —')
            ->assertDontSee('Wyszedłeś/aś ze spotkania');
    }

    public function test_invalid_course_query_is_ignored(): void
    {
        $this->get(route('post-training.thank-you', ['course' => 'abc']))
            ->assertOk()
            ->assertSee('Dziękujemy za udział w szkoleniu');
    }

    public function test_thank_you_page_shows_certificate_available_when_download_enabled(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($this->duringClosingPhase($start, $end));

        $course = Course::query()->create([
            'title' => 'KURS Z ZAŚWIADCZENIEM',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        $course->forceFill(['certificate_download_status' => 'download_enabled'])->save();

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Zaświadczenie jest już dostępne do pobrania na Twoim koncie')
            ->assertSee('Zaświadczenie ukończenia —')
            ->assertSee('już dostępne');
    }

    public function test_thank_you_page_hides_certificate_line_when_no_certificate(): void
    {
        if (! $this->tablesReady()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($this->duringClosingPhase($start, $end));

        $course = Course::query()->create([
            'title' => 'KURS BEZ ZAŚWIADCZENIA',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        $course->forceFill(['certificate_download_status' => 'no_certificate'])->save();

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertDontSee('Zaświadczenie ukończenia —')
            ->assertSee('Nagranie pojawi się wkrótce');
    }

    public function test_thank_you_page_shows_recording_available_when_video_exists(): void
    {
        if (! $this->tablesReady()
            || ! \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('course_videos')) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm.');
        }

        $start = Carbon::parse('2026-10-01 14:00:00', 'Europe/Warsaw');
        $end = $start->copy()->addHours(2);
        Carbon::setTestNow($this->duringClosingPhase($start, $end));

        $course = Course::query()->create([
            'title' => 'KURS Z NAGRANIEM',
            'description' => 'Opis',
            'start_date' => $start,
            'end_date' => $end,
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        \App\Models\CourseVideo::query()->create([
            'course_id' => $course->id,
            'video_url' => 'https://example.test/video.mp4',
            'platform' => 'youtube',
            'title' => 'Nagranie 1',
            'order' => 1,
        ]);

        $this->get(route('post-training.thank-you', ['course' => $course->id]))
            ->assertOk()
            ->assertSee('Nagranie szkolenia jest już dostępne na Twoim koncie')
            ->assertSee('Nagranie szkolenia —')
            ->assertSee('już dostępne');
    }

    private function duringClosingPhase(Carbon $start, Carbon $end): Carbon
    {
        $threshold = PostTrainingThankYouPhase::closingThresholdMinutes($start, $end);

        return $end->copy()->subMinutes(max(1, (int) floor($threshold / 2)));
    }

    private function tablesReady(): bool
    {
        return \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('courses')
            && \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('course_online_details');
    }
}
