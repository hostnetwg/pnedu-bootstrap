<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    const CACHE_KEY = 'homepage_statistics';

    const LAST_GOOD_KEY = 'homepage_statistics_last_good';

    const CACHE_TTL = 86400; // 24 godziny (w sekundach)

    const LAST_GOOD_TTL = 7776000; // 90 dni — ostatni wiarygodny odczyt po błędzie źródła

    const EMPTY_CACHE_TTL = 300; // krótki retry, gdy nie ma żadnej wartości do pokazania

    /** @var list<string> */
    public const METRIC_KEYS = [
        'trained_teachers',
        'courses_this_year',
        'average_rating',
        'nps',
    ];

    /**
     * Pobiera statystyki z cache. Liczenie z bazy tylko przy braku cache (raz na dobę).
     *
     * @return array{
     *     trained_teachers: int|null,
     *     trained_teachers_display: string|null,
     *     courses_this_year: int|null,
     *     courses_this_year_display: string|null,
     *     average_rating: float|null,
     *     average_rating_display: string|null,
     *     nps: float|null,
     *     nps_display: string|null,
     *     last_updated: \Carbon\Carbon|null
     * }
     */
    public function getStatistics(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (! is_array($cached)) {
            return $this->refreshStatistics();
        }

        $this->rememberLastGoodFromCache($cached);

        return $this->hydrate($cached);
    }

    /**
     * Oblicza wszystkie statystyki
     */
    public function calculateStatistics(): array
    {
        return [
            'trained_teachers' => $this->getTrainedTeachersCount(),
            'courses_this_year' => $this->getCoursesThisYearCount(),
            'average_rating' => $this->getAverageRating(),
            'nps' => $this->getNPS(),
        ];
    }

    /**
     * Oblicza ilość przeszkolonych nauczycieli (unikalni uczestnicy)
     */
    public function getTrainedTeachersCount(): ?int
    {
        try {
            // Unikalni uczestnicy po emailu
            $uniqueByEmail = DB::connection('pneadm')
                ->table('participants')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->distinct('email')
                ->count('email');

            // Uczestnicy bez emaila - liczymy po unikalnych kombinacjach imię+nazwisko
            $uniqueByName = DB::connection('pneadm')
                ->table('participants')
                ->where(function ($query) {
                    $query->whereNull('email')
                        ->orWhere('email', '=', '');
                })
                ->select(DB::raw('CONCAT(first_name, " ", last_name) as full_name'))
                ->distinct()
                ->count();

            return $uniqueByEmail + $uniqueByName;
        } catch (\Exception $e) {
            \Log::error('Błąd obliczania przeszkolonych nauczycieli: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Oblicza średnią roczną ilość szkoleń na podstawie ostatnich 12 miesięcy
     * Liczy szkolenia z ostatnich 12 miesięcy od daty obliczenia
     */
    public function getCoursesThisYearCount(): ?int
    {
        try {
            // Data 12 miesięcy wstecz od teraz
            $twelveMonthsAgo = now()->subMonths(12);

            // Pobierz szkolenia z ostatnich 12 miesięcy
            $coursesCount = DB::connection('pneadm')
                ->table('courses')
                ->where('start_date', '>=', $twelveMonthsAgo)
                ->whereNotNull('start_date')
                ->count();

            return $coursesCount;
        } catch (\Exception $e) {
            \Log::error('Błąd obliczania średniej rocznej szkoleń: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Oblicza średnią ocenę ze wszystkich ankiet
     * (podobnie jak w DashboardController w pneadm-bootstrap)
     */
    public function getAverageRating(): ?float
    {
        try {
            // Pobierz wszystkie ankiety z pytaniami i odpowiedziami
            $surveys = DB::connection('pneadm')
                ->table('surveys')
                ->join('courses', 'surveys.course_id', '=', 'courses.id')
                ->select('surveys.id')
                ->get();

            if ($surveys->isEmpty()) {
                return null;
            }

            $totalRating = 0;
            $surveysWithRatings = 0;

            foreach ($surveys as $survey) {
                // Pobierz pytania ratingowe dla tej ankiety
                $ratingQuestions = DB::connection('pneadm')
                    ->table('survey_questions')
                    ->where('survey_id', $survey->id)
                    ->where('question_type', 'rating')
                    ->get();

                if ($ratingQuestions->isEmpty()) {
                    continue;
                }

                // Pobierz wszystkie odpowiedzi dla tej ankiety
                $responses = DB::connection('pneadm')
                    ->table('survey_responses')
                    ->where('survey_id', $survey->id)
                    ->get();

                if ($responses->isEmpty()) {
                    continue;
                }

                // Oblicz średnią ocenę dla tej ankiety
                $surveyTotalRating = 0;
                $surveyTotalResponses = 0;

                foreach ($ratingQuestions as $question) {
                    foreach ($responses as $response) {
                        // response_data może być JSON stringiem lub już tablicą
                        $responseData = is_string($response->response_data)
                            ? json_decode($response->response_data, true)
                            : $response->response_data;

                        if (! is_array($responseData)) {
                            continue;
                        }

                        $answer = $responseData[$question->question_text] ?? null;

                        if (is_numeric($answer)) {
                            $surveyTotalRating += (float) $answer;
                            $surveyTotalResponses++;
                        }
                    }
                }

                if ($surveyTotalResponses > 0) {
                    $surveyAverage = $surveyTotalRating / $surveyTotalResponses;
                    $totalRating += $surveyAverage;
                    $surveysWithRatings++;
                }
            }

            return $surveysWithRatings > 0 ? round($totalRating / $surveysWithRatings, 2) : null;
        } catch (\Exception $e) {
            \Log::error('Błąd obliczania średniej oceny: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Oblicza wskaźnik poleceń (NPS - Net Promoter Score)
     * Na podstawie pytań o polecanie szkoleń innym (skala 1-5)
     * Używa DOKŁADNIE tej samej logiki co SurveyController::calculateNPS w pneadm-bootstrap
     */
    public function getNPS(): ?float
    {
        try {
            // Wzorce pytania NPS - IDENTYCZNE jak w SurveyController
            $npsQuestionPatterns = [
                '/czy.*poleci.*szkolenie.*innym/i',
                '/poleci.*szkolenie.*innym/i',
                '/poleci.*innym.*osobom/i',
                '/czy.*poleci.*innym/i',
                '/poleci.*innym/i',
            ];

            // Pobierz wszystkie ankiety z odpowiedziami - IDENTYCZNIE jak w SurveyController
            // Używamy with(['questions', 'responses']) tak jak w SurveyController
            $surveys = Survey::with(['questions', 'responses'])
                ->whereHas('responses')
                ->get();

            if ($surveys->isEmpty()) {
                \Log::info('Brak ankiet z odpowiedziami dla obliczenia NPS');

                return null;
            }

            $npsResponses = [];

            // IDENTYCZNA logika jak w SurveyController::calculateNPS
            foreach ($surveys as $survey) {
                foreach ($survey->responses as $response) {
                    // response_data jest automatycznie dekodowane jako tablica przez cast w modelu
                    $responseData = $response->response_data;

                    if (! is_array($responseData)) {
                        continue;
                    }

                    // Sprawdź każdą odpowiedź czy to pytanie NPS - IDENTYCZNA logika
                    foreach ($responseData as $questionText => $answer) {
                        // Sprawdź czy to pytanie NPS
                        $isNpsQuestion = false;
                        foreach ($npsQuestionPatterns as $pattern) {
                            if (preg_match($pattern, $questionText)) {
                                $isNpsQuestion = true;
                                break;
                            }
                        }

                        if ($isNpsQuestion && is_numeric($answer) && $answer >= 1 && $answer <= 5) {
                            $npsResponses[] = (int) $answer;
                        }
                    }
                }
            }

            if (empty($npsResponses)) {
                // Debugowanie - sprawdź przykładowe pytania z ankiet
                $sampleQuestions = [];
                foreach ($surveys->take(3) as $survey) {
                    foreach ($survey->responses->take(1) as $response) {
                        if (is_array($response->response_data)) {
                            $sampleQuestions = array_merge($sampleQuestions, array_keys($response->response_data));
                            break;
                        }
                    }
                }
                \Log::info('Brak odpowiedzi NPS w ankietach. Przykładowe pytania: '.json_encode(array_slice($sampleQuestions, 0, 10)));

                return null;
            }

            $totalResponses = count($npsResponses);
            $promoters = 0; // 4-5
            $detractors = 0; // 1-2
            $passives = 0; // 3

            foreach ($npsResponses as $rating) {
                if ($rating >= 4) {
                    $promoters++;
                } elseif ($rating <= 2) {
                    $detractors++;
                } else {
                    $passives++;
                }
            }

            $promotersPercent = ($promoters / $totalResponses) * 100;
            $detractorsPercent = ($detractors / $totalResponses) * 100;
            $nps = round($promotersPercent - $detractorsPercent, 1);

            \Log::info("NPS obliczony: {$nps} (promoters: {$promoters}, detractors: {$detractors}, passives: {$passives}, total: {$totalResponses})");

            return $nps;
        } catch (\Exception $e) {
            \Log::error('Błąd obliczania wskaźnika poleceń (NPS): '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return null;
        }
    }

    /**
     * Odświeża statystyki (czyści cache bieżącego odczytu i generuje nowe).
     * Ostatni wiarygodny odczyt zostaje, dopóki nowe wartości nie są poprawne.
     */
    public function refreshStatistics(): array
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'_timestamp');

        $resolved = $this->resolveWithFallback($this->calculateStatistics());
        Cache::put(self::CACHE_KEY, $this->forCache($resolved), $this->cacheTtl($resolved));

        return $this->hydrate($this->forCache($resolved));
    }

    /**
     * Pobiera czas ostatniej aktualizacji statystyk
     */
    public function getLastUpdated(): ?\Carbon\Carbon
    {
        $statistics = Cache::get(self::CACHE_KEY);

        if (! is_array($statistics) || empty($statistics['last_updated'])) {
            $statistics = Cache::get(self::LAST_GOOD_KEY);
        }

        if (! is_array($statistics) || empty($statistics['last_updated'])) {
            $legacy = Cache::get(self::CACHE_KEY.'_timestamp');

            return $legacy instanceof \Carbon\Carbon ? $legacy : null;
        }

        $lastUpdated = $statistics['last_updated'];

        return is_string($lastUpdated) ? \Carbon\Carbon::parse($lastUpdated) : $lastUpdated;
    }

    /**
     * Tekst liczby taki, jaki ma trafić do HTML (bez odliczania od zera).
     */
    public function formatMetric(string $key, int|float|null $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        if (in_array($key, ['average_rating', 'nps'], true)) {
            $rounded = round((float) $value, 1);

            if (abs($rounded - round($rounded)) < 0.00001) {
                return number_format($rounded, 0, '.', '');
            }

            return number_format($rounded, 1, '.', '');
        }

        return number_format((int) $value, 0, ',', ' ');
    }

    /**
     * @param  array<string, mixed>  $fresh
     * @return array<string, mixed>
     */
    private function resolveWithFallback(array $fresh): array
    {
        $lastGood = $this->lastGood();
        $resolved = [];
        $updatedGood = $lastGood;
        $usedFresh = false;

        foreach (self::METRIC_KEYS as $key) {
            $value = $fresh[$key] ?? null;

            if ($this->isPublishable($key, $value)) {
                $resolved[$key] = $this->normalizeNumber($key, $value);
                $updatedGood[$key] = $resolved[$key];
                $usedFresh = true;

                continue;
            }

            $fallback = $lastGood[$key] ?? null;
            $resolved[$key] = $this->isPublishable($key, $fallback)
                ? $this->normalizeNumber($key, $fallback)
                : null;
        }

        if ($usedFresh) {
            $updatedGood['last_updated'] = now()->toIso8601String();
            Cache::put(self::LAST_GOOD_KEY, $updatedGood, self::LAST_GOOD_TTL);
            $resolved['last_updated'] = $updatedGood['last_updated'];
        } else {
            $resolved['last_updated'] = $lastGood['last_updated'] ?? null;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $statistics
     * @return array<string, mixed>
     */
    private function hydrate(array $statistics): array
    {
        $lastGood = null;

        foreach (self::METRIC_KEYS as $key) {
            $value = $statistics[$key] ?? null;

            if (! $this->isPublishable($key, $value)) {
                $lastGood ??= $this->lastGood();
                $fallback = $lastGood[$key] ?? null;
                $value = $this->isPublishable($key, $fallback) ? $fallback : null;
            }

            $statistics[$key] = is_numeric($value) ? $this->normalizeNumber($key, $value) : null;
            $statistics[$key.'_display'] = $this->formatMetric($key, $statistics[$key]);
        }

        $lastUpdated = $statistics['last_updated'] ?? null;
        if ($lastUpdated instanceof \DateTimeInterface) {
            $statistics['last_updated'] = \Carbon\Carbon::parse($lastUpdated->format('c'));
        } elseif (is_string($lastUpdated) && $lastUpdated !== '') {
            $statistics['last_updated'] = \Carbon\Carbon::parse($lastUpdated);
        } else {
            $statistics['last_updated'] = null;
        }

        return $statistics;
    }

    /**
     * Zapisuje bieżący poprawny cache jako ostatni wiarygodny odczyt,
     * jeśli ten zapas jeszcze nie istnieje. Bez ponownego liczenia z bazy.
     *
     * @param  array<string, mixed>  $cached
     */
    private function rememberLastGoodFromCache(array $cached): void
    {
        $existing = $this->lastGood();
        $updated = $existing;
        $changed = false;

        foreach (self::METRIC_KEYS as $key) {
            if ($this->isPublishable($key, $cached[$key] ?? null) && ! $this->isPublishable($key, $existing[$key] ?? null)) {
                $updated[$key] = $this->normalizeNumber($key, $cached[$key]);
                $changed = true;
            }
        }

        if (! $changed) {
            return;
        }

        if (empty($updated['last_updated']) && ! empty($cached['last_updated'])) {
            $lastUpdated = $cached['last_updated'];
            $updated['last_updated'] = $lastUpdated instanceof \DateTimeInterface
                ? \Carbon\Carbon::parse($lastUpdated->format('c'))->toIso8601String()
                : $lastUpdated;
        }

        Cache::put(self::LAST_GOOD_KEY, $updated, self::LAST_GOOD_TTL);
    }

    /**
     * @return array<string, mixed>
     */
    private function lastGood(): array
    {
        $lastGood = Cache::get(self::LAST_GOOD_KEY);

        return is_array($lastGood) ? $lastGood : [];
    }

    private function isPublishable(string $key, mixed $value): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $number = (float) $value;

        return match ($key) {
            'trained_teachers', 'courses_this_year' => $number > 0,
            'average_rating' => $number > 0 && $number <= 5,
            'nps' => $number >= -100 && $number <= 100,
            default => false,
        };
    }

    private function normalizeNumber(string $key, mixed $value): int|float
    {
        if (in_array($key, ['trained_teachers', 'courses_this_year'], true)) {
            return (int) $value;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $statistics
     * @return array<string, mixed>
     */
    private function forCache(array $statistics): array
    {
        $payload = [];

        foreach (self::METRIC_KEYS as $key) {
            $payload[$key] = $statistics[$key] ?? null;
        }

        $lastUpdated = $statistics['last_updated'] ?? null;
        if ($lastUpdated instanceof \DateTimeInterface) {
            $lastUpdated = \Carbon\Carbon::parse($lastUpdated->format('c'))->toIso8601String();
        }

        $payload['last_updated'] = is_string($lastUpdated) ? $lastUpdated : null;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $statistics
     */
    private function cacheTtl(array $statistics): int
    {
        foreach (self::METRIC_KEYS as $key) {
            if ($this->isPublishable($key, $statistics[$key] ?? null)) {
                return self::CACHE_TTL;
            }
        }

        return self::EMPTY_CACHE_TTL;
    }
}
