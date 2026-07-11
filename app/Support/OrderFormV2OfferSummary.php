<?php

namespace App\Support;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Kompaktowe dane oferty szkolenia do bloku nad formularzem zamówienia V2.
 */
final class OrderFormV2OfferSummary
{
    /**
     * @return array{
     *     title: string,
     *     date_line: string|null,
     *     duration: string|null,
     *     trainer_label: string|null,
     *     trainer_name: string|null,
     *     trainer_photo_url: string|null,
     *     format_label: string|null,
     *     platform_label: string|null,
     *     additional_line: string|null,
     *     recording_line: string|null,
     *     variant_label: string|null,
     *     price_info: array<string, mixed>|null,
     *     course_url: string
     * }
     */
    public static function fromCourse(Course $course, ?array $priceInfo): array
    {
        $startDate = $course->start_date
            ? Carbon::parse($course->start_date)->locale('pl')
            : null;

        $duration = null;
        if ($startDate && $course->end_date) {
            $diff = $startDate->diff(Carbon::parse($course->end_date));
            $duration = trim(($diff->h ? $diff->h.'h ' : '').($diff->i ? $diff->i.'min' : ''));
            $duration = $duration !== '' ? $duration : null;
        }

        $dateLine = null;
        if ($startDate) {
            $formattedDate = $startDate->translatedFormat('j F Y').' '.$startDate->format('H:i');
            $dateLine = $formattedDate.' ('.$startDate->translatedFormat('l').')';
        }

        $formatLabel = self::formatLabel($course->type);
        $platformLabel = self::shouldShowPlatform($course->type)
            ? self::platformLabel($course)
            : null;

        $trainerPhoto = null;
        if ($course->relationLoaded('instructor') && $course->instructor && filled($course->instructor->photo)) {
            $trainerPhoto = PneadmMedia::url($course->instructor->photo);
        }

        return [
            'title' => trim(strip_tags((string) $course->title)),
            'date_line' => $dateLine,
            'duration' => $duration,
            'trainer_label' => $course->trainer_title ?? null,
            'trainer_name' => self::trainerName($course),
            'trainer_photo_url' => $trainerPhoto,
            'format_label' => $formatLabel,
            'platform_label' => $platformLabel,
            'additional_line' => self::additionalLine($course),
            'recording_line' => self::recordingLine($course),
            'variant_label' => self::variantLabel($course, $priceInfo),
            'price_info' => $priceInfo,
            'course_url' => route('courses.show', $course->id),
        ];
    }

    private static function trainerName(Course $course): ?string
    {
        $name = trim((string) $course->trainer);
        if ($name === '' || $name === 'Brak trenera') {
            return null;
        }

        return $name;
    }

    private static function variantLabel(Course $course, ?array $priceInfo): ?string
    {
        if ($priceInfo === null || self::activeVariantCount($course) <= 1) {
            return null;
        }

        $name = trim((string) ($priceInfo['variant_name'] ?? ''));
        if ($name === '' || preg_match('/^#\d+$/', $name)) {
            return null;
        }

        return 'Wariant: '.$name;
    }

    private static function activeVariantCount(Course $course): int
    {
        $variants = $course->relationLoaded('priceVariants')
            ? $course->priceVariants
            : $course->priceVariants()->where('is_active', true)->get();

        return $variants
            ->filter(fn ($variant) => (bool) $variant->is_active)
            ->filter(fn ($variant) => $variant->isAvailableForCourseEndState($course->hasEnded()))
            ->count();
    }

    private static function formatLabel(?string $type): ?string
    {
        $normalized = Str::lower(trim((string) ($type ?? 'online')));

        return match ($normalized) {
            'online' => 'Online',
            'stacjonarne', 'stationary', 'onsite' => 'Stacjonarne',
            '' => null,
            default => ucfirst($normalized),
        };
    }

    private static function shouldShowPlatform(?string $type): bool
    {
        $normalized = Str::lower(trim((string) ($type ?? 'online')));

        return ! in_array($normalized, ['stacjonarne', 'stationary', 'onsite'], true);
    }

    private static function platformLabel(Course $course): ?string
    {
        $platform = $course->relationLoaded('onlineDetail') && $course->onlineDetail
            ? trim((string) ($course->onlineDetail->platform ?? ''))
            : '';

        if ($platform !== '') {
            return ucfirst($platform);
        }

        return 'Zoom';
    }

    private static function additionalLine(Course $course): ?string
    {
        $base = trim((string) ($course->additional_info ?? ''));
        $line = $base !== '' ? $base : 'Materiały do pobrania, zaświadczenie';

        if (! Str::contains(Str::lower($line), 'pytań')) {
            $line .= ', sesja pytań i odpowiedzi';
        }

        return $line;
    }

    private static function recordingLine(Course $course): ?string
    {
        if (! $course->is_paid) {
            return null;
        }

        $recording = trim((string) ($course->recording_access ?? ''));

        return $recording !== '' ? $recording : '2 miesiące';
    }
}
