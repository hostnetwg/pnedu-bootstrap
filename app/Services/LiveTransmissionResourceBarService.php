<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseFileLink;
use App\Models\CourseOnlineDetail;
use App\Models\PneadmCourseSurveyLink;
use App\Support\OrderFormVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
     * Osadzony /transmisja i gość /live/{token}: rejestracji na belce nie ma
     * (gość wypełnia formularz przed wejściem).
     */
    public const ATTENDANCE_VISIBLE_ON_AUTHENTICATED_EMBED = false;

    /** Poll belki u uczestnika — tani JSON; API ClickMeeting i tak ma cache 12 s. */
    public const VIEWER_POLL_MS = 5000;

    public const VIEWER_POLL_FIRST_MS = 400;

    /** Wspólny cache belki+oferty na czas jednego „piku” wielu widzów. */
    public const VIEWER_PAYLOAD_CACHE_SECONDS = 2;

    /** Jak w ADM: oferta znika po tylu sekundach od włączenia. */
    public const LIVE_OFFER_AUTO_HIDE_SECONDS = 120;

    /**
     * @return array{resource_links: list<array{key: string, label: string, url: string}>, live_offer: array<string, mixed>|null}
     */
    public function viewerPayload(?Course $course, bool $forGuest = false): array
    {
        if (! $course) {
            return [
                'resource_links' => [],
                'live_offer' => null,
            ];
        }

        $courseId = (int) $course->id;
        if ($courseId <= 0) {
            return [
                'resource_links' => [],
                'live_offer' => null,
            ];
        }

        /** @var array{resource_links: list<array{key: string, label: string, url: string}>, live_offer: array<string, mixed>|null} */
        return Cache::remember(
            'live_tx_bar:'.$courseId.':'.($forGuest ? 'g' : 'a'),
            self::VIEWER_PAYLOAD_CACHE_SECONDS,
            function () use ($course, $forGuest): array {
                $fresh = $course->fresh(['onlineDetail', 'fileLinks']) ?? $course;

                return [
                    'resource_links' => $this->visibleLinks($fresh, $forGuest),
                    'live_offer' => $this->visibleOffer($fresh),
                ];
            }
        );
    }

    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public function visibleLinks(?Course $course, bool $forGuest = false): array
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

        if (! $forGuest && self::ATTENDANCE_VISIBLE_ON_AUTHENTICATED_EMBED && $details->live_bar_attendance_enabled) {
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

        if (! $forGuest && $details->live_bar_certificate_enabled) {
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

    /** Skrót opisu w modalu oferty (pełny opis: link „Opis szkolenia” → strona kursu). */
    public const OFFER_DESCRIPTION_MAX = 220;

    /**
     * Oferta kolejnego szkolenia — wyśrodkowane okienko na /transmisja (sterowanie z ADM).
     *
     * @return array{
     *   course_id: int,
     *   title: string,
     *   start_date: ?string,
     *   instructor: ?string,
     *   image_url: ?string,
     *   description: ?string,
     *   price: array{
     *     amount: float,
     *     amount_label: string,
     *     original_amount: ?float,
     *     original_amount_label: ?string,
     *     is_promotion: bool,
     *     promotion_end_label: ?string,
     *     omnibus_lowest_label: ?string,
     *     is_free: bool
     *   }|null,
     *   order_url: string,
     *   description_url: string
     * }|null
     *
     * `description` = skrót ze złamaniami wierszy; pełny opis na `description_url`.
     * `order_url` → formularz zamówienia; `description_url` → publiczny opis szkolenia.
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

        if ($this->expireLiveOfferIfNeeded($details)) {
            $this->forgetViewerPayloadCache((int) $liveCourse->id);
            $details->refresh();
        }

        if (! $details->live_offer_enabled) {
            return null;
        }

        $offerId = (int) ($details->live_offer_course_id ?? 0);
        if ($offerId <= 0 || $offerId === (int) $liveCourse->id) {
            return null;
        }

        $offer = Course::query()
            ->with([
                'instructor:id,title,first_name,last_name',
                'priceVariants',
            ])
            ->find($offerId);
        if (! $offer instanceof Course) {
            return null;
        }

        $tz = (string) config('app.timezone', 'Europe/Warsaw');
        $instructor = $offer->instructor;
        $instructorName = $instructor
            ? trim((string) $instructor->full_name_with_title)
            : '';

        $autoHide = $details->live_offer_auto_hide !== false;
        $enabledAt = $autoHide ? $details->live_offer_enabled_at : null;
        $expiresAt = ($autoHide && $enabledAt)
            ? $enabledAt->copy()->addSeconds(self::LIVE_OFFER_AUTO_HIDE_SECONDS)
            : null;

        return [
            'course_id' => (int) $offer->id,
            'title' => $offer->plainTitle('Szkolenie'),
            'start_date' => $offer->start_date
                ? $offer->start_date->copy()->timezone($tz)->format('d.m.Y H:i')
                : null,
            'instructor' => $instructorName !== '' ? $instructorName : null,
            'image_url' => $offer->publicImageUrl(),
            'description' => $this->shortOfferDescription($offer),
            'price' => $this->offerPricePayload($offer),
            'order_url' => $this->offerOrderUrl($offer),
            'description_url' => route('courses.show', $offer->id, true),
            'auto_hide' => $autoHide,
            'enabled_at' => $enabledAt?->toIso8601String(),
            'expires_at' => $expiresAt?->toIso8601String(),
            'auto_hide_seconds' => self::LIVE_OFFER_AUTO_HIDE_SECONDS,
        ];
    }

    /**
     * Wyłącza ofertę po LIVE_OFFER_AUTO_HIDE_SECONDS tylko gdy live_offer_auto_hide = true.
     */
    public function expireLiveOfferIfNeeded(CourseOnlineDetail $details): bool
    {
        if (! $details->live_offer_enabled) {
            if ($details->live_offer_enabled_at !== null) {
                $details->live_offer_enabled_at = null;
                $details->save();

                return true;
            }

            return false;
        }

        if ($details->live_offer_auto_hide === false) {
            if ($details->live_offer_enabled_at !== null) {
                $details->live_offer_enabled_at = null;
                $details->save();

                return true;
            }

            return false;
        }

        if ($details->live_offer_enabled_at === null) {
            $details->live_offer_enabled_at = now();
            $details->save();

            return true;
        }

        $deadline = $details->live_offer_enabled_at->copy()
            ->addSeconds(self::LIVE_OFFER_AUTO_HIDE_SECONDS);
        if (now()->lt($deadline)) {
            return false;
        }

        $details->live_offer_enabled = false;
        $details->live_offer_enabled_at = null;
        $details->save();

        return true;
    }

    public function forgetViewerPayloadCache(int $courseId): void
    {
        if ($courseId <= 0) {
            return;
        }
        Cache::forget('live_tx_bar:'.$courseId.':a');
        Cache::forget('live_tx_bar:'.$courseId.':g');
    }

    /**
     * Pełny tekst opisu oferty (ze złamaniami wierszy) — bez limitu skrótu.
     */
    public function offerDescriptionText(Course $offer): ?string
    {
        $html = trim((string) ($offer->offer_description_html ?? ''));
        if ($html === '') {
            $html = trim((string) ($offer->description ?? ''));
        }
        if ($html === '') {
            return null;
        }

        $withBreaks = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $html) ?? $html;
        $withBreaks = preg_replace('/<\/\s*(p|div|h[1-6]|li|tr)\s*>/iu', "\n", $withBreaks) ?? $withBreaks;
        $withBreaks = preg_replace('/<\s*li[^>]*>/iu', '• ', $withBreaks) ?? $withBreaks;

        $plain = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = str_replace("\r\n", "\n", $plain);
        $plain = preg_replace('/\x{00A0}/u', ' ', $plain) ?? $plain;
        $plain = preg_replace('/[ \t]+/u', ' ', $plain) ?? $plain;
        $plain = preg_replace('/ *\n */u', "\n", $plain) ?? $plain;
        $plain = preg_replace("/\n{3,}/u", "\n\n", $plain) ?? $plain;
        $plain = trim($plain);

        return $plain !== '' ? $plain : null;
    }

    /** Skrót do modala (entery zachowane, długość ograniczona). */
    public function shortOfferDescription(Course $offer): ?string
    {
        $plain = $this->offerDescriptionText($offer);
        if ($plain === null) {
            return null;
        }

        return Str::limit($plain, self::OFFER_DESCRIPTION_MAX);
    }

    /**
     * @return array{
     *   amount: float,
     *   amount_label: string,
     *   original_amount: ?float,
     *   original_amount_label: ?string,
     *   is_promotion: bool,
     *   promotion_end_label: ?string,
     *   omnibus_lowest_label: ?string,
     *   is_free: bool
     * }|null
     */
    public function offerPricePayload(Course $offer): ?array
    {
        if (! $offer->is_paid) {
            return [
                'amount' => 0.0,
                'amount_label' => 'Bezpłatne',
                'original_amount' => null,
                'original_amount_label' => null,
                'is_promotion' => false,
                'promotion_end_label' => null,
                'omnibus_lowest_label' => null,
                'is_free' => true,
            ];
        }

        $info = $offer->getCurrentPrice();
        if ($info === null) {
            return null;
        }

        $amount = (float) ($info['price'] ?? 0);
        $original = isset($info['original_price']) ? (float) $info['original_price'] : null;
        $isPromotion = (bool) ($info['is_promotion'] ?? false);
        $promotionEnd = $info['promotion_end'] ?? null;
        $promotionEndLabel = null;
        if ($isPromotion && $promotionEnd) {
            try {
                $promotionEndLabel = \Carbon\Carbon::parse($promotionEnd)
                    ->timezone((string) config('app.timezone', 'Europe/Warsaw'))
                    ->format('d.m.Y H:i');
            } catch (\Throwable) {
                $promotionEndLabel = null;
            }
        }

        $omnibus = $info['omnibus_lowest_price'] ?? null;
        $omnibusLabel = $omnibus !== null
            ? 'Najniższa cena z 30 dni przed obniżką: '.$this->formatPln((float) $omnibus)
            : null;

        return [
            'amount' => $amount,
            'amount_label' => $this->formatPln($amount),
            'original_amount' => $isPromotion ? $original : null,
            'original_amount_label' => ($isPromotion && $original !== null)
                ? $this->formatPln($original)
                : null,
            'is_promotion' => $isPromotion,
            'promotion_end_label' => $promotionEndLabel,
            'omnibus_lowest_label' => $omnibusLabel,
            'is_free' => false,
        ];
    }

    public function offerOrderUrl(Course $offer): string
    {
        $url = route(OrderFormVariant::publicRouteName(), $offer->id, true);
        if (! $offer->is_paid) {
            return $url;
        }

        $offer->loadMissing('priceVariants');
        $courseEnded = $offer->hasEnded();
        $active = $offer->priceVariants
            ->filter(fn ($variant) => (bool) $variant->is_active
                && $variant->isAvailableForCourseEndState($courseEnded))
            ->values();

        if ($active->count() !== 1) {
            return $url;
        }

        $variantId = (int) $active->first()->id;
        if ($variantId <= 0) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'price_variant_id='.$variantId;
    }

    private function formatPln(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' zł';
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
