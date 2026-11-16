<?php

namespace Tests\Feature;

use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClosedTrainingSchoolCertificatePageTest extends TestCase
{
    private ?int $caseId = null;

    private ?int $courseId = null;

    /** @var list<int> */
    private array $participantIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pneadm.api_url' => 'https://pneadm.test',
            'services.pneadm.api_token' => 'test-api-token',
            'services.pneadm.timeout' => 5,
        ]);

        if (! Schema::connection('pneadm')->hasColumn('closed_training_cases', 'school_certificate_token')
            || ! Schema::connection('pneadm')->hasColumn('closed_training_cases', 'school_certificate_token_expires_at')) {
            $this->markTestSkipped('Brak kolumny ważności linku do zaświadczeń w bazie pneadm.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->participantIds !== []) {
            DB::connection('pneadm')->table('certificate_email_logs')->whereIn('participant_id', $this->participantIds)->delete();
            DB::connection('pneadm')->table('certificates')->whereIn('participant_id', $this->participantIds)->delete();
            DB::connection('pneadm')->table('participants')->whereIn('id', $this->participantIds)->delete();
        }

        if ($this->courseId !== null) {
            DB::connection('pneadm')->table('courses')->where('id', $this->courseId)->delete();
        }

        if ($this->caseId !== null) {
            DB::connection('pneadm')->table('closed_training_case_events')->where('closed_training_case_id', $this->caseId)->delete();
            DB::connection('pneadm')->table('closed_training_cases')->where('id', $this->caseId)->delete();
        }

        parent::tearDown();
    }

    public function test_school_page_shows_send_and_download_status_and_download_does_not_mark_participant(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->caseId = (int) DB::connection('pneadm')->table('closed_training_cases')->insertGetId([
            'source' => 'manual',
            'status' => 'in_progress',
            'topic' => 'Rada',
            'contact_name' => 'Dyrekcja',
            'message' => 'Zapytanie.',
            'school_certificate_token' => $token,
            'school_certificate_token_expires_at' => now()->addMonths(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->courseId = (int) DB::connection('pneadm')->table('courses')->insertGetId([
            'title' => 'Szkolenie rady online',
            'description' => 'Opis',
            'start_date' => now()->subHours(3),
            'end_date' => now()->subHour(),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'closed',
            'is_active' => true,
            'certificate_download_status' => 'download_enabled',
            'closed_training_case_id' => $this->caseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $downloadedId = $this->insertParticipant('Anna', 'Kowalska', 'anna.szkola@example.com', 1);
        $waitingId = $this->insertParticipant('Jan', 'Nowak', 'jan.szkola@example.com', 2);

        DB::connection('pneadm')->table('certificates')->insert([
            'participant_id' => $downloadedId,
            'course_id' => $this->courseId,
            'certificate_number' => 'SCH-'.$downloadedId.'/PNE',
            'download_count' => 2,
            'first_downloaded_at' => now()->subMinutes(5),
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('pneadm')->table('certificate_email_logs')->insert([
            'course_id' => $this->courseId,
            'participant_id' => $downloadedId,
            'type' => 'list_link',
            'status' => 'sent',
            'sent_at' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('closed-training-certificates.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('noindex, nofollow', false)
            ->assertSee('Anna Kowalska', false)
            ->assertSee('ważny do', false)
            ->assertSee('Pobranie przez uczestnika', false)
            ->assertSee('text-primary', false)
            ->assertSee('wysłany na e-mail', false)
            ->assertSee('pobrane', false)
            ->assertSee('Jan Nowak', false)
            ->assertSee('nie wysłany', false)
            ->assertSee('nie pobrane', false)
            ->assertSee('Pobierz PDF', false)
            ->assertSee('Poniżej lista uczestników szkolenia, którzy wypełnili formularz obecności udostępniony podczas szkolenia.', false)
            ->assertDontSee('Lista osób zapisanych na to szkolenie.', false)
            ->assertSee('Po tej dacie albo po unieważnieniu na karcie sprawy przestanie działać.', false)
            ->assertSee('text-danger', false)
            ->assertSee('Unieważnij link', false);

        $this->get('/szkolenie-zamkniete/zaswiadczenia/'.str_repeat('a', 64))->assertNotFound();

        Http::fake([
            'https://pneadm.test/api/certificates/ensure' => Http::response([
                'success' => true,
                'certificate_number' => 'SCH-'.$waitingId.'/PNE',
            ], 200),
            'https://pneadm.test/api/certificates/generate' => Http::response('%PDF-1.4 school', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $download = $this->get(route('closed-training-certificates.download', [
            'token' => $token,
            'participant' => $waitingId,
        ]));

        $download->assertOk();
        $download->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(0, (int) DB::connection('pneadm')->table('certificates')->where('participant_id', $waitingId)->value('download_count'));
        $this->assertSame(2, (int) DB::connection('pneadm')->table('certificates')->where('participant_id', $downloadedId)->value('download_count'));
        Http::assertNotSent(function ($request): bool {
            return str_contains($request->url(), 'mark-downloaded');
        });

        $this->post(route('closed-training-certificates.revoke', ['token' => $token]))
            ->assertOk()
            ->assertSee('Link został unieważniony', false);

        $this->assertNull(DB::connection('pneadm')->table('closed_training_cases')->where('id', $this->caseId)->value('school_certificate_token'));
        $this->assertNotNull(DB::connection('pneadm')->table('closed_training_case_events')->where('closed_training_case_id', $this->caseId)->where('body', 'Szkoła unieważniła link do listy zaświadczeń ze strony pobierania.')->first());
        $this->get(route('closed-training-certificates.show', ['token' => $token]))->assertNotFound();
        $this->post(route('closed-training-certificates.revoke', ['token' => $token]))->assertNotFound();
    }

    public function test_page_is_hidden_until_the_online_training_ends(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->caseId = (int) DB::connection('pneadm')->table('closed_training_cases')->insertGetId([
            'source' => 'manual',
            'status' => 'in_progress',
            'topic' => 'Jeszcze trwa',
            'contact_name' => 'Dyrekcja',
            'message' => 'Zapytanie.',
            'school_certificate_token' => $token,
            'school_certificate_token_expires_at' => now()->addMonths(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->courseId = (int) DB::connection('pneadm')->table('courses')->insertGetId([
            'title' => 'Szkolenie w trakcie',
            'description' => 'Opis',
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'closed',
            'is_active' => true,
            'closed_training_case_id' => $this->caseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('closed-training-certificates.show', ['token' => $token]))->assertNotFound();
    }

    public function test_expired_school_link_is_hidden(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->caseId = (int) DB::connection('pneadm')->table('closed_training_cases')->insertGetId([
            'source' => 'manual',
            'status' => 'in_progress',
            'topic' => 'Po terminie',
            'contact_name' => 'Dyrekcja',
            'message' => 'Zapytanie.',
            'school_certificate_token' => $token,
            'school_certificate_token_expires_at' => now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->courseId = (int) DB::connection('pneadm')->table('courses')->insertGetId([
            'title' => 'Szkolenie zakończone',
            'description' => 'Opis',
            'start_date' => now()->subHours(3),
            'end_date' => now()->subHour(),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'closed',
            'is_active' => true,
            'closed_training_case_id' => $this->caseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('closed-training-certificates.show', ['token' => $token]))->assertNotFound();
    }

    public function test_prepared_link_opens_before_the_email_is_sent(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->caseId = (int) DB::connection('pneadm')->table('closed_training_cases')->insertGetId([
            'source' => 'manual',
            'status' => 'in_progress',
            'topic' => 'Przed wysyłką',
            'contact_name' => 'Dyrekcja',
            'message' => 'Zapytanie.',
            'school_certificate_token' => $token,
            'school_certificate_token_expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->courseId = (int) DB::connection('pneadm')->table('courses')->insertGetId([
            'title' => 'Szkolenie przed wysyłką',
            'description' => 'Opis',
            'start_date' => now()->subHours(3),
            'end_date' => now()->subHour(),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'closed',
            'is_active' => true,
            'closed_training_case_id' => $this->caseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('closed-training-certificates.show', ['token' => $token]))
            ->assertOk()
            ->assertDontSee('Link jest już aktywny', false)
            ->assertSee('Szkolenie przed wysyłką', false);
    }

    private function insertParticipant(string $firstName, string $lastName, string $email, int $order): int
    {
        $id = (int) Participant::query()->create([
            'course_id' => $this->courseId,
            'order' => $order,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ])->id;
        $this->participantIds[] = $id;

        return $id;
    }
}
