<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingAccessLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_account_redirects_to_login_with_intended_training_url(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = Participant::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->first();

        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z e-mailem w bazie pneadm do testu.');
        }

        $wrongUser = User::factory()->create([
            'email' => 'wrong-account-'.uniqid('', true).'@example.test',
            'email_verified_at' => now(),
        ]);

        $url = route('dashboard.szkolenia.wideo', $participant);

        $response = $this->actingAs($wrongUser)->get($url);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('training_access_relogin', true);
        $response->assertSessionHas('login_email_hint', $participant->email);
        $response->assertSessionHas('url.intended', $url);
    }

    private function canUsePneadmParticipants(): bool
    {
        try {
            Participant::query()->limit(1)->exists();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
