<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use App\Services\DashboardCourseLiveAccessService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCourseLiveAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardCourseLiveAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Europe/Warsaw']);
        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:00', 'Europe/Warsaw'));

        $this->service = app(DashboardCourseLiveAccessService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prefers_live_access_room_url_with_token(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-20 12:00:00',
            end: '2026-07-20 14:00:00',
            meetingLink: 'https://fallback.example/room',
            platform: 'clickmeeting',
        );

        ParticipantLiveAccess::query()->create([
            'participant_id' => $participant->id,
            'course_id' => $participant->course_id,
            'platform' => 'clickmeeting',
            'room_url' => 'https://pnedu.clickmeeting.com/wydarzenie',
            'token' => 'TOK123',
            'status' => 'success',
            'synced_at' => now(),
            'expires_at' => Carbon::parse('2026-07-20 14:00:00', 'Europe/Warsaw')->utc(),
        ]);

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertTrue($live->show);
        $this->assertSame('https://pnedu.clickmeeting.com/wydarzenie/TOK123', $live->joinUrl);
        $this->assertSame('ClickMeeting', $live->platformLabel);
        $this->assertSame('until_start', $live->countdownPhase);
        $this->assertFalse($live->joinUnlocked);
    }

    public function test_falls_back_to_meeting_link_without_live_access(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-20 12:00:00',
            end: '2026-07-20 14:00:00',
            meetingLink: 'https://meet.google.com/abc-defg-hij',
            platform: 'google meet',
            password: 'sekret123',
        );

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertTrue($live->show);
        $this->assertSame('https://meet.google.com/abc-defg-hij', $live->joinUrl);
        $this->assertSame('sekret123', $live->password);
        $this->assertSame('Google Meet', $live->platformLabel);
        $this->assertFalse($live->joinUnlocked);
    }

    public function test_join_button_unlocks_two_hours_before_start(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-18 11:30:00',
            end: '2026-07-18 13:00:00',
            meetingLink: 'https://meet.example/soon',
            platform: 'google meet',
        );

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertTrue($live->show);
        $this->assertTrue($live->joinUnlocked);
        $this->assertSame(
            Carbon::parse('2026-07-18 09:30:00', 'Europe/Warsaw')->toIso8601String(),
            $live->joinUnlockAtIso
        );
    }

    public function test_join_button_locked_more_than_two_hours_before_start(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-18 13:00:00',
            end: '2026-07-18 15:00:00',
            meetingLink: 'https://meet.example/later',
            platform: 'google meet',
        );

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertTrue($live->show);
        $this->assertFalse($live->joinUnlocked);
        $this->assertStringContainsString('2 godziny', (string) $live->joinUnlockHint);
    }

    public function test_shows_countdown_until_end_when_course_in_progress(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-18 09:00:00',
            end: '2026-07-18 12:00:00',
            meetingLink: 'https://zoom.example/j/1',
            platform: 'zoom',
        );

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertTrue($live->show);
        $this->assertSame('until_end', $live->countdownPhase);
        $this->assertSame('Do zakończenia szkolenia', $live->countdownLabel);
        $this->assertTrue($live->joinUnlocked);
    }

    public function test_hides_when_course_ended(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-10 10:00:00',
            end: '2026-07-10 12:00:00',
            meetingLink: 'https://zoom.example/j/1',
            platform: 'zoom',
        );

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertFalse($live->show);
    }

    public function test_hides_when_no_end_date_and_start_already_past(): void
    {
        $course = new Course([
            'start_date' => Carbon::parse('2026-07-17 10:00:00', 'Europe/Warsaw'),
            'end_date' => null,
            'type' => 'online',
        ]);

        $this->assertFalse($this->service->isLiveWindowOpen($course));
    }

    public function test_shows_clickmeeting_without_token(): void
    {
        if (! $this->pneadmTablesAvailable()) {
            $this->markTestSkipped('Brak tabel pneadm w środowisku testowym.');
        }

        [$participant] = $this->seedCourseWithParticipant(
            start: '2026-07-20 12:00:00',
            end: '2026-07-20 14:00:00',
            meetingLink: 'https://pnedu.clickmeeting.com/open-room',
            platform: 'clickmeeting',
        );

        ParticipantLiveAccess::query()->create([
            'participant_id' => $participant->id,
            'course_id' => $participant->course_id,
            'platform' => 'clickmeeting',
            'room_url' => 'https://pnedu.clickmeeting.com/open-room',
            'token' => null,
            'status' => 'success',
            'synced_at' => now(),
        ]);

        $live = $this->service->forParticipant($participant->fresh(['course.onlineDetail', 'liveAccess']));

        $this->assertTrue($live->show);
        $this->assertSame('https://pnedu.clickmeeting.com/open-room', $live->joinUrl);
    }

    /**
     * @return array{0: Participant, 1: Course}
     */
    private function seedCourseWithParticipant(
        string $start,
        string $end,
        string $meetingLink,
        string $platform,
        ?string $password = null,
    ): array {
        $course = Course::query()->create([
            'title' => 'Szkolenie live test',
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
            'platform' => $platform,
            'meeting_link' => $meetingLink,
            'meeting_password' => $password,
        ]);

        $participant = Participant::query()->create([
            'course_id' => $course->id,
            'order' => 1,
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'email' => 'anna.live.'.uniqid('', true).'@example.test',
        ]);

        return [$participant, $course];
    }

    private function pneadmTablesAvailable(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('participants')
                && \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('courses')
                && \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('course_online_details')
                && \Illuminate\Support\Facades\Schema::connection('pneadm')->hasTable('participant_live_access');
        } catch (\Throwable) {
            return false;
        }
    }
}
