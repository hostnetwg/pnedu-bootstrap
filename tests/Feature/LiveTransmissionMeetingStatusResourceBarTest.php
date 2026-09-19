<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseFileLink;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveTransmissionMeetingStatusResourceBarTest extends TestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    protected $connectionsToTransact = ['mysql', 'pneadm'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'Europe/Warsaw',
            'services.clickmeeting.url' => 'https://api.clickmeeting.com/v1/',
            'services.clickmeeting.token' => 'test-api-key',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:00', 'Europe/Warsaw'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_meeting_status_includes_resource_links_when_bar_flags_are_on(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'status' => 'active',
                ],
            ], 200),
        ]);

        [$participant] = $this->seedParticipant();
        $course = $participant->course;
        $course->forceFill([
            'certificate_registration_open' => true,
            'certificate_registration_token' => 'reg-live-token',
        ])->save();
        $fileLink = CourseFileLink::query()->create([
            'course_id' => $course->id,
            'url' => 'https://drive.google.com/file/test',
            'title' => 'Slajdy',
            'order' => 0,
        ]);
        $course->onlineDetail->update([
            'live_bar_attendance_enabled' => true,
            'live_bar_materials_enabled' => true,
            'live_bar_survey_enabled' => false,
        ]);

        $user = User::factory()->create(['email' => 'anna@example.test']);

        $this->actingAs($user)
            ->getJson(route('dashboard.szkolenia.transmisja.meeting-status', $participant))
            ->assertOk()
            ->assertJsonPath('ended', false)
            ->assertJsonPath('resource_links.0.key', 'material-'.$fileLink->id)
            ->assertJsonPath('resource_links.0.label', 'Pobierz materiały')
            ->assertJsonCount(1, 'resource_links');
    }

    public function test_meeting_status_includes_live_offer_when_enabled(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'status' => 'active',
                ],
            ], 200),
        ]);

        [$participant] = $this->seedParticipant();
        $promo = Course::query()->create([
            'title' => 'Oferta z meeting-status',
            'description' => 'Opis',
            'start_date' => Carbon::parse('2026-09-22 09:00:00', 'Europe/Warsaw'),
            'end_date' => Carbon::parse('2026-09-22 11:00:00', 'Europe/Warsaw'),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);
        $participant->course->onlineDetail->update([
            'live_offer_course_id' => $promo->id,
            'live_offer_enabled' => true,
        ]);

        $user = User::factory()->create(['email' => 'anna@example.test']);

        $this->actingAs($user)
            ->getJson(route('dashboard.szkolenia.transmisja.meeting-status', $participant))
            ->assertOk()
            ->assertJsonPath('live_offer.course_id', $promo->id)
            ->assertJsonPath('live_offer.title', 'Oferta z meeting-status')
            ->assertJsonPath('live_offer.order_url', route('courses.show', $promo->id, true));

        $participant->course->onlineDetail->update(['live_offer_enabled' => false]);

        $this->actingAs($user)
            ->getJson(route('dashboard.szkolenia.transmisja.meeting-status', $participant))
            ->assertOk()
            ->assertJsonPath('live_offer', null);
    }

    public function test_meeting_status_hides_links_after_flag_is_turned_off(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'status' => 'active',
                ],
            ], 200),
        ]);

        [$participant] = $this->seedParticipant();
        CourseFileLink::query()->create([
            'course_id' => $participant->course_id,
            'url' => 'https://drive.google.com/file/test',
            'title' => 'Slajdy',
            'order' => 0,
        ]);
        $participant->course->onlineDetail->update(['live_bar_materials_enabled' => true]);

        $user = User::factory()->create(['email' => 'anna@example.test']);

        $this->actingAs($user)
            ->getJson(route('dashboard.szkolenia.transmisja.meeting-status', $participant))
            ->assertOk()
            ->assertJsonCount(1, 'resource_links');

        $participant->course->onlineDetail->update(['live_bar_materials_enabled' => false]);

        $this->actingAs($user)
            ->getJson(route('dashboard.szkolenia.transmisja.meeting-status', $participant))
            ->assertOk()
            ->assertJsonPath('resource_links', []);
    }

    /**
     * @return array{0: Participant}
     */
    private function seedParticipant(): array
    {
        $course = Course::query()->create([
            'title' => 'Embed test',
            'description' => 'Opis',
            'start_date' => Carbon::parse('2026-07-18 09:00:00', 'Europe/Warsaw'),
            'end_date' => Carbon::parse('2026-07-18 14:00:00', 'Europe/Warsaw'),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        CourseOnlineDetail::query()->create([
            'course_id' => $course->id,
            'platform' => 'clickmeeting',
            'meeting_link' => 'https://pnedu.clickmeeting.com/testowy-webinar',
            'clickmeeting_event_id' => '10164812',
            'embed_on_pnedu' => true,
        ]);

        $participant = Participant::query()->create([
            'course_id' => $course->id,
            'order' => 1,
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'email' => 'anna@example.test',
        ]);

        ParticipantLiveAccess::query()->create([
            'participant_id' => $participant->id,
            'course_id' => $course->id,
            'platform' => 'clickmeeting',
            'clickmeeting_event_id' => '10164812',
            'room_url' => 'https://pnedu.clickmeeting.com/testowy-webinar',
            'token' => 'TOK99',
            'access_type' => 3,
            'status' => 'success',
            'synced_at' => now(),
        ]);

        return [$participant->fresh(['course.onlineDetail', 'liveAccess'])];
    }

    private function pneadmReady(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('participants')
                && Schema::connection('pneadm')->hasTable('course_online_details')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_bar_attendance_enabled')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_bar_certificate_enabled')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_offer_enabled')
                && Schema::connection('pneadm')->hasTable('course_file_links');
        } catch (\Throwable) {
            return false;
        }
    }
}
