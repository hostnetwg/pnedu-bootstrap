<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Ochrona przed wielokrotnym wypełnieniem ankiety natywnej.
 *
 * - Nieanonimowa: twardy limit po e-mail / respondent_id / participant_id.
 * - Anonimowa (+ dodatkowa warstwa): cookie w przeglądarce (miękkie).
 */
class NativeSurveyDuplicateGuard
{
    private const COOKIE_PREFIX = 'pne_sv_done_';

    private const COOKIE_MINUTES = 60 * 24 * 400; // ~400 dni

    public function allowsMultiple(): bool
    {
        return SurveySetting::getSettings()->allowsMultipleResponses();
    }

    public function cookieName(int $surveyId): string
    {
        return self::COOKIE_PREFIX.$surveyId;
    }

    public function hasSoftCookie(Request $request, int $surveyId): bool
    {
        return $request->cookie($this->cookieName($surveyId)) === '1';
    }

    public function queueSoftCookie(int $surveyId): void
    {
        Cookie::queue(
            cookie(
                $this->cookieName($surveyId),
                '1',
                self::COOKIE_MINUTES,
                '/',
                null,
                (bool) config('session.secure', false),
                true,
                false,
                'lax'
            )
        );
    }

    /**
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identity
     */
    public function hasHardDuplicate(Survey $survey, array $identity): bool
    {
        $email = isset($identity['respondent_email'])
            ? strtolower(trim((string) $identity['respondent_email']))
            : '';
        $respondentId = isset($identity['respondent_id'])
            ? trim((string) $identity['respondent_id'])
            : '';
        $participantId = isset($identity['participant_id']) && $identity['participant_id']
            ? (int) $identity['participant_id']
            : null;

        if ($email === '' && $respondentId === '' && $participantId === null) {
            return false;
        }

        return SurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->where(function ($q) use ($email, $respondentId, $participantId) {
                if ($email !== '') {
                    $q->orWhereRaw('LOWER(respondent_email) = ?', [$email]);
                }
                if ($respondentId !== '') {
                    $q->orWhere('respondent_id', $respondentId);
                }
                if ($participantId !== null) {
                    $q->orWhere('participant_id', $participantId);
                }
            })
            ->exists();
    }

    /**
     * Czy zablokować pokazanie formularza (przed submitem).
     *
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identityHint
     */
    public function shouldBlockForm(Request $request, Survey $survey, bool $isAnonymous, array $identityHint = []): bool
    {
        if ($this->allowsMultiple()) {
            return false;
        }

        if ($this->hasSoftCookie($request, (int) $survey->id)) {
            return true;
        }

        if (! $isAnonymous && $this->hasHardDuplicate($survey, $identityHint)) {
            return true;
        }

        return false;
    }

    /**
     * Czy zablokować zapis odpowiedzi.
     *
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identity
     */
    public function shouldBlockSubmit(Request $request, Survey $survey, bool $isAnonymous, array $identity): bool
    {
        if ($this->allowsMultiple()) {
            return false;
        }

        if ($this->hasSoftCookie($request, (int) $survey->id)) {
            return true;
        }

        if (! $isAnonymous && $this->hasHardDuplicate($survey, $identity)) {
            return true;
        }

        // Anonimowa bez cookie: nie blokujemy twarde (brak tożsamości).
        return false;
    }
}
