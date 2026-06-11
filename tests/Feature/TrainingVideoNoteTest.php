<?php

namespace Tests\Feature;

use App\Models\CourseVideo;
use App\Models\Participant;
use App\Models\ParticipantTrainingVideoNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingVideoNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_participant_can_save_training_video_note(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = $this->participantWithVideo();
        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z nagraniem wideo w bazie pneadm.');
        }

        $video = $participant->course->videos->first();
        $user = User::factory()->create([
            'email' => $participant->email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(
            route('dashboard.szkolenia.wideo-note.save', [$participant, $video]),
            ['training_video_note_body' => 'Moja notatka testowa']
        );

        $response->assertOk()
            ->assertJson([
                'saved' => true,
                'video_id' => (int) $video->id,
                'body' => 'Moja notatka testowa',
            ]);

        $this->assertDatabaseHas('participant_training_video_notes', [
            'participant_id' => $participant->id,
            'course_video_id' => $video->id,
            'body' => 'Moja notatka testowa',
        ], 'pneadm');
    }

    public function test_expired_access_still_allows_saving_training_video_note(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = $this->participantWithVideo(true);
        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z nagraniem wideo w bazie pneadm.');
        }

        $video = $participant->course->videos->first();
        $user = User::factory()->create([
            'email' => $participant->email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(
            route('dashboard.szkolenia.wideo-note.save', [$participant, $video]),
            ['training_video_note_body' => 'Notatka po wygaśnięciu dostępu']
        );

        $response->assertOk()->assertJson(['saved' => true]);
    }

    public function test_expired_access_still_allows_viewing_training_page_with_notes(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = $this->participantWithVideo(true);
        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z nagraniem wideo w bazie pneadm.');
        }

        $video = $participant->course->videos->first();
        ParticipantTrainingVideoNote::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'course_video_id' => $video->id,
            ],
            ['body' => 'Trwała notatka']
        );

        $user = User::factory()->create([
            'email' => $participant->email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(
            route('dashboard.szkolenia.wideo', $participant).'?video='.$video->id
        );

        $response->assertOk();
        $response->assertSee('Twoje notatki', false);
        $response->assertSee('Trwała notatka', false);
        $response->assertSee('Dostęp do nagrania i materiałów do pobrania wygasł', false);
        $response->assertDontSee($video->getEmbedUrl(), false);
    }

    public function test_szkolenia_list_shows_indicator_when_participant_has_training_notes(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = $this->participantWithVideo();
        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z nagraniem wideo w bazie pneadm.');
        }

        $video = $participant->course->videos->first();
        ParticipantTrainingVideoNote::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'course_video_id' => $video->id,
            ],
            ['body' => 'Notatka widoczna na liście']
        );

        $user = User::factory()->create([
            'email' => $participant->email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.szkolenia'));

        $response->assertOk();
        $response->assertSee('Masz zapisaną notatkę', false);
        $response->assertSee('bi-journal-text', false);
    }

    public function test_szkolenia_list_fragment_shows_indicator_when_participant_has_training_notes(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = $this->participantWithVideo();
        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z nagraniem wideo w bazie pneadm.');
        }

        $video = $participant->course->videos->first();
        ParticipantTrainingVideoNote::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'course_video_id' => $video->id,
            ],
            ['body' => 'Notatka widoczna w fragmencie listy']
        );

        $user = User::factory()->create([
            'email' => $participant->email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard/fragments/szkolenia-list');

        $response->assertOk();
        $response->assertSee('Masz zapisaną notatkę', false);
    }

    public function test_cannot_save_note_for_video_from_other_course(): void
    {
        if (! $this->canUsePneadmParticipants()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabeli participants w środowisku testowym.');
        }

        $participant = $this->participantWithVideo();
        if (! $participant) {
            $this->markTestSkipped('Brak uczestnika z nagraniem wideo w bazie pneadm.');
        }

        $foreignVideo = CourseVideo::query()
            ->where('course_id', '!=', $participant->course_id)
            ->first();

        if (! $foreignVideo) {
            $this->markTestSkipped('Brak obcego nagrania w bazie pneadm.');
        }

        $user = User::factory()->create([
            'email' => $participant->email,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(
            route('dashboard.szkolenia.wideo-note.save', [$participant, $foreignVideo]),
            ['training_video_note_body' => 'Nie powinno się udać']
        );

        $response->assertNotFound();
    }

    private function participantWithVideo(bool $expired = false): ?Participant
    {
        $query = Participant::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereHas('course.videos');

        if ($expired) {
            $query->whereNotNull('access_expires_at')
                ->where('access_expires_at', '<', now());
        }

        $participant = $query->with(['course.videos'])->first();

        return $participant instanceof Participant ? $participant : null;
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
