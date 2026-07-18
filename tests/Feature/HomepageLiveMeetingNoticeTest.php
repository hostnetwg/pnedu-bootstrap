<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomepageLiveMeetingNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Europe/Warsaw']);
        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:00', 'Europe/Warsaw'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_homepage_does_not_show_live_notice(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('homepage-live-notice', false);
        $response->assertDontSee('Dołącz do spotkania', false);
    }

    public function test_authenticated_user_sees_nearest_live_notice_on_homepage(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        $email = 'homepage.live.'.uniqid('', true).'@example.test';

        $this->seedLiveCourse(
            email: $email,
            title: 'Szkolenie dalsze',
            start: '2026-07-25 12:00:00',
            end: '2026-07-25 14:00:00',
            meetingLink: 'https://meet.example/dalsze',
        );

        $this->seedLiveCourse(
            email: $email,
            title: 'Szkolenie bliższe',
            start: '2026-07-20 12:00:00',
            end: '2026-07-20 14:00:00',
            meetingLink: 'https://meet.example/blizsze',
            password: 'haslo99',
        );

        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('homepage-live-notice', false);
        $response->assertSee('Szkolenie bliższe', false);
        $response->assertDontSee('Szkolenie dalsze', false);
        $response->assertSee('data-join-url="https://meet.example/blizsze"', false);
        $response->assertSee('data-live-join-btn', false);
        $response->assertSee('disabled pe-none', false);
        $response->assertSee('Link do spotkania zostanie aktywowany 2 godziny', false);
        $response->assertSee('haslo99', false);
        $response->assertSee('Dołącz do spotkania', false);
        $response->assertSee(route('dashboard.szkolenia'), false);
        $response->assertSee('Wszystkie szkolenia', false);
    }

    public function test_authenticated_user_without_live_course_sees_no_notice(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        $user = User::factory()->create([
            'email' => 'no.live.'.uniqid('', true).'@example.test',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('homepage-live-notice', false);
        $response->assertDontSee('Dołącz do spotkania', false);
    }

    private function seedLiveCourse(
        string $email,
        string $title,
        string $start,
        string $end,
        string $meetingLink,
        ?string $password = null,
    ): Participant {
        $course = Course::query()->create([
            'title' => $title,
            'description' => 'Opis',
            'start_date' => Carbon::parse($start, 'Europe/Warsaw'),
            'end_date' => Carbon::parse($end, 'Europe/Warsaw'),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        CourseOnlineDetail::query()->create([
            'course_id' => $course->id,
            'platform' => 'google meet',
            'meeting_link' => $meetingLink,
            'meeting_password' => $password,
        ]);

        return Participant::query()->create([
            'course_id' => $course->id,
            'order' => 1,
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'email' => $email,
            'email_normalized' => Participant::normalizeEmail($email),
        ]);
    }

    private function pneadmTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('participants')
                && Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('course_online_details');
        } catch (\Throwable) {
            return false;
        }
    }
}
