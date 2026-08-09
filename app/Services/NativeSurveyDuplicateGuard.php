<?php

namespace App\Services;

use App\Models\PneadmCourseSurveyLink;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Ochrona przed wielokrotnym wypełnieniem ankiety natywnej.
 *
 * Limit jest per ankieta (course_survey_links.allow_multiple_responses / surveys).
 * Ustawienie globalne w adm służy tylko jako domyślna wartość przy tworzeniu.
 *
 * - Nieanonimowa: twardy limit po e-mail / respondent_id / participant_id.
 * - Anonimowa (+ dodatkowa warstwa): cookie w przeglądarce (miękkie).
 */
class NativeSurveyDuplicateGuard
{
    private const COOKIE_PREFIX = 'pne_sv_done_';

    private const COOKIE_MINUTES = 60 * 24 * 400; // ~400 dni

    public function allowsMultipleForLink(PneadmCourseSurveyLink $link): bool
    {
        return (bool) ($link->allow_multiple_responses ?? false);
    }

    public function allowsMultipleForSurvey(Survey $survey): bool
    {
        return (bool) ($survey->allow_multiple_responses ?? false);
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
     * Czy ukryć link ankiety na dashboardzie (np. strona wideo) po wypełnieniu.
     *
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identity
     */
    public function shouldHideFromDashboard(Request $request, PneadmCourseSurveyLink $link, array $identity = []): bool
    {
        if ($this->allowsMultipleForLink($link)) {
            return false;
        }

        if (! $link->isNative() || ! $link->survey_id) {
            // Zewnętrzna (Google) — nie wiemy, czy wypełniono.
            return false;
        }

        $surveyId = (int) $link->survey_id;

        if ($this->hasSoftCookie($request, $surveyId)) {
            return true;
        }

        if ($link->is_anonymous) {
            return false;
        }

        $survey = Survey::query()->find($surveyId);
        if (! $survey) {
            return false;
        }

        return $this->hasHardDuplicate($survey, $identity);
    }

    /**
     * Czy zablokować pokazanie formularza (przed submitem).
     *
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identityHint
     */
    public function shouldBlockForm(Request $request, PneadmCourseSurveyLink $link, Survey $survey, array $identityHint = []): bool
    {
        if ($this->allowsMultipleForLink($link)) {
            return false;
        }

        if ($this->hasSoftCookie($request, (int) $survey->id)) {
            return true;
        }

        if (! $link->is_anonymous && $this->hasHardDuplicate($survey, $identityHint)) {
            return true;
        }

        return false;
    }

    /**
     * Czy zablokować zapis odpowiedzi.
     *
     * @param  array{respondent_id?: ?string, participant_id?: ?int, respondent_email?: ?string}  $identity
     */
    public function shouldBlockSubmit(Request $request, PneadmCourseSurveyLink $link, Survey $survey, array $identity): bool
    {
        if ($this->allowsMultipleForLink($link)) {
            return false;
        }

        if ($this->hasSoftCookie($request, (int) $survey->id)) {
            return true;
        }

        if (! $link->is_anonymous && $this->hasHardDuplicate($survey, $identity)) {
            return true;
        }

        // Anonimowa bez cookie: nie blokujemy twarde (brak tożsamości).
        return false;
    }
}
