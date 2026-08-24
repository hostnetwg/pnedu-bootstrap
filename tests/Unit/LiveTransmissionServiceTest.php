<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use App\Services\LiveTransmissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveTransmissionServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_first_embed_reuses_primary_token_without_minting(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'name' => 'TEST',
                    'access_type' => 3,
                    'room_pin' => '225723416',
                    'room_url' => 'https://pnedu.clickmeeting.com/testowy-webinar',
                ],
            ], 200),
            'api.clickmeeting.com/v1/conferences/10164812/tokens' => Http::response([
                'access_tokens' => [
                    [
                        'token' => 'TOK99',
                        'sent_to_email' => 'anna@example.test',
                        'first_use_date' => null,
                    ],
                ],
            ], 200),
            'api.clickmeeting.com/v1/conferences/10164812/room/autologin_hash' => Http::response([
                'autologin_hash' => 'HASH99',
            ], 200),
        ]);

        [$participant] = $this->seedParticipant();

        $result = app(LiveTransmissionService::class)->buildForParticipant($participant);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['token_rotated']);
        $this->assertSame('TOK99', $participant->fresh()->liveAccess?->token);
        $this->assertNotNull($participant->fresh()->liveAccess?->embed_token_consumed_at);
        $this->assertStringContainsString('l=HASH99', (string) $result['iframe_src']);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'autologin_hash')) {
                return false;
            }

            return $request['token'] === 'TOK99';
        });

        Http::assertNotSent(fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/tokens')
            && (int) ($request['how_many'] ?? 0) === 1);
        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_rejoin_mints_new_token_and_deletes_old(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'access_type' => 3,
                    'room_pin' => '225723416',
                    'room_url' => 'https://pnedu.clickmeeting.com/testowy-webinar',
                ],
            ], 200),
            'api.clickmeeting.com/v1/conferences/10164812/tokens' => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'access_tokens' => [
                            [
                                'token' => 'TOK99',
                                'sent_to_email' => 'anna@example.test',
                                'first_use_date' => '2026-07-18T09:30:00+02:00',
                            ],
                        ],
                    ], 200);
                }

                if ($request->method() === 'DELETE') {
                    return Http::response([
                        'status' => 'OK',
                        'message' => 'The tokens is not accessible anymore',
                    ], 200);
                }

                return Http::response([
                    'access_tokens' => [
                        [
                            'token' => 'NEWTOK',
                            'sent_to_email' => null,
                            'first_use_date' => null,
                        ],
                    ],
                ], 200);
            },
            'api.clickmeeting.com/v1/conferences/10164812/room/autologin_hash' => Http::response([
                'autologin_hash' => 'HASH_NEW',
            ], 200),
        ]);

        [$participant] = $this->seedParticipant();
        $participant->liveAccess->forceFill([
            'embed_token_consumed_at' => now()->subMinute(),
        ])->save();

        $result = app(LiveTransmissionService::class)->buildForParticipant(
            $participant->fresh(['liveAccess', 'course.onlineDetail'])
        );

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['token_rotated']);
        $this->assertSame('NEWTOK', $participant->fresh()->liveAccess?->token);
        $this->assertNotNull($participant->fresh()->liveAccess?->embed_token_consumed_at);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_ends_with($request->url(), '/tokens')
                && (int) ($request['how_many'] ?? 0) === 1;
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_ends_with($request->url(), '/tokens');
        });
    }

    public function test_rejects_when_embed_disabled(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        [$participant] = $this->seedParticipant(embedOnPnedu: false);

        $result = app(LiveTransmissionService::class)->buildForParticipant($participant);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('nie jest włączony', (string) ($result['error'] ?? ''));
    }

    public function test_record_embed_entry_persists_first_and_last_timestamps(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        [$participant] = $this->seedParticipant();
        $service = app(LiveTransmissionService::class);

        Carbon::setTestNow(Carbon::parse('2026-07-18 10:05:00', 'Europe/Warsaw'));
        $service->recordEmbedEntry($participant);

        $first = $participant->fresh()->liveAccess;
        $this->assertNotNull($first?->embed_first_entered_at);
        $this->assertNotNull($first?->embed_last_entered_at);
        $this->assertTrue($first->hasEnteredEmbedOnPnedu());

        Carbon::setTestNow(Carbon::parse('2026-07-18 11:15:00', 'Europe/Warsaw'));
        $service->recordEmbedEntry($participant->fresh(['liveAccess']));

        $second = $participant->fresh()->liveAccess;
        $this->assertSame(
            $first->embed_first_entered_at?->toDateTimeString(),
            $second->embed_first_entered_at?->toDateTimeString(),
        );
        $this->assertSame('2026-07-18 11:15:00', $second->embed_last_entered_at?->format('Y-m-d H:i:s'));
    }

    /**
     * @return array{0: Participant}
     */
    private function seedParticipant(bool $embedOnPnedu = true): array
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
            'embed_on_pnedu' => $embedOnPnedu,
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
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'embed_on_pnedu')
                && Schema::connection('pneadm')->hasTable('participant_live_access')
                && Schema::connection('pneadm')->hasColumn('participant_live_access', 'embed_last_entered_at');
        } catch (\Throwable) {
            return false;
        }
    }
}
