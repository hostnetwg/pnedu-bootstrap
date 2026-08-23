<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseFileLink;
use App\Models\CourseVideo;
use App\Models\PneadmCourseSurveyLink;

/**
 * Zasoby szkolenia widoczne na stronie podziękowania po live (pnedu.pl/po-szkoleniu).
 */
class PostTrainingThankYouResources
{
    public const CERT_DOWNLOAD_ENABLED = 'download_enabled';

    public const CERT_IN_PREPARATION = 'in_preparation';

    public const CERT_NONE = 'no_certificate';

    public function __construct(
        public bool $hasMaterials,
        public bool $hasRecording,
        public string $certificateStatus,
        public ?string $surveyUrl = null,
    ) {}

    public static function forCourse(?Course $course): self
    {
        if ($course === null) {
            return new self(false, false, self::CERT_IN_PREPARATION);
        }

        return new self(
            hasMaterials: self::courseHasMaterials($course),
            hasRecording: self::courseHasRecording($course),
            certificateStatus: self::normalizeCertificateStatus($course->certificate_download_status ?? null),
            surveyUrl: self::resolveSurveyUrl($course),
        );
    }

    public function certificateAvailable(): bool
    {
        return $this->certificateStatus === self::CERT_DOWNLOAD_ENABLED;
    }

    public function certificateInPreparation(): bool
    {
        return $this->certificateStatus === self::CERT_IN_PREPARATION;
    }

    public function hasNoCertificate(): bool
    {
        return $this->certificateStatus === self::CERT_NONE;
    }

    public function showCertificateInList(): bool
    {
        return ! $this->hasNoCertificate();
    }

    public function hasAvailableResources(): bool
    {
        return $this->hasMaterials || $this->hasRecording || $this->certificateAvailable();
    }

    public function hasPendingResources(): bool
    {
        return ! $this->hasRecording || $this->certificateInPreparation();
    }

    /**
     * @return list<array{strong: bool, text: string}>
     */
    public function summaryLines(): array
    {
        $lines = [];

        $availableLabels = [];
        if ($this->hasMaterials) {
            $availableLabels[] = 'materiały szkoleniowe';
        }
        if ($this->hasRecording) {
            $availableLabels[] = 'nagranie';
        }
        if ($this->certificateAvailable()) {
            $availableLabels[] = 'zaświadczenie';
        }

        if ($availableLabels !== []) {
            $lines[] = [
                'strong' => true,
                'text' => $this->formatAvailableResourcesLine($availableLabels),
            ];
        }

        $pending = $this->pendingResourceLabels();

        if ($pending !== []) {
            $lines[] = [
                'strong' => false,
                'text' => $this->formatPendingLine($pending),
            ];
        }

        $lines[] = [
            'strong' => false,
            'text' => $this->footerLine($pending !== []),
        ];

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function pendingResourceLabels(): array
    {
        $pending = [];

        if (! $this->hasRecording) {
            $pending[] = 'nagranie';
        }

        if ($this->certificateInPreparation()) {
            $pending[] = 'zaświadczenie';
        }

        return $pending;
    }

    private function footerLine(bool $hasPending): string
    {
        if ($hasPending) {
            return 'O gotowości damy znać osobnym e-mailem — możesz też zajrzeć później na swoje konto na pnedu.pl.';
        }

        if ($this->hasAvailableResources()) {
            return 'Możesz od razu skorzystać z zasobów na swoim koncie na pnedu.pl.';
        }

        return 'O gotowości damy znać osobnym e-mailem — możesz też zajrzeć później na swoje konto na pnedu.pl.';
    }

    /**
     * @param  list<string>  $labels
     */
    private function formatAvailableResourcesLine(array $labels): string
    {
        if ($labels === ['materiały szkoleniowe']) {
            return 'Materiały szkoleniowe są już dostępne na Twoim koncie.';
        }

        if ($labels === ['nagranie']) {
            return 'Nagranie szkolenia jest już dostępne na Twoim koncie.';
        }

        if ($labels === ['zaświadczenie']) {
            return 'Zaświadczenie jest już dostępne do pobrania na Twoim koncie.';
        }

        $joined = $this->joinPolishList($labels);

        return ucfirst($joined).' '.($this->countPolishPlural($labels) ? 'są' : 'jest').' już dostępne na Twoim koncie.';
    }

    /**
     * @param  list<string>  $items
     */
    private function joinPolishList(array $items): string
    {
        $count = count($items);

        if ($count <= 1) {
            return $items[0] ?? '';
        }

        if ($count === 2) {
            return $items[0].' i '.$items[1];
        }

        return implode(', ', array_slice($items, 0, -1)).' i '.end($items);
    }

    /**
     * @param  list<string>  $labels
     */
    private function countPolishPlural(array $labels): bool
    {
        return count($labels) > 1;
    }

    /**
     * @param  list<string>  $items
     */
    private function formatPendingLine(array $items): string
    {
        $suffix = ' — potrzebujemy chwili, by je przygotować i udostępnić.';

        if ($items === ['nagranie']) {
            return 'Nagranie pojawi się wkrótce'.$suffix;
        }

        if ($items === ['zaświadczenie']) {
            return 'Zaświadczenie pojawi się wkrótce'.$suffix;
        }

        return 'Nagranie i zaświadczenie pojawią się wkrótce'.$suffix;
    }

    private static function courseHasMaterials(Course $course): bool
    {
        return CourseFileLink::query()
            ->where('course_id', $course->id)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->exists();
    }

    private static function courseHasRecording(Course $course): bool
    {
        return CourseVideo::query()
            ->where('course_id', $course->id)
            ->exists();
    }

    private static function normalizeCertificateStatus(mixed $status): string
    {
        $status = trim((string) ($status ?? ''));

        return in_array($status, [self::CERT_DOWNLOAD_ENABLED, self::CERT_IN_PREPARATION, self::CERT_NONE], true)
            ? $status
            : self::CERT_IN_PREPARATION;
    }

    private static function resolveSurveyUrl(Course $course): ?string
    {
        $link = PneadmCourseSurveyLink::query()
            ->where('course_id', $course->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->first(fn (PneadmCourseSurveyLink $item) => $item->isAvailableNow());

        return $link?->gateAbsoluteUrl();
    }
}
