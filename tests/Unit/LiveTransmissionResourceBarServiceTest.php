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
            'description' => 'Opis',
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
        ]);

        $offer = app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail']));

        $this->assertNotNull($offer);
        $this->assertSame($promo->id, $offer['course_id']);
        $this->assertSame('Następne szkolenie promo', $offer['title']);
        $this->assertSame('21.09.2026 15:00', $offer['start_date']);
        $this->assertSame(route('courses.show', $promo->id, true), $offer['order_url']);

        $live->onlineDetail->update(['live_offer_enabled' => false]);
        $this->assertNull(app(LiveTransmissionResourceBarService::class)->visibleOffer($live->fresh(['onlineDetail'])));
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
                && Schema::connection('pneadm')->hasTable('course_file_links')
                && Schema::connection('pneadm')->hasTable('course_survey_links');
        } catch (\Throwable) {
            return false;
        }
    }
}
