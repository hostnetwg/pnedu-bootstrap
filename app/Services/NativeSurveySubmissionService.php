<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyTestimonial;
use App\Support\SurveyAvatarPresets;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NativeSurveySubmissionService
{
    /**
     * @param  array<string, mixed>  $answers
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identity
     */
    public function submit(Survey $survey, array $answers, array $identity = []): SurveyResponse
    {
        $survey->loadMissing('questions');

        $responseData = [];
        $errors = [];

        foreach ($survey->questions as $question) {
            if ($question->question_type === 'testimonial') {
                continue;
            }

            $raw = $answers[(string) $question->id] ?? null;
            $normalized = $this->normalizeAnswer($question, $raw);

            if ($normalized === null || $normalized === '' || $normalized === []) {
                if ($question->question_type === 'rating') {
                    $errors['answers.'.$question->id] = 'To pytanie jest wymagane.';
                }

                continue;
            }

            $responseData[$question->question_text] = $normalized;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::connection('pneadm')->transaction(function () use ($survey, $responseData, $identity) {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'response_data' => $responseData,
                'submitted_at' => now(),
                'respondent_id' => $identity['respondent_id'] ?? null,
                'participant_id' => $identity['participant_id'] ?? null,
                'respondent_email' => $identity['respondent_email'] ?? null,
            ]);

            $survey->increment('total_responses');

            return $response;
        });
    }

    /**
     * Zapis rekomendacji po ankiecie (osobny krok).
     *
     * @param  array<string, mixed>  $payload
     */
    public function storeTestimonial(
        Survey $survey,
        SurveyResponse $response,
        array $payload,
        ?UploadedFile $avatarFile = null,
    ): SurveyTestimonial {
        $quote = trim((string) ($payload['quote'] ?? ''));
        $name = trim((string) ($payload['author_name'] ?? ''));

        if ($quote === '' || $name === '') {
            throw ValidationException::withMessages([
                'quote' => 'Podaj treść rekomendacji.',
                'author_name' => 'Podaj imię i nazwisko.',
            ]);
        }

        if ((int) $response->survey_id !== (int) $survey->id) {
            throw ValidationException::withMessages([
                'quote' => 'Nie udało się powiązać rekomendacji z ankietą.',
            ]);
        }

        $consent = filter_var($payload['publish_consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $avatar = $this->resolveAvatar($payload, $avatarFile);

        return SurveyTestimonial::create([
            'survey_id' => $survey->id,
            'survey_response_id' => $response->id,
            'course_id' => $survey->course_id,
            'author_name' => $name,
            'author_role' => trim((string) ($payload['author_role'] ?? '')) ?: null,
            'author_city' => trim((string) ($payload['author_city'] ?? '')) ?: null,
            'avatar_type' => $avatar['type'],
            'avatar_preset' => $avatar['preset'],
            'avatar_path' => $avatar['path'],
            'quote' => $quote,
            'rating' => isset($payload['rating']) && is_numeric($payload['rating']) ? (int) $payload['rating'] : null,
            'publish_consent' => $consent,
            'is_published' => false,
            'display_order' => 100,
        ]);
    }

    private function normalizeAnswer(SurveyQuestion $question, mixed $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($question->question_type) {
            'rating' => is_numeric($raw) ? (int) $raw : null,
            'multiple_choice', 'availability' => array_values(array_filter((array) $raw, fn ($v) => filled($v))),
            'single_choice', 'text', 'date', 'time' => is_string($raw) ? trim($raw) : $raw,
            default => $raw,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{type: ?string, preset: ?string, path: ?string}
     */
    private function resolveAvatar(array $payload, ?UploadedFile $avatarFile): array
    {
        $mode = (string) ($payload['avatar_mode'] ?? 'preset');

        // Najpierw upload — w formularzu nadal może iść ukryte avatar_preset=none.
        if ($mode === 'upload') {
            if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
                $dir = public_path('images/avatars/uploads');
                File::ensureDirectoryExists($dir);
                $ext = strtolower($avatarFile->getClientOriginalExtension() ?: 'jpg');
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $ext = 'jpg';
                }
                $filename = Str::uuid()->toString().'.'.$ext;
                $avatarFile->move($dir, $filename);

                return [
                    'type' => 'upload',
                    'preset' => null,
                    'path' => 'images/avatars/uploads/'.$filename,
                ];
            }

            return [
                'type' => 'none',
                'preset' => null,
                'path' => null,
            ];
        }

        if ($mode === 'none' || ($payload['avatar_preset'] ?? null) === SurveyAvatarPresets::NONE) {
            return [
                'type' => 'none',
                'preset' => null,
                'path' => null,
            ];
        }

        $preset = SurveyAvatarPresets::migrateLegacyKey((string) ($payload['avatar_preset'] ?? ''));
        if ($preset === null || ! SurveyAvatarPresets::isValid($preset)) {
            return [
                'type' => 'none',
                'preset' => null,
                'path' => null,
            ];
        }

        return [
            'type' => 'preset',
            'preset' => $preset,
            'path' => null,
        ];
    }
}
