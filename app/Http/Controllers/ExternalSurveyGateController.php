<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\PneadmCourseSurveyLink;
use App\Models\Participant;
use App\Models\SurveyResponse;
use App\Services\NativeSurveySubmissionService;
use App\Support\SurveyAvatarPresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExternalSurveyGateController extends Controller
{
    private const REC_SESSION_PREFIX = 'native_survey_recommendation.';

    /**
     * Bramka ankiet — external redirect albo formularz natywny.
     */
    public function visit(string $token): RedirectResponse|View
    {
        $link = $this->findLink($token);

        if (! $link->isAvailableNow()) {
            return view('survey-gate-unavailable', [
                'surveyTitle' => $link->title,
                'opensAt' => $link->opens_at,
                'closesAt' => $link->closes_at,
                'active' => $link->is_active,
            ]);
        }

        if ($link->isNative()) {
            return $this->showNativeForm($link);
        }

        $destination = trim((string) ($link->url ?? ''));
        if ($destination === '' || ! filter_var($destination, FILTER_VALIDATE_URL)) {
            abort(503, 'Konfiguracja ankiety jest niekompletna.');
        }

        return redirect()->away($destination);
    }

    public function submit(Request $request, string $token, NativeSurveySubmissionService $submissionService): RedirectResponse
    {
        $link = $this->findLink($token);

        if (! $link->isAvailableNow() || ! $link->isNative()) {
            return redirect()->route('survey.gate.visit', ['token' => $token]);
        }

        $survey = $link->survey;
        if (! $survey) {
            abort(503, 'Ankieta natywna nie jest jeszcze przygotowana.');
        }

        if (filled($request->input('website'))) {
            return redirect()->route('survey.gate.thanks', ['token' => $token]);
        }

        $identity = $this->resolveIdentity($request, $link);

        if (! $link->is_anonymous && blank($identity['respondent_email'] ?? null) && blank($identity['respondent_id'] ?? null)) {
            throw ValidationException::withMessages([
                'respondent_email' => 'Podaj adres e-mail, abyśmy mogli powiązać odpowiedź z uczestnictwem.',
            ]);
        }

        $response = $submissionService->submit(
            $survey,
            (array) $request->input('answers', []),
            $identity,
        );

        $this->putRecommendationSession($token, $response);

        return redirect()->route('survey.gate.recommend', ['token' => $token]);
    }

    public function recommend(string $token): RedirectResponse|View
    {
        $link = $this->findLink($token);
        $ctx = $this->getRecommendationSession($token);

        if (! $ctx) {
            return redirect()->route('survey.gate.thanks', ['token' => $token]);
        }

        $user = Auth::user();
        $prefillName = $ctx['prefill_name']
            ?: trim(collect([$user?->first_name ?? null, $user?->last_name ?? null])->filter()->implode(' '));

        $course = Course::query()->find($link->course_id);

        return view('survey-recommendation', [
            'link' => $link,
            'token' => $link->public_token,
            'surveyTitle' => $link->title ?: 'Ankieta',
            'courseTitle' => $course?->title
                ? strip_tags(html_entity_decode($course->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                : null,
            'prefillName' => $prefillName,
            'avatarPresetsByGroup' => SurveyAvatarPresets::optionsByGroup(),
            'defaultAvatarPreset' => SurveyAvatarPresets::defaultKey(),
        ]);
    }

    public function submitRecommendation(
        Request $request,
        string $token,
        NativeSurveySubmissionService $submissionService,
    ): RedirectResponse {
        $link = $this->findLink($token);
        $ctx = $this->getRecommendationSession($token);

        if (! $ctx) {
            return redirect()->route('survey.gate.thanks', ['token' => $token]);
        }

        $survey = $link->survey;
        $response = SurveyResponse::query()->find($ctx['response_id'] ?? 0);

        if (! $survey || ! $response) {
            $this->forgetRecommendationSession($token);

            return redirect()->route('survey.gate.thanks', ['token' => $token]);
        }

        if (filled($request->input('website'))) {
            $this->forgetRecommendationSession($token);

            return redirect()->route('survey.gate.thanks', ['token' => $token, 'rec' => 1]);
        }

        $request->validate([
            'quote' => ['required', 'string', 'max:1000'],
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'author_city' => ['nullable', 'string', 'max:80'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'publish_consent' => ['nullable', 'boolean'],
            'avatar_mode' => ['nullable', Rule::in(['preset', 'upload'])],
            'avatar_preset' => ['nullable', 'string', Rule::in(array_merge(SurveyAvatarPresets::keys(), [SurveyAvatarPresets::NONE]))],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'quote.required' => 'Napisz krótką rekomendację — to najważniejsze pole.',
            'author_name.required' => 'Podaj imię i nazwisko (lub imię), które możemy pokazać przy opinii.',
            'avatar.image' => 'Awatar musi być obrazem (JPG, PNG lub WebP).',
            'avatar.max' => 'Awatar może mieć maksymalnie 2 MB.',
        ]);

        if ($request->input('avatar_mode') === 'upload' && ! $request->hasFile('avatar')) {
            throw ValidationException::withMessages([
                'avatar' => 'Wybierz zdjęcie albo wróć do przykładowych awatarów (w tym BRAK).',
            ]);
        }

        $submissionService->storeTestimonial(
            $survey,
            $response,
            [
                'quote' => $request->input('quote'),
                'author_name' => $request->input('author_name'),
                'author_role' => $request->input('author_role'),
                'author_city' => $request->input('author_city'),
                'rating' => $request->input('rating'),
                'publish_consent' => $request->boolean('publish_consent'),
                'avatar_mode' => $request->input('avatar_mode', 'preset'),
                'avatar_preset' => $request->input('avatar_preset', SurveyAvatarPresets::NONE),
            ],
            $request->file('avatar'),
        );

        $this->forgetRecommendationSession($token);

        return redirect()->route('survey.gate.thanks', ['token' => $token, 'rec' => 1]);
    }

    public function skipRecommendation(string $token): RedirectResponse
    {
        $this->forgetRecommendationSession($token);

        return redirect()->route('survey.gate.thanks', ['token' => $token]);
    }

    public function thanks(Request $request, string $token): View
    {
        $link = $this->findLink($token);

        return view('survey-native-thanks', [
            'surveyTitle' => $link->title ?: 'Ankieta',
            'sharedRecommendation' => $request->boolean('rec'),
        ]);
    }

    private function findLink(string $token): PneadmCourseSurveyLink
    {
        $normalized = strtolower(trim($token));
        $link = PneadmCourseSurveyLink::query()
            ->where('public_token', $normalized)
            ->first();

        if (! $link) {
            abort(404);
        }

        return $link;
    }

    private function showNativeForm(PneadmCourseSurveyLink $link): View
    {
        $survey = $link->survey()->with('questions')->first();
        if (! $survey || $survey->questions->isEmpty()) {
            abort(503, 'Ankieta natywna nie jest jeszcze przygotowana. Spróbuj później lub skontaktuj się z organizatorem.');
        }

        $user = Auth::user();
        $prefillEmail = $user?->email ? strtolower(trim((string) $user->email)) : null;
        $prefillName = trim(collect([$user?->first_name ?? null, $user?->last_name ?? null])->filter()->implode(' '));
        $course = Course::query()->find($link->course_id);
        $questions = $survey->questions->where('question_type', '!=', 'testimonial')->values();
        $usesAccountEmail = ! (bool) $link->is_anonymous && filled($prefillEmail);

        return view('survey-native-form', [
            'link' => $link,
            'survey' => $survey,
            'course' => $course,
            'questions' => $questions,
            'isAnonymous' => (bool) $link->is_anonymous,
            'usesAccountEmail' => $usesAccountEmail,
            'prefillEmail' => $prefillEmail,
            'prefillName' => $prefillName,
            'token' => $link->public_token,
        ]);
    }

    private function putRecommendationSession(string $token, SurveyResponse $response): void
    {
        $user = Auth::user();
        $prefillName = trim(collect([$user?->first_name ?? null, $user?->last_name ?? null])->filter()->implode(' '));

        session([
            self::REC_SESSION_PREFIX.strtolower(trim($token)) => [
                'response_id' => $response->id,
                'survey_id' => $response->survey_id,
                'prefill_name' => $prefillName,
                'created_at' => now()->timestamp,
            ],
        ]);
    }

    /**
     * @return array{response_id: int, survey_id: int, prefill_name?: string, created_at?: int}|null
     */
    private function getRecommendationSession(string $token): ?array
    {
        $key = self::REC_SESSION_PREFIX.strtolower(trim($token));
        $ctx = session($key);

        if (! is_array($ctx) || empty($ctx['response_id'])) {
            return null;
        }

        // Sesja ważna 2 godziny
        if (! empty($ctx['created_at']) && (now()->timestamp - (int) $ctx['created_at']) > 7200) {
            session()->forget($key);

            return null;
        }

        return $ctx;
    }

    private function forgetRecommendationSession(string $token): void
    {
        session()->forget(self::REC_SESSION_PREFIX.strtolower(trim($token)));
    }

    /**
     * @return array{respondent_id: ?string, participant_id: ?int, respondent_email: ?string}
     */
    private function resolveIdentity(Request $request, PneadmCourseSurveyLink $link): array
    {
        if ($link->is_anonymous) {
            return [
                'respondent_id' => null,
                'participant_id' => null,
                'respondent_email' => null,
            ];
        }

        $fromRequest = strtolower(trim((string) $request->input('respondent_email', '')));
        $fromAccount = Auth::user()?->email
            ? strtolower(trim((string) Auth::user()->email))
            : '';
        // Zalogowany: zawsze e-mail z konta (ignoruj ewentualne podmienie w POST).
        $email = $fromAccount !== '' ? $fromAccount : $fromRequest;
        $participantId = null;

        if ($email !== '') {
            $participantId = Participant::query()
                ->where('course_id', $link->course_id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->value('id');
        }

        return [
            'respondent_id' => $email !== '' ? $email : (Auth::id() ? 'user:'.Auth::id() : null),
            'participant_id' => $participantId ? (int) $participantId : null,
            'respondent_email' => $email !== '' ? $email : null,
        ];
    }
}
