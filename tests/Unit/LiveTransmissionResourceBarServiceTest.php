<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseFileLink;
use App\Models\CourseOnlineDetail;
use App\Models\PneadmCourseSurveyLink;
use App\Services\LiveTransmissionResourceBarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveTransmissionResourceBarServiceTest extends TestCase
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

    public function test_returns_empty_when_flags_are_off(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks($course);

        $this->assertSame([], $links);
    }

    public function test_attendance_stays_hidden_on_authenticated_embed_even_with_flag_and_token(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();
        $course->forceFill([
            'certificate_registration_open' => true,
            'certificate_registration_token' => 'reg-live-token',
            'certificate_registration_starts_at' => Carbon::parse('2026-08-01 10:00:00', 'Europe/Warsaw'),
            'certificate_registration_ends_at' => Carbon::parse('2026-08-01 12:00:00', 'Europe/Warsaw'),
        ])->save();
        $course->onlineDetail->update(['live_bar_attendance_enabled' => true]);

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail', 'fileLinks']));

        $this->assertSame([], $links);
        $this->assertFalse(LiveTransmissionResourceBarService::ATTENDANCE_VISIBLE_ON_AUTHENTICATED_EMBED);
    }

    public function test_attendance_stays_hidden_on_guest_embed_because_gate_form_collects_it(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();
        $course->forceFill([
            'certificate_registration_open' => true,
            'certificate_registration_token' => 'reg-live-token',
        ])->save();
        $course->onlineDetail->update(['live_bar_attendance_enabled' => true]);

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks(
            $course->fresh(['onlineDetail', 'fileLinks']),
            forGuest: true
        );

        $this->assertSame([], $links);
    }

    public function test_guest_embed_hides_certificate_even_when_flag_is_on(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();
        $course->forceFill(['certificate_download_status' => 'download_enabled'])->save();
        $course->onlineDetail->update(['live_bar_certificate_enabled' => true]);

        $guest = app(LiveTransmissionResourceBarService::class)->visibleLinks(
            $course->fresh(['onlineDetail']),
            forGuest: true
        );
        $this->assertSame([], $guest);

        $auth = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail']));
        $this->assertCount(1, $auth);
        $this->assertSame('certificate', $auth[0]['key']);
    }

    public function test_certificate_appears_when_flag_and_download_status_are_set(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();
        $course->forceFill(['certificate_download_status' => 'download_enabled'])->save();
        $course->onlineDetail->update(['live_bar_certificate_enabled' => true]);

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail']));

        $this->assertCount(1, $links);
        $this->assertSame('certificate', $links[0]['key']);
        $this->assertSame('Pobierz zaświadczenie', $links[0]['label']);
        $this->assertSame(route('dashboard.zaswiadczenia.course', $course->id, true), $links[0]['url']);

        $course->forceFill(['certificate_download_status' => 'in_preparation'])->save();
        $linksOff = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail']));
        $this->assertSame([], $linksOff);
    }

    public function test_materials_appear_before_course_end_when_flag_is_on(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();
        CourseFileLink::query()->create([
            'course_id' => $course->id,
            'url' => 'https://drive.google.com/file/test',
            'title' => 'Slajdy',
            'order' => 0,
        ]);
        $course->onlineDetail->update(['live_bar_materials_enabled' => true]);

        $this->assertTrue($course->end_date->isFuture());

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail', 'fileLinks']));

        $this->assertCount(1, $links);
        $this->assertSame('material-'.$course->fileLinks()->first()->id, $links[0]['key']);
        $this->assertSame('Pobierz materiały', $links[0]['label']);
    }

    public function test_survey_appears_when_active_and_uncheck_hides_all(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse();
        PneadmCourseSurveyLink::query()->create([
            'course_id' => $course->id,
            'url' => 'https://forms.gle/test',
            'title' => 'Ankieta po szkoleniu',
            'public_token' => 'surveytokenlivebar001234567890123456',
            'is_active' => true,
            'order' => 0,
        ]);
        $course->onlineDetail->update([
            'live_bar_survey_enabled' => true,
            'live_bar_materials_enabled' => false,
            'live_bar_attendance_enabled' => false,
        ]);

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail', 'fileLinks']));
        $this->assertCount(1, $links);
        $this->assertSame('Wypełnij ankietę', $links[0]['label']);
        $this->assertStringContainsString('/ankieta/surveytokenlivebar001234567890123456', $links[0]['url']);

        $course->onlineDetail->update(['live_bar_survey_enabled' => false]);
        $linksOff = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail', 'fileLinks']));
        $this->assertSame([], $linksOff);
    }

    public function test_visible_offer_appears_when_enabled_for_another_course(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $live = $this->seedCourse();
        $promo = Course::query()->create([
            'title' => 'Następne szkolenie promo',
            'description' => '<p>Krótki opis promocji z <strong>HTML</strong> &nbsp; i  spacjami.</p><p>Druga linia.</p>',
            'image' => 'courses/images/promo-test.jpg',
            'start_date' => Carbon::parse('2026-09-21 15:00:00', 'Europe/Warsaw'),
            'end_date' => Carbon::parse('2026-09-21 17:00:00', 'Europe/Warsaw'),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);
        $live->onlineDetail->update([
            'live_offer_course_id' => $promo->id,
            'live_offer_enabled' => true,
            'live_offer_enabled_at' => now(),
        ]);

        $offer = app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail']));

        $this->assertNotNull($offer);
        $this->assertSame($promo->id, $offer['course_id']);
        $this->assertSame('Następne szkolenie promo', $offer['title']);
        $this->assertSame('21.09.2026 15:00', $offer['start_date']);
        $this->assertSame(route('payment.order-form', $promo->id, true), $offer['order_url']);
        $this->assertSame(route('courses.show', $promo->id, true), $offer['description_url']);
        $this->assertNotNull($offer['image_url']);
        $this->assertStringContainsString('courses/images/promo-test.jpg', (string) $offer['image_url']);
        $this->assertSame(
            "Krótki opis promocji z HTML i spacjami.\nDruga linia.",
            $offer['description']
        );
        $this->assertSame(120, $offer['auto_hide_seconds']);
        $this->assertTrue($offer['auto_hide']);
        $this->assertNotNull($offer['expires_at']);

        $live->onlineDetail->update(['live_offer_enabled' => false, 'live_offer_enabled_at' => null]);
        $this->assertNull(app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail'])));
    }

    public function test_visible_offer_auto_hides_after_two_minutes(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $live = $this->seedCourse();
        $promo = Course::query()->create([
            'title' => 'Promo auto hide',
            'description' => 'Test',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(5)->addHours(2),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);
        $live->onlineDetail->update([
            'live_offer_course_id' => $promo->id,
            'live_offer_enabled' => true,
            'live_offer_auto_hide' => true,
            'live_offer_enabled_at' => now()->subSeconds(121),
        ]);

        $this->assertNull(
            app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail']))
        );
        $details = $live->fresh('onlineDetail')->onlineDetail;
        $this->assertFalse((bool) $details->live_offer_enabled);
        $this->assertNull($details->live_offer_enabled_at);
    }

    public function test_visible_offer_stays_when_auto_hide_disabled(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $live = $this->seedCourse();
        $promo = Course::query()->create([
            'title' => 'Promo bez limitu',
            'description' => 'Test',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(5)->addHours(2),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'certificate_format' => '{nr}/PNE',
        ]);
        $live->onlineDetail->update([
            'live_offer_course_id' => $promo->id,
            'live_offer_enabled' => true,
            'live_offer_auto_hide' => false,
            'live_offer_enabled_at' => now()->subMinutes(30),
        ]);

        $offer = app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail']));
        $this->assertNotNull($offer);
        $this->assertFalse($offer['auto_hide']);
        $this->assertNull($offer['expires_at']);
        $this->assertTrue((bool) $live->fresh('onlineDetail')->onlineDetail->live_offer_enabled);
    }

    public function test_offer_description_preserves_line_breaks_and_prefers_offer_html(): void
    {
        $course = new Course([
            'offer_description_html' => '<p>Pierwszy akapit</p><p>Drugi<br>z enterem</p>',
            'description' => 'Fallback nie powinien wejść',
        ]);

        $text = app(LiveTransmissionResourceBarService::class)->offerDescriptionText($course);

        $this->assertSame("Pierwszy akapit\nDrugi\nz enterem", $text);
    }

    public function test_short_offer_description_is_truncated_for_modal(): void
    {
        $course = new Course([
            'offer_description_html' => '<p>'.str_repeat('A ', 200).'</p>',
        ]);

        $short = app(LiveTransmissionResourceBarService::class)->shortOfferDescription($course);

        $this->assertNotNull($short);
        $this->assertLessThanOrEqual(LiveTransmissionResourceBarService::OFFER_DESCRIPTION_MAX + 3, mb_strlen($short));
        $this->assertStringEndsWith('...', $short);
    }

    public function test_visible_offer_ignores_the_course_currently_on_air(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $live = $this->seedCourse();
        $live->onlineDetail->update([
            'live_offer_course_id' => $live->id,
            'live_offer_enabled' => true,
        ]);

        $this->assertNull(app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail'])));
    }

    public function test_returns_empty_when_embed_is_off(): void
    {
        if (! $this->pneadmReady()) {
            $this->markTestSkipped('Brak tabel/kolumn pneadm.');
        }

        $course = $this->seedCourse(embedOnPnedu: false);
        $course->forceFill([
            'certificate_registration_open' => true,
            'certificate_registration_token' => 'reg-live-token',
        ])->save();
        $course->onlineDetail->update(['live_bar_attendance_enabled' => true]);

        $links = app(LiveTransmissionResourceBarService::class)->visibleLinks($course->fresh(['onlineDetail']));
        $this->assertSame([], $links);
    }

    private function seedCourse(bool $embedOnPnedu = true): Course
    {
        $course = Course::query()->create([
            'title' => 'Resource bar test',
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
            'live_bar_attendance_enabled' => false,
            'live_bar_materials_enabled' => false,
            'live_bar_survey_enabled' => false,
        ]);

        return $course->fresh(['onlineDetail', 'fileLinks']);
    }

    private function pneadmReady(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('course_online_details')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_bar_attendance_enabled')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_bar_certificate_enabled')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_offer_enabled')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_offer_enabled_at')
                && Schema::connection('pneadm')->hasColumn('course_online_details', 'live_offer_auto_hide')
                && Schema::connection('pneadm')->hasTable('course_file_links')
                && Schema::connection('pneadm')->hasTable('course_survey_links');
        } catch (\Throwable) {
            return false;
        }
    }
}
