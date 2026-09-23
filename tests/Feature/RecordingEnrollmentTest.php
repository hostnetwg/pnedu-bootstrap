<?php

namespace Tests\Feature;

use App\Jobs\SubscribeCertificateRegistrationNewsletterJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecordingEnrollmentTest extends TestCase
{
    private const TOKEN = 'recTokenForPublicForm12345678901234567890123456789012';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pneadm.api_url' => 'https://pneadm.test',
            'services.pneadm.api_token' => 'test-api-token',
            'services.pneadm.timeout' => 5,
        ]);
    }

    public function test_show_renders_form_when_enrollment_is_active(): void
    {
        Http::fake([
            'https://pneadm.test/api/recording-enrollment/status/*' => Http::response([
                'active' => true,
                'course_title' => 'Rada pedagogiczna',
                'course_start_display' => '20.09.2026 09:00',
                'instructor_name' => 'Jan Kowalski',
            ], 200),
        ]);

        $this->get(route('recording-enrollment.show', self::TOKEN))
            ->assertOk()
            ->assertSee('Dostęp do nagrania i zaświadczenia')
            ->assertSee('Rada pedagogiczna')
            ->assertSee('Ustaw hasło dostępu do nagrania.')
            ->assertSee('Hasło do nagrania')
            ->assertSee('Ustaw hasło i zapisz dostęp');
    }

    public function test_show_renders_inactive_message(): void
    {
        Http::fake([
            'https://pneadm.test/api/recording-enrollment/status/*' => Http::response([
                'active' => false,
                'course_title' => 'Rada pedagogiczna',
                'message' => 'Dopisywanie do nagrania jest wyłączone.',
            ], 200),
        ]);

        $this->get(route('recording-enrollment.show', self::TOKEN))
            ->assertOk()
            ->assertSee('Dopisywanie do nagrania jest wyłączone.');
    }

    public function test_submit_requires_password_before_creating_an_account(): void
    {
        if (! $this->usersSchemaReady()) {
            $this->markTestSkipped('Baza testing nie ma aktualnej tabeli users.');
        }
        Http::fake([
            'https://pneadm.test/api/recording-enrollment/status/*' => Http::response([
                'active' => true,
                'course_title' => 'Rada pedagogiczna',
            ], 200),
        ]);

        $this->from(route('recording-enrollment.show', self::TOKEN))
            ->post(route('recording-enrollment.submit', self::TOKEN), [
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'email' => 'brak-hasla-'.uniqid().'@example.test',
                'rodo_consent' => '1',
            ])->assertRedirect(route('recording-enrollment.show', self::TOKEN))
            ->assertSessionHasErrors('password');

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->assertGuest();
    }

    public function test_submit_creates_account_and_logs_in_when_password_is_set(): void
    {
        if (! $this->usersSchemaReady()) {
            $this->markTestSkipped('Baza testing nie ma aktualnej tabeli users.');
        }

        Queue::fake();

        $email = 'rec-enroll-'.uniqid().'@example.test';
        \App\Models\User::withTrashed()->where('email', $email)->forceDelete();

        Http::fake([
            'https://pneadm.test/api/recording-enrollment/status/*' => Http::response([
                'active' => true,
                'course_title' => 'Rada pedagogiczna',
            ], 200),
            'https://pneadm.test/api/recording-enrollment/register' => Http::response([
                'success' => true,
                'updated' => false,
                'has_pnedu_account' => true,
                'next_url' => 'http://edu.localhost:8081/login?email='.urlencode($email),
                'email_sent' => true,
                'email_failed' => false,
                'message' => 'Jesteś na liście szkolenia.',
            ], 200),
        ]);

        $this->post(route('recording-enrollment.submit', self::TOKEN), [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => $email,
            'password' => 'haslo-nagranie-1',
            'password_confirmation' => 'haslo-nagranie-1',
            'rodo_consent' => '1',
            'newsletter_consent' => '1',
        ])->assertOk()
            ->assertSee('Konto gotowe')
            ->assertSee('Przejdź do nagrania')
            ->assertSee('całkowicie usunąć konto na pnedu.pl')
            ->assertDontSee('Dokończ rejestrację');

        $this->assertAuthenticated();
        $user = \App\Models\User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('haslo-nagranie-1', $user->password));

        Queue::assertPushed(SubscribeCertificateRegistrationNewsletterJob::class);

        $user->forceDelete();
    }

    public function test_submit_existing_account_does_not_change_password(): void
    {
        if (! $this->usersSchemaReady()) {
            $this->markTestSkipped('Baza testing nie ma aktualnej tabeli users.');
        }

        Queue::fake();

        $email = 'rec-enroll-existing-'.uniqid().'@example.test';
        \App\Models\User::withTrashed()->where('email', $email)->forceDelete();
        $user = \App\Models\User::query()->create([
            'first_name' => 'Ewa',
            'last_name' => 'Malinowska',
            'email' => $email,
            'password' => 'dotychczasowe-haslo',
            'email_verified_at' => now(),
        ]);

        Http::fake([
            'https://pneadm.test/api/recording-enrollment/status/*' => Http::response([
                'active' => true,
                'course_title' => 'Rada pedagogiczna',
            ], 200),
            'https://pneadm.test/api/recording-enrollment/register' => Http::response([
                'success' => true,
                'updated' => false,
                'has_pnedu_account' => true,
                'next_url' => 'http://edu.localhost:8081/login?email='.urlencode($email),
                'email_sent' => true,
                'message' => 'Zaloguj się.',
            ], 200),
        ]);

        $this->post(route('recording-enrollment.submit', self::TOKEN), [
            'first_name' => 'Ewa',
            'last_name' => 'Malinowska',
            'email' => $email,
            'password' => 'nowe-haslo-ktorego-nie-zapisujemy',
            'password_confirmation' => 'nowe-haslo-ktorego-nie-zapisujemy',
            'rodo_consent' => '1',
        ])->assertOk()
            ->assertSee('Hasła nie zmieniliśmy')
            ->assertSee('Zaloguj się na pnedu.pl')
            ->assertDontSee('Konto gotowe');

        $this->assertGuest();
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('dotychczasowe-haslo', $user->password));

        Queue::assertNothingPushed();

        $user->forceDelete();
    }

    public function test_register_form_locks_email_from_training_list(): void
    {
        $email = 'anna@szkola.pl';
        $lock = hash_hmac('sha256', $email, 'test-api-token');
        $query = http_build_query([
            'email' => $email,
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email_lock' => $lock,
        ]);

        $this->get('/register?'.$query)
            ->assertOk()
            ->assertSee('value="Anna"', false)
            ->assertSee('value="Nowak"', false)
            ->assertSee('value="anna@szkola.pl"', false)
            ->assertSee('readonly', false)
            ->assertSee('Tego adresu nie można zmienić')
            ->assertSee('klauzulą informacyjną RODO')
            ->assertDontSee('otrzymywanie newslettera');

        $this->from('/register?'.$query)->post('/register', [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'inny@szkola.pl',
            'email_lock' => $lock,
            'password' => 'password',
            'password_confirmation' => 'password',
            'rodo_consent' => '1',
        ])->assertRedirect('/register?'.$query)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function usersSchemaReady(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('users')
            && \Illuminate\Support\Facades\Schema::hasColumn('users', 'deleted_at')
            && \Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name')
            && \Illuminate\Support\Facades\Schema::hasColumn('users', 'email_verified_at');
    }
}
