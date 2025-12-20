<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Survey;

class StatisticsService
{
    const CACHE_KEY = 'homepage_statistics';
    const CACHE_TTL = 86400; // 24 godziny (w sekundach)

    /**
     * Pobiera wszystkie statystyki z cache lub generuje nowe
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $statistics = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $stats = $this->calculateStatistics();
            // Zapisz timestamp aktualizacji
            Cache::put(self::CACHE_KEY . '_timestamp', now(), self::CACHE_TTL);
            return $stats;
        });
        
        // Dodaj timestamp do zwracanych statystyk
        $statistics['last_updated'] = Cache::get(self::CACHE_KEY . '_timestamp', now());
        
        return $statistics;
    }

    /**
     * Oblicza wszystkie statystyki
     *
     * @return array
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
     *
     * @return int
     */
    public function getTrainedTeachersCount(): int
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
                ->where(function($query) {
                    $query->whereNull('email')
                          ->orWhere('email', '=', '');
                })
                ->select(DB::raw('CONCAT(first_name, " ", last_name) as full_name'))
                ->distinct()
                ->count();

            return $uniqueByEmail + $uniqueByName;
        } catch (\Exception $e) {
            \Log::error('Błąd obliczania przeszkolonych nauczycieli: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Oblicza średnią roczną ilość szkoleń na podstawie ostatnich 12 miesięcy
     * Liczy szkolenia z ostatnich 12 miesięcy od daty obliczenia
     *
     * @return int
     */
    public function getCoursesThisYearCount(): int
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
            \Log::error('Błąd obliczania średniej rocznej szkoleń: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Oblicza średnią ocenę ze wszystkich ankiet
     * (podobnie jak w DashboardController w pneadm-bootstrap)
     *
     * @return float
     */
    public function getAverageRating(): float
    {
        try {
            // Pobierz wszystkie ankiety z pytaniami i odpowiedziami
            $surveys = DB::connection('pneadm')
                ->table('surveys')
                ->join('courses', 'surveys.course_id', '=', 'courses.id')
                ->select('surveys.id')
                ->get();

            if ($surveys->isEmpty()) {
                return 0;
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
                        
                        if (!is_array($responseData)) {
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

            return $surveysWithRatings > 0 ? round($totalRating / $surveysWithRatings, 2) : 0;
        } catch (\Exception $e) {
            \Log::error('Błąd obliczania średniej oceny: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Oblicza wskaźnik poleceń (NPS - Net Promoter Score)
     * Na podstawie pytań o polecanie szkoleń innym (skala 1-5)
     * Używa DOKŁADNIE tej samej logiki co SurveyController::calculateNPS w pneadm-bootstrap
     *
     * @return float
     */
    public function getNPS(): float
    {
        try {
            // Wzorce pytania NPS - IDENTYCZNE jak w SurveyController
            $npsQuestionPatterns = [
                '/czy.*poleci.*szkolenie.*innym/i',
                '/poleci.*szkolenie.*innym/i',
                '/poleci.*innym.*osobom/i',
                '/czy.*poleci.*innym/i',
                '/poleci.*innym/i'
            ];

            // Pobierz wszystkie ankiety z odpowiedziami - IDENTYCZNIE jak w SurveyController
            // Używamy with(['questions', 'responses']) tak jak w SurveyController
            $surveys = Survey::with(['questions', 'responses'])
                ->whereHas('responses')
                ->get();

            if ($surveys->isEmpty()) {
                \Log::info('Brak ankiet z odpowiedziami dla obliczenia NPS');
                return 0;
            }

            $npsResponses = [];

            // IDENTYCZNA logika jak w SurveyController::calculateNPS
            foreach ($surveys as $survey) {
                foreach ($survey->responses as $response) {
                    // response_data jest automatycznie dekodowane jako tablica przez cast w modelu
                    $responseData = $response->response_data;
                    
                    if (!is_array($responseData)) {
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
                \Log::info('Brak odpowiedzi NPS w ankietach. Przykładowe pytania: ' . json_encode(array_slice($sampleQuestions, 0, 10)));
                return 0;
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
            \Log::error('Błąd obliczania wskaźnika poleceń (NPS): ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return 0;
        }
    }

    /**
     * Odświeża statystyki (czyści cache i generuje nowe)
     *
     * @return array
     */
    public function refreshStatistics(): array
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_timestamp');
        
        $statistics = $this->calculateStatistics();
        
        // Zapisz w cache z timestamp
        Cache::put(self::CACHE_KEY, $statistics, self::CACHE_TTL);
        Cache::put(self::CACHE_KEY . '_timestamp', now(), self::CACHE_TTL);
        
        return $statistics;
    }

    /**
     * Pobiera czas ostatniej aktualizacji statystyk
     *
     * @return \Carbon\Carbon|null
     */
    public function getLastUpdated(): ?\Carbon\Carbon
    {
        return Cache::get(self::CACHE_KEY . '_timestamp');
    }
}

