<?php

namespace App\Services\Seo;

use App\Models\Course;
use App\Support\PneadmMedia;
use Illuminate\Support\Str;

class CourseSeoService
{
    public function listingMeta(?string $routeName): array
    {
        $listings = (array) config('course_seo.listings', []);
        $meta = $listings[$routeName] ?? $listings['courses.free'] ?? [
            'title' => 'Bezpłatne szkolenia | PNE',
            'description' => (string) config('seo.default_description'),
        ];

        return [
            'title' => (string) ($meta['title'] ?? 'Bezpłatne szkolenia | PNE'),
            'description' => $this->limitDescription((string) ($meta['description'] ?? '')),
        ];
    }

    public function seoTitle(Course $course): string
    {
        $override = $this->courseOverride($course, 'title');
        if ($override !== null) {
            return $override;
        }

        $suffix = (string) config('course_seo.brand_suffix', ' | PNE');
        $max = max(20, (int) config('course_seo.default_title_max', 60));
        $plain = $course->plainTitle('Szkolenie online');
        $available = max(10, $max - mb_strlen($suffix));

        if (mb_strlen($plain) <= $available) {
            return $plain.$suffix;
        }

        $short = rtrim(Str::limit($plain, $available, ''), " \t\n\r\0\x0B-–—|");
        if ($short === '') {
            $short = Str::limit($plain, $available);
        }

        return $short.$suffix;
    }

    public function seoDescription(Course $course): string
    {
        $override = $this->courseOverride($course, 'description');
        if ($override !== null) {
            return $override;
        }

        $fromDescription = trim(strip_tags((string) ($course->description ?? '')));
        if ($fromDescription !== '') {
            return $this->limitDescription($fromDescription);
        }

        return $this->limitDescription(
            'Szkolenie „'.$course->plainTitle('online').'”: termin, program, forma online i zapis w Platformie Nowoczesnej Edukacji.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function structuredDataGraph(Course $course): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $courseUrl = route('courses.show', $course->id);
        $orgId = $baseUrl.'/#organization';
        $priceInfo = $course->getCurrentPrice();
        $price = $priceInfo['price'] ?? ($course->is_paid ? null : 0.0);
        $image = PneadmMedia::url($course->image) ?? config('seo.default_og_image');

        $courseNode = [
            '@type' => 'Course',
            '@id' => $courseUrl.'#course',
            'name' => $course->plainTitle(),
            'description' => $this->seoDescription($course),
            'url' => $courseUrl,
            'inLanguage' => 'pl-PL',
            'provider' => ['@id' => $orgId],
            'isAccessibleForFree' => ! $course->is_paid,
        ];

        if (is_string($image) && $image !== '') {
            $courseNode['image'] = [$image];
        }

        if ($price !== null) {
            $courseNode['offers'] = [
                '@type' => 'Offer',
                'url' => $course->publicOrderFormUrl(),
                'price' => number_format((float) $price, 2, '.', ''),
                'priceCurrency' => 'PLN',
                'availability' => 'https://schema.org/InStock',
                'category' => $course->is_paid ? 'paid' : 'free',
            ];
        }

        if ($course->start_date !== null) {
            $instance = [
                '@type' => 'CourseInstance',
                'courseMode' => 'online',
                'startDate' => $course->start_date->toAtomString(),
            ];

            if ($course->end_date !== null) {
                $instance['endDate'] = $course->end_date->toAtomString();
            }

            $meetingLink = $course->onlineDetail?->meeting_link;
            if (is_string($meetingLink) && $meetingLink !== '') {
                $instance['location'] = [
                    '@type' => 'VirtualLocation',
                    'url' => $meetingLink,
                ];
            }

            $courseNode['hasCourseInstance'] = $instance;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $courseNode,
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $courseUrl.'#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Strona główna',
                            'item' => route('home'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Szkolenia',
                            'item' => route('courses.individual'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $course->plainTitle(),
                            'item' => $courseUrl,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function courseOverride(Course $course, string $key): ?string
    {
        $overrides = (array) config('course_seo.course_overrides', []);
        $courseOverrides = $overrides[(int) $course->getKey()] ?? null;

        if (! is_array($courseOverrides) || ! isset($courseOverrides[$key])) {
            return null;
        }

        $value = trim((string) $courseOverrides[$key]);

        if ($value === '') {
            return null;
        }

        return $key === 'description'
            ? $this->limitDescription($value)
            : $value;
    }

    private function limitDescription(string $value): string
    {
        $max = max(80, (int) config('course_seo.default_description_max', 160));

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $value) ?? ''), $max);
    }
}
