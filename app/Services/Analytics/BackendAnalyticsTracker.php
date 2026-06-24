<?php

namespace App\Services\Analytics;

use App\Enums\Analytics\AnalyticsEventName;
use App\Models\Course;
use App\Models\FormOrder;
use App\Services\FunnelSkipService;
use App\Services\MarketingAttributionService;
use App\Services\MarketingBotDetector;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BackendAnalyticsTracker
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly AnalyticsContextService $context,
        private readonly AnalyticsSessionService $sessions,
        private readonly OrderFormSessionService $orderFormSessions,
        private readonly MarketingAttributionService $attribution,
        private readonly FunnelSkipService $funnelSkip,
        private readonly MarketingBotDetector $botDetector,
    ) {}

    public function trackCampaignShortLinkVisit(Request $request, array $campaignContext): void
    {
        $this->trackGetEvent($request, AnalyticsEventName::CampaignShortLinkVisit, $campaignContext);
    }

    public function trackCampaignRedirectResolved(Request $request, array $campaignContext): void
    {
        $this->trackGetEvent($request, AnalyticsEventName::CampaignRedirectResolved, $campaignContext);
    }

    public function trackUtmCaptured(Request $request, array $utmPayload): void
    {
        if ($utmPayload === []) {
            return;
        }

        $this->trackGetEvent($request, AnalyticsEventName::UtmCaptured, [
            'campaign_code' => $utmPayload['campaign_code'] ?? null,
            'utm_source' => $utmPayload['utm_source'] ?? null,
            'utm_medium' => $utmPayload['utm_medium'] ?? null,
            'utm_campaign' => $utmPayload['campaign_code'] ?? null,
            'utm_content' => $utmPayload['utm_content'] ?? null,
            'utm_term' => $request->query('utm_term'),
        ]);
    }

    public function trackCourseDescriptionViewed(Request $request, int $courseId): void
    {
        $this->trackCoursePageViewed($request, $courseId, AnalyticsEventName::CourseDescriptionViewed, [
            'landing_target' => 'course_description',
        ]);
    }

    public function trackOrderFormViewed(Request $request, int $courseId): void
    {
        $orderFormSessionId = $this->orderFormSessions->id($request, $courseId);
        $metadata = [];

        $priceVariantId = $request->query('price_variant_id');
        if (is_numeric($priceVariantId)) {
            $metadata['price_variant_id'] = (int) $priceVariantId;
        }

        $this->trackCoursePageViewed($request, $courseId, AnalyticsEventName::OrderFormViewed, [
            'landing_target' => 'order_form_direct',
            'order_form_session_id' => $orderFormSessionId,
            'metadata' => $metadata,
        ]);
    }

    public function trackOrderFormSubmitAttempted(Request $request, Course $course): void
    {
        $this->trackOrderFormPostEvent(
            $request,
            $course,
            AnalyticsEventName::OrderFormSubmitAttempted,
            [
                'metadata' => array_merge(
                    $this->orderFormSessionMetadata($request, (int) $course->id),
                    [
                        'has_price_variant' => $request->filled('price_variant_id'),
                    ],
                ),
            ]
        );
    }

    public function trackOrderFormValidationFailed(
        Request $request,
        Course $course,
        ValidationException $validationException,
        string $context = 'laravel_validation',
    ): void {
        $fieldKeys = $this->safeValidationFieldKeys(array_keys($validationException->errors()));

        $this->trackOrderFormValidationFailurePayload($request, $course, $fieldKeys, $context, $validationException);
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    public function trackOrderFormManualValidationFailed(
        Request $request,
        Course $course,
        array $fieldKeys,
        string $context,
    ): void {
        $this->trackOrderFormValidationFailurePayload(
            $request,
            $course,
            $this->safeValidationFieldKeys($fieldKeys),
            $context
        );
    }

    public function trackFormOrderCreated(Request $request, Course $course, FormOrder $formOrder, array $context = []): void
    {
        if (! $formOrder->id) {
            return;
        }

        $this->trackOrderFormPostEvent(
            $request,
            $course,
            AnalyticsEventName::FormOrderCreated,
            [
                'form_order_id' => (int) $formOrder->id,
                'landing_target' => 'form_order_created',
                'metadata' => array_merge(
                    $this->orderFormSessionMetadata($request, (int) $course->id),
                    [
                        'order_flow' => $context['order_flow'] ?? null,
                        'payment_type' => $formOrder->payment_mode,
                        'participant_count' => $this->participantCount($formOrder),
                        'has_price_variant' => $formOrder->course_price_variant_id !== null,
                        'has_recipient' => $this->hasRecipient($formOrder),
                        'buyer_type' => $context['buyer_type'] ?? null,
                        'amount_gross' => $formOrder->product_price !== null ? (float) $formOrder->product_price : null,
                        'form_order_status' => $formOrder->payment_status,
                    ],
                ),
            ]
        );
    }

    public function appendResponseCookies(Response $response, Request $request, ?int $orderFormCourseId = null): void
    {
        $this->sessions->appendCookie($response, $request);

        if ($orderFormCourseId !== null) {
            $this->orderFormSessions->appendCookie($response, $request, $orderFormCourseId);
        }
    }

    private function trackOrderFormPostEvent(Request $request, Course $course, AnalyticsEventName $eventName, array $extra = []): void
    {
        try {
            if (! $this->shouldTrackFormPostRequest($request)) {
                return;
            }

            $analyticsSessionId = $this->sessions->id($request);
            if ($analyticsSessionId === null) {
                return;
            }

            $orderFormSessionId = $this->orderFormSessions->id($request, (int) $course->id);
            $campaignCode = $this->attribution->resolveCampaignCode($request);
            $metadata = array_merge(
                $this->priceVariantMetadata($request),
                is_array($extra['metadata'] ?? null) ? $extra['metadata'] : []
            );
            unset($extra['metadata']);

            $this->analytics->track($eventName, array_merge(
                $this->context->fromRequest($request),
                [
                    'analytics_session_id' => $analyticsSessionId,
                    'order_form_session_id' => $orderFormSessionId,
                    'course_id' => (int) $course->id,
                    'course_title_snapshot' => $course->title,
                    'campaign_code' => $campaignCode,
                    'utm_campaign' => $campaignCode,
                    'landing_target' => 'order_form_submit',
                    'metadata' => $metadata,
                ],
                $extra,
            ));
        } catch (Throwable) {
            // Backend tracking must stay fail-silent.
        }
    }

    private function trackOrderFormValidationFailurePayload(
        Request $request,
        Course $course,
        array $fieldKeys,
        string $context,
        ?ValidationException $validationException = null,
    ): void {
        $this->trackOrderFormPostEvent(
            $request,
            $course,
            AnalyticsEventName::OrderFormValidationFailed,
            [
                'metadata' => array_merge(
                    $this->orderFormSessionMetadata($request, (int) $course->id),
                    [
                        'validation_context' => $context,
                        'error_count' => count($fieldKeys),
                        'field_keys' => $fieldKeys,
                        'section_keys' => $this->sectionKeysForFields($fieldKeys),
                        'error_codes' => $this->safeValidationErrorCodes($validationException),
                    ],
                ),
            ]
        );
    }

    private function trackCoursePageViewed(Request $request, int $courseId, AnalyticsEventName $eventName, array $extra = []): void
    {
        if ($courseId <= 0) {
            return;
        }

        $course = Course::query()->select(['id', 'title'])->find($courseId);
        $campaignCode = $this->attribution->resolveCampaignCode($request);

        $this->trackGetEvent($request, $eventName, array_merge([
            'course_id' => $courseId,
            'course_title_snapshot' => $course?->title,
            'campaign_code' => $campaignCode,
            'utm_campaign' => $campaignCode,
        ], $extra));
    }

    private function trackGetEvent(Request $request, AnalyticsEventName $eventName, array $payload = []): void
    {
        try {
            if (! $this->shouldTrackGetRequest($request)) {
                return;
            }

            $analyticsSessionId = $this->sessions->id($request);
            if ($analyticsSessionId === null) {
                return;
            }

            $this->analytics->track($eventName, array_merge(
                $this->context->fromRequest($request),
                $payload,
                ['analytics_session_id' => $analyticsSessionId],
            ));
        } catch (Throwable) {
            // Backend tracking must stay fail-silent.
        }
    }

    private function shouldTrackGetRequest(Request $request): bool
    {
        if (! config('analytics.enabled', true)) {
            return false;
        }

        if ($this->funnelSkip->shouldSkipTracking($request) || $this->funnelSkip->shouldSkipAnalytics($request)) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->prefetch() || $request->header('Purpose') === 'prefetch') {
            return false;
        }

        return ! $this->botDetector->isBotOrPreviewCrawler($request);
    }

    private function shouldTrackFormPostRequest(Request $request): bool
    {
        if (! config('analytics.enabled', true)) {
            return false;
        }

        if ($this->funnelSkip->shouldSkipTracking($request) || $this->funnelSkip->shouldSkipAnalytics($request)) {
            return false;
        }

        return $request->isMethod('POST');
    }

    private function priceVariantMetadata(Request $request): array
    {
        $priceVariantId = $request->input('price_variant_id');

        if (! is_numeric($priceVariantId)) {
            return [];
        }

        return [
            'price_variant_id' => (int) $priceVariantId,
        ];
    }

    private function orderFormSessionMetadata(Request $request, int $courseId): array
    {
        $fromCookie = $request->cookie($this->orderFormSessions->cookieName($courseId));

        return [
            'order_form_session_created_on_submit' => ! is_string($fromCookie) || $fromCookie === '',
        ];
    }

    /**
     * @param  list<string>  $fieldKeys
     * @return list<string>
     */
    private function safeValidationFieldKeys(array $fieldKeys): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (string $fieldKey): string => $this->normalizeValidationFieldKey($fieldKey),
            $fieldKeys
        ))));
    }

    private function normalizeValidationFieldKey(string $fieldKey): string
    {
        $fieldKey = preg_replace('/\.\d+\./', '.', $fieldKey) ?? $fieldKey;
        $fieldKey = preg_replace('/\.\d+$/', '', $fieldKey) ?? $fieldKey;
        $fieldKey = str_replace(['-', ' '], '_', strtolower($fieldKey));

        return match ($fieldKey) {
            'participants.email' => 'participant_email',
            'participants.name' => 'participant_name_field',
            default => $fieldKey,
        };
    }

    /**
     * @param  list<string>  $fieldKeys
     * @return list<string>
     */
    private function sectionKeysForFields(array $fieldKeys): array
    {
        $sections = [];

        foreach ($fieldKeys as $fieldKey) {
            $sections[] = match (true) {
                str_starts_with($fieldKey, 'buyer_') => 'buyer',
                str_starts_with($fieldKey, 'recipient_') => 'recipient',
                str_starts_with($fieldKey, 'participant_') => 'participant',
                str_starts_with($fieldKey, 'payment_') => 'payment',
                str_starts_with($fieldKey, 'contact_') => 'contact',
                default => 'order_form',
            };
        }

        return array_values(array_unique($sections));
    }

    /**
     * @return list<string>
     */
    private function safeValidationErrorCodes(?ValidationException $validationException): array
    {
        if ($validationException === null || ! isset($validationException->validator)) {
            return ['validation_failed'];
        }

        $failed = $validationException->validator->failed();
        $codes = [];

        foreach ($failed as $rules) {
            foreach (array_keys($rules) as $rule) {
                $codes[] = strtolower((string) $rule);
            }
        }

        return $codes === [] ? ['validation_failed'] : array_values(array_unique($codes));
    }

    private function participantCount(FormOrder $formOrder): int
    {
        try {
            return (int) $formOrder->participants()->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function hasRecipient(FormOrder $formOrder): bool
    {
        return filled($formOrder->recipient_name)
            || filled($formOrder->recipient_address)
            || filled($formOrder->recipient_postal_code)
            || filled($formOrder->recipient_city)
            || filled($formOrder->recipient_nip);
    }
}
