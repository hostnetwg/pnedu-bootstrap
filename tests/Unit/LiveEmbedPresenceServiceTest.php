<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use App\Services\LiveEmbedPresenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveEmbedPresenceServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    protected $connectionsToTransact = ['pneadm'];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:00', 'Europe/Warsaw'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_touch_is_throttled_and_clear_removes_presence(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak kolumny embed_last_seen_at.');
        }

        [$participant] = $this->seedParticipant();
        $service = app(LiveEmbedPresenceService::class);

        $service->touch($participant);
        $first = $participant->fresh()->liveAccess?->embed_last_seen_at?->format('Y-m-d H:i:s');
        $this->assertSame('2026-07-18 10:00:00', $first);

        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:10', 'Europe/Warsaw'));
        $service->touch($participant->fresh(['liveAccess']));
        $this->assertSame(
            '2026-07-18 10:00:00',
            $participant->fresh()->liveAccess?->embed_last_seen_at?->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:21', 'Europe/Warsaw'));
        $service->touch($participant->fresh(['liveAccess']));
        $this->assertSame(
            '2026-07-18 10:00:21',
            $participant->fresh()->liveAccess?->embed_last_seen_at?->format('Y-m-d H:i:s')
        );

        $service->clear($participant);
        $this->assertNull($participant->fresh()->liveAccess?->embed_last_seen_at);
    }

    /**
     * @return array{0: Participant}
     */
    private function seedParticipant(): array
    {
        $course = Course::query()->create([
            'title' => 'Presence test',
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
            'email' => 'anna-presence@example.test',
        ]);

        ParticipantLiveAccess::query()->create([
            'participant_id' => $participant->id,
            'course_id' => $course->id,
            'platform' => 'clickmeeting',
            'status' => 'success',
        ]);

        return [$participant->fresh(['liveAccess'])];
    }

    private function pneadmReady(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('participant_live_access')
                && Schema::connection('pneadm')->hasColumn('participant_live_access', 'embed_last_seen_at');
        } catch (\Throwable) {
            return false;
        }
    }
}
