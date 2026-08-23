<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Participant;
use App\Services\DashboardCourseLiveAccessService;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Faza strony /po-szkoleniu?course=… — wczesne wyjście vs komunikat końcowy.
 */
class PostTrainingThankYouPhase
{
    public const PHASE_GENERIC = 'generic';

    public const PHASE_CLOSING = 'closing';

    public const PHASE_EARLY_BEFORE_START = 'early_before_start';

    public const PHASE_EARLY_DURING = 'early_during';

    public const MAX_CLOSING_MINUTES_BEFORE_END = 45;

    public const CLOSING_PERCENT_OF_DURATION = 0.2;

    public const DEFAULT_DURATION_WHEN_NO_END_HOURS = 1;

    public function __construct(
        public string $phase,
        public bool $showFullResources,
        public bool $canReturnToLive = false,
        public ?string $returnToLiveUrl = null,
        public ?string $joinUnlockHint = null,
    ) {}

    public static function generic(): self
    {
        return new self(
            phase: self::PHASE_GENERIC,
            showFullResources: true,
        );
    }

    public static function resolve(
        ?Course $course,
        bool $cmEndedByHost,
        ?Participant $participant,
        DashboardCourseLiveAccessService $liveAccessService,
    ): self {
        if ($course === null || ! $course->start_date instanceof CarbonInterface) {
            return self::generic();
        }

        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $now = Carbon::now($tz);
        $start = Carbon::parse($course->start_date)->timezone($tz);
        $end = self::effectiveEnd($course, $start);

        if (self::isClosingPhase($now, $start, $end, $cmEndedByHost)) {
            return new self(
                phase: self::PHASE_CLOSING,
                showFullResources: true,
            );
        }

        $liveReturn = self::resolveLiveReturn($participant, $liveAccessService);

        if ($now->lessThan($start)) {
            return new self(
                phase: self::PHASE_EARLY_BEFORE_START,
                showFullResources: false,
                canReturnToLive: $liveReturn['can_return'],
                returnToLiveUrl: $liveReturn['url'],
                joinUnlockHint: $liveReturn['hint'],
            );
        }

        return new self(
            phase: self::PHASE_EARLY_DURING,
            showFullResources: false,
            canReturnToLive: $liveReturn['can_return'],
            returnToLiveUrl: $liveReturn['url'],
            joinUnlockHint: $liveReturn['hint'],
        );
    }

    public function isEarlyExit(): bool
    {
        return in_array($this->phase, [self::PHASE_EARLY_BEFORE_START, self::PHASE_EARLY_DURING], true);
    }

    /**
     * @return list<array{strong: bool, text: string}>
     */
    public function earlySummaryLines(): array
    {
        if ($this->phase === self::PHASE_EARLY_BEFORE_START) {
            return [
                [
                    'strong' => true,
                    'text' => 'Szkolenie jeszcze się nie rozpoczęło.',
                ],
                [
                    'strong' => false,
                    'text' => 'Jeśli tylko sprawdziłeś/aś dostęp do pokoju — wróć na spotkanie o zaplanowanej godzinie.',
                ],
            ];
        }

        if ($this->phase === self::PHASE_EARLY_DURING) {
            return [
                [
                    'strong' => true,
                    'text' => 'Wyszedłeś/aś ze spotkania.',
                ],
                [
                    'strong' => false,
                    'text' => 'Szkolenie nadal trwa. Możesz wrócić do transmisji z panelu. Materiały, nagranie i zaświadczenie udostępniamy po zakończeniu szkolenia.',
                ],
            ];
        }

        return [];
    }

    public static function effectiveEnd(Course $course, CarbonInterface $start): CarbonInterface
    {
        if ($course->end_date instanceof CarbonInterface) {
            return Carbon::parse($course->end_date)->timezone($start->timezone);
        }

        return $start->copy()->addHours(self::DEFAULT_DURATION_WHEN_NO_END_HOURS);
    }

    public static function closingThresholdMinutes(CarbonInterface $start, CarbonInterface $end): int
    {
        $durationMinutes = max(1, (int) $start->diffInMinutes($end));

        return min(
            self::MAX_CLOSING_MINUTES_BEFORE_END,
            (int) floor($durationMinutes * self::CLOSING_PERCENT_OF_DURATION)
        );
    }

    public static function isClosingPhase(
        CarbonInterface $now,
        CarbonInterface $start,
        CarbonInterface $end,
        bool $cmEndedByHost,
    ): bool {
        if ($now->greaterThanOrEqualTo($end)) {
            return true;
        }

        $thresholdMinutes = self::closingThresholdMinutes($start, $end);
        $closingStartsAt = $end->copy()->subMinutes($thresholdMinutes);

        if ($now->greaterThanOrEqualTo($closingStartsAt)) {
            return true;
        }

        return $cmEndedByHost && $now->greaterThanOrEqualTo($start);
    }

    /**
     * @return array{can_return: bool, url: ?string, hint: ?string}
     */
    private static function resolveLiveReturn(
        ?Participant $participant,
        DashboardCourseLiveAccessService $liveAccessService,
    ): array {
        if (! $participant instanceof Participant) {
            return [
                'can_return' => false,
                'url' => null,
                'hint' => null,
            ];
        }

        $live = $liveAccessService->forParticipant($participant);

        if (! $live->show) {
            return [
                'can_return' => false,
                'url' => null,
                'hint' => null,
            ];
        }

        if (! $live->joinUnlocked) {
            return [
                'can_return' => false,
                'url' => null,
                'hint' => $live->joinUnlockHint,
            ];
        }

        $url = null;
        if ($live->embedEnabled && is_string($live->embedUrl) && $live->embedUrl !== '') {
            $url = $live->embedUrl;
        } elseif ($live->clickmeetingJoinEnabled && is_string($live->joinUrl) && $live->joinUrl !== '') {
            $url = $live->joinUrl;
        }

        if ($url === null) {
            return [
                'can_return' => false,
                'url' => null,
                'hint' => null,
            ];
        }

        return [
            'can_return' => true,
            'url' => $url,
            'hint' => null,
        ];
    }
}
