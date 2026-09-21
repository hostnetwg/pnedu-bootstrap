<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseOnlineDetail;
use App\Models\Participant;
use App\Services\LiveTransmissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuestLiveTransmissionTest extends TestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    protected $connectionsToTransact = ['pneadm'];

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

    public function test_unknown_token_is_not_found(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $this->get('/live/'.str_repeat('a', 64))->assertNotFound();
    }

    public function test_closed_embed_shows_gate_form_then_creates_participant_and_embed(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'access_type' => 1,
                    'status' => 'active',
                    'room_pin' => '225723416',
                    'room_url' => 'https://pnedu.clickmeeting.com/testowy-webinar',
                ],
            ], 200),
            'api.clickmeeting.com/v1/conferences/10164812/room/autologin_hash' => Http::response([
                'autologin_hash' => 'HASH_GUEST_PAGE',
            ], 200),
        ]);

        $token = str_repeat('b', 64);
        $course = $this->seedClosedCourse($token);

        $this->get(route('guest-live.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Wejście na szkolenie', false)
            ->assertSee('Nazwisko', false)
            ->assertDontSee('HASH_GUEST_PAGE', false)
            ->assertSee('noindex', false);

        $this->getJson(route('guest-live.meeting-status', ['token' => $token]))
            ->assertStatus(401);

        $this->post(route('guest-live.register', ['token' => $token]), [
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'email' => 'anna.kowalska@example.test',
            'rodo_consent' => '1',
        ])->assertRedirect(route('guest-live.show', ['token' => $token]));

        $this->assertDatabaseHas('participants', [
            'course_id' => $course->id,
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'email' => 'anna.kowalska@example.test',
        ], 'pneadm');

        $this->get(route('guest-live.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('HASH_GUEST_PAGE', false)
            ->assertDontSee('Wejdź ponownie', false);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'autologin_hash')) {
                return false;
            }

            return ($request['email'] ?? '') === 'anna.kowalska@example.test'
                && ($request['nickname'] ?? '') === 'Anna Kowalska';
        });

        $this->getJson(route('guest-live.meeting-status', ['token' => $token]))
            ->assertOk()
            ->assertJsonPath('ended', false)
            ->assertJsonPath('resource_links', []);
    }

    public function test_same_email_updates_existing_participant_instead_of_duplicating(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $token = str_repeat('g', 64);
        $course = $this->seedClosedCourse($token);
        Participant::query()->create([
            'course_id' => $course->id,
            'order' => 1,
            'first_name' => 'Anna',
            'last_name' => 'Stara',
            'email' => 'anna.kowalska@example.test',
            'email_normalized' => 'anna.kowalska@example.test',
        ]);

        $this->post(route('guest-live.register', ['token' => $token]), [
            'first_name' => 'Anna',
            'last_name' => 'Nowa',
            'email' => 'anna.kowalska@example.test',
            'rodo_consent' => '1',
        ])->assertRedirect(route('guest-live.show', ['token' => $token]));

        $this->assertSame(1, Participant::query()->where('course_id', $course->id)->count());
        $this->assertSame('Nowa', Participant::query()->where('course_id', $course->id)->value('last_name'));
    }

    public function test_gate_form_requires_last_name_and_rodo(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $token = str_repeat('h', 64);
        $course = $this->seedClosedCourse($token);

        $this->from(route('guest-live.show', ['token' => $token]))
            ->post(route('guest-live.register', ['token' => $token]), [
                'first_name' => 'Anna',
                'email' => 'anna@example.test',
            ])
            ->assertRedirect(route('guest-live.show', ['token' => $token]))
            ->assertSessionHasErrors(['last_name', 'rodo_consent']);

        $this->assertSame(0, Participant::query()->where('course_id', $course->id)->count());
    }

    public function test_open_category_with_token_does_not_enter(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $token = str_repeat('c', 64);
        $course = $this->seedClosedCourse($token);
        $course->forceFill(['category' => 'open'])->save();

        $this->get(route('guest-live.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('nieaktywny', false);
    }

    public function test_guest_rejects_token_access_type(): void
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
        ]);

        $token = str_repeat('e', 64);
        $this->seedClosedCourse($token);

        $this->get(route('guest-live.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Wejście na szkolenie', false)
            ->assertDontSee('Dla wszystkich', false);

        $this->post(route('guest-live.register', ['token' => $token]), [
            'first_name' => 'Ewa',
            'last_name' => 'Nowak',
            'email' => 'ewa@example.test',
            'rodo_consent' => '1',
        ])->assertRedirect(route('guest-live.show', ['token' => $token]));

        $this->get(route('guest-live.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Dla wszystkich', false);
    }

    public function test_build_for_guest_skips_tokens_on_open_cm(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        Http::fake([
            'api.clickmeeting.com/v1/conferences/10164812' => Http::response([
                'conference' => [
                    'id' => 10164812,
                    'access_type' => 1,
                    'room_pin' => '225723416',
                    'room_url' => 'https://pnedu.clickmeeting.com/testowy-webinar',
                ],
            ], 200),
            'api.clickmeeting.com/v1/conferences/10164812/room/autologin_hash' => Http::response([
                'autologin_hash' => 'HASH_GUEST',
            ], 200),
        ]);

        $course = $this->seedClosedCourse(str_repeat('f', 64));
        $result = app(LiveTransmissionService::class)->buildForGuest(
            $course,
            'guest.abc@pnedu.pl',
            'Gość'
        );

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('l=HASH_GUEST', (string) $result['iframe_src']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/tokens'));
    }

    public function test_sitemap_does_not_list_guest_live(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        config(['seo.block_search_indexing' => false]);
        $token = str_repeat('d', 64);
        $this->seedClosedCourse($token);

        $response = $this->get('/sitemap.xml');
        if ($response->status() === 404) {
            $this->markTestSkipped('Sitemap zablokowany w tym środowisku.');
        }

        $response->assertOk();
        $this->assertStringNotContainsString('/live/'.$token, $response->getContent());
        $this->assertStringNotContainsString('guest-live', $response->getContent());
    }

    private function seedClosedCourse(string $token): Course
    {
        $course = Course::query()->create([
            'title' => 'Rada pedagogiczna guest live',
            'description' => 'Opis',
            'start_date' => Carbon::parse('2026-07-18 09:00:00', 'Europe/Warsaw'),
            'end_date' => Carbon::parse('2026-07-18 14:00:00', 'Europe/Warsaw'),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'closed',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);

        CourseOnlineDetail::query()->create([
            'course_id' => $course->id,
            'platform' => 'clickmeeting',
            'meeting_link' => 'https://pnedu.clickmeeting.com/testowy-webinar',
            'clickmeeting_event_id' => '10164812',
            'embed_on_pnedu' => true,
            'guest_live_token' => $token,
            'live_bar_attendance_enabled' => false,
            'live_bar_materials_enabled' => false,
            'live_bar_survey_enabled' => false,
            'live_bar_certificate_enabled' => false,
        ]);

        return $course->fresh(['onlineDetail']);
    }

    private function pneadmReady(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('course_online_details')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'guest_live_token')
                && Schema::connection('pneadm')->hasTable('participants')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'embed_on_pnedu');
        } catch (\Throwable) {
            return false;
        }
    }
}
