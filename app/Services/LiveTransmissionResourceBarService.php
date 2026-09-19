<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseFileLink;
use App\Models\CourseOnlineDetail;
use App\Models\PneadmCourseSurveyLink;

/**
 * Linki belki zasobów na /transmisja — te same flagi co panel ADM `/courses/{id}/live`.
 */
class LiveTransmissionResourceBarService
{
    public const MAX_MATERIAL_LINKS = 5;

    public const LABEL_ATTENDANCE = 'Rejestracja: lista obecności';

    public const LABEL_CERTIFICATE = 'Pobierz zaświadczenie';

    public const LABEL_MATERIALS = 'Pobierz materiały';

    public const LABEL_SURVEY = 'Wypełnij ankietę';

    /**
     * Osadzony /transmisja jest dziś tylko dla zalogowanego uczestnika — rejestracja na belce
     * wróci, gdy live będzie dostępny bez konta pnedu (np. zamknięty link od dyrektora).
     */
    public const ATTENDANCE_VISIBLE_ON_AUTHENTICATED_EMBED = false;

    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public function visibleLinks(?Course $course): array
    {
        if (! $course) {
            return [];
        }

        $course->loadMissing(['onlineDetail', 'fileLinks']);
        $details = $course->onlineDetail;
        if (! $details instanceof CourseOnlineDetail || ! $details->embed_on_pnedu) {
            return [];
        }

        $links = [];

        if (self::ATTENDANCE_VISIBLE_ON_AUTHENTICATED_EMBED && $details->live_bar_attendance_enabled) {
            $url = $this->attendanceUrl($course);
            if ($url !== null) {
                $links[] = [
                    'key' => 'attendance',
                    'label' => self::LABEL_ATTENDANCE,
                    'url' => $url,
                ];
            }
        }

        if ($details->live_bar_materials_enabled) {
            foreach ($this->materialItems($course) as $item) {
                $links[] = [
                    'key' => 'material-'.$item['id'],
                    'label' => self::LABEL_MATERIALS,
                    'url' => $item['url'],
                ];
            }
        }

        if ($details->live_bar_survey_enabled) {
            foreach ($this->surveyItems($course) as $item) {
                $links[] = [
                    'key' => 'survey-'.$item['id'],
                    'label' => self::LABEL_SURVEY,
                    'url' => $item['url'],
                ];
            }
        }

        if ($details->live_bar_certificate_enabled) {
            $url = $this->certificateDownloadUrl($course);
            if ($url !== null) {
                $links[] = [
                    'key' => 'certificate',
                    'label' => self::LABEL_CERTIFICATE,
                    'url' => $url,
                ];
            }
        }

        return $links;
    }

    /**
     * Oferta kolejnego szkolenia na belce pod paskiem PNE (sterowanie z ADM).
     *
     * @return array{course_id: int, title: string, start_date: ?string, instructor: ?string, order_url: string}|null
     *
     * `order_url` to publiczny opis szkolenia (`courses.show`), nie formularz zamówienia.
     */
    public function visibleOffer(?Course $liveCourse): ?array
    {
        if (! $liveCourse) {
            return null;
        }

        $liveCourse->loadMissing('onlineDetail');
        $details = $liveCourse->onlineDetail;
        if (! $details instanceof CourseOnlineDetail || ! $details->embed_on_pnedu) {
            return null;
        }
        if (! $details->live_offer_enabled) {
            return null;
        }

        $offerId = (int) ($details->live_offer_course_id ?? 0);
        if ($offerId <= 0 || $offerId === (int) $liveCourse->id) {
            return null;
        }

        $offer = Course::query()
            ->with('instructor:id,title,first_name,last_name')
            ->find($offerId);
        if (! $offer instanceof Course) {
            return null;
        }

        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $instructor = $offer->instructor;
        $instructorName = $instructor
            ? trim((string) $instructor->full_name_with_title)
            : '';

        return [
            'course_id' => (int) $offer->id,
            'title' => $offer->plainTitle('Szkolenie'),
            'start_date' => $offer->start_date
                ? $offer->start_date->copy()->timezone($tz)->format('d.m.Y H:i')
                : null,
            'instructor' => $instructorName !== '' ? $instructorName : null,
            'order_url' => route('courses.show', $offer->id, true),
        ];
    }

    public function attendanceUrl(Course $course): ?string
    {
        $open = (bool) ($course->certificate_registration_open ?? false);
        $token = trim((string) ($course->certificate_registration_token ?? ''));
        if (! $open || $token === '') {
            return null;
        }

        return route('certificate-registration.show', ['token' => $token], absolute: true);
    }

    public function certificateDownloadUrl(Course $course): ?string
    {
        if (($course->certificate_download_status ?? '') !== 'download_enabled') {
            return null;
        }

        return route('dashboard.zaswiadczenia.course', $course->id, true);
    }

    /**
     * @return list<array{id: int, label: string, url: string}>
     */
    private function materialItems(Course $course): array
    {
        $items = [];
        $fileLinks = $course->relationLoaded('fileLinks')
            ? $course->fileLinks
            : $course->fileLinks()->get();

        foreach ($fileLinks as $link) {
            if (! $link instanceof CourseFileLink) {
                continue;
            }
            $url = trim((string) ($link->url ?? ''));
            if ($url === '') {
                continue;
            }
            $title = trim((string) ($link->title ?? ''));
            $items[] = [
                'id' => (int) $link->id,
                'label' => $title !== '' ? $title : 'Materiały',
                'url' => $url,
            ];
            if (count($items) >= self::MAX_MATERIAL_LINKS) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return list<array{id: int, label: string, url: string}>
     */
    private function surveyItems(Course $course): array
    {
        $items = [];
        $links = PneadmCourseSurveyLink::query()
            ->where('course_id', $course->id)
            ->orderBy('order')
            ->get();

        foreach ($links as $link) {
            if (! $link->isAvailableNow()) {
                continue;
            }
            $url = trim((string) ($link->gateAbsoluteUrl() ?: $link->url ?: ''));
            if ($url === '') {
                continue;
            }
            $title = trim((string) ($link->title ?? ''));
            $items[] = [
                'id' => (int) $link->id,
                'label' => $title !== '' ? $title : 'Ankieta',
                'url' => $url,
            ];
        }

        return $items;
    }
}
