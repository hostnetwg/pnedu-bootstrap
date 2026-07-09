<?php

namespace App\Services\Analytics;

use App\Enums\Analytics\AnalyticsEventName;
use App\Services\FunnelSkipService;
use App\Services\MarketingBotDetector;
use Illuminate\Http\Request;
use Throwable;

class GusAnalyticsTracker
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly AnalyticsContextService $context,
        private readonly AnalyticsSessionService $sessions,
        private readonly OrderFormSessionService $orderFormSessions,
        private readonly FunnelSkipService $funnelSkip,
        private readonly MarketingBotDetector $botDetector,
    ) {}

    public function trackLookupStarted(Request $request, string $target, float $startedAt): void
    {
        $this->track($request, AnalyticsEventName::GusLookupStarted, $target, [
            'started_at' => gmdate('c', (int) $startedAt),
        ]);
    }

    public function trackLookupSuccess(
        Request $request,
        string $target,
        int $latencyMs,
        int $fieldsReturnedCount,
        string $responseSource = 'gus',
        string $resultType = 'exact_match',
    ): void {
        $this->track($request, AnalyticsEventName::GusLookupSuccess, $target, [
            'latency_ms' => max(0, $latencyMs),
            'fields_returned_count' => max(0, $fieldsReturnedCount),
            'response_source' => $this->whitelisted($responseSource, ['gus', 'cache', 'unknown']) ?? 'unknown',
            'result_type' => $this->whitelisted($resultType, ['exact_match', 'partial_match', 'unknown']) ?? 'unknown',
        ]);
    }

    public function trackLookupError(
        Request $request,
        string $target,
        string $errorType,
        ?int $httpStatus = null,
        ?int $latencyMs = null,
        ?bool $retryPossible = null,
    ): void {
        $metadata = [
            'error_type' => $this->whitelisted($errorType, AnalyticsEventContract::GUS_ERROR_TYPES) ?? 'unknown',
        ];

        if ($httpStatus !== null) {
            $metadata['http_status'] = max(0, min(599, $httpStatus));
        }

        if ($latencyMs !== null) {
            $metadata['latency_ms'] = max(0, $latencyMs);
        }

        if ($retryPossible !== null) {
            $metadata['retry_possible'] = $retryPossible;
        }

        $this->track($request, AnalyticsEventName::GusLookupError, $target, $metadata);
    }

    public function trackValidationError(Request $request, string $target): void
    {
        $this->trackLookupError($request, $target, 'validation_error', 422, null, false);
    }

    public function countReturnedFields(?array $data): int
    {
        if (! is_array($data)) {
            return 0;
        }

        $count = 0;
        foreach (['name', 'regon', 'postcode', 'city', 'address'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                $count++;
            }
        }

        return $count;
    }

    public function resolveTarget(?string $target): string
    {
        $normalized = strtolower(trim((string) $target));

        return in_array($normalized, ['buyer', 'recipient'], true) ? $normalized : 'buyer';
    }

    private function track(Request $request, AnalyticsEventName $eventName, string $target, array $metadata = []): void
    {
        try {
            if (! $this->shouldTrack($request)) {
                return;
            }

            $courseId = $this->positiveInt($request->input('course_id'));
            $analyticsSessionId = $this->sessions->id($request);
            $preferredFormSessionId = $this->clientUuidOrNull($request->input('form_session_id'));
            $orderFormSessionId = $courseId !== null
                ? $this->orderFormSessions->id($request, $courseId, $preferredFormSessionId)
                : $preferredFormSessionId;

            $payload = array_merge(
                $this->context->fromRequest($request),
                array_filter([
                    'course_id' => $courseId,
                    'analytics_session_id' => $analyticsSessionId,
                    'order_form_session_id' => $orderFormSessionId,
                ], static fn ($value): bool => $value !== null),
                [
                    'metadata' => array_merge(
                        [
                            'tracking_schema_version' => AnalyticsEventContract::SCHEMA_VERSION,
                            'target' => $this->resolveTarget($target),
                            'section_key' => $this->sectionKeyForTarget($target),
                        ],
                        $this->priceVariantMetadata($request),
                        $orderFormSessionId !== null ? ['form_session_id' => $orderFormSessionId] : [],
                        $metadata,
                    ),
                ],
            );

            $this->analytics->track($eventName, $payload);
        } catch (Throwable) {
            // Fail-silent.
        }
    }

    private function shouldTrack(Request $request): bool
    {
        if (! config('analytics.enabled', true)) {
            return false;
        }

        if ($this->funnelSkip->shouldSkipTracking($request) || $this->funnelSkip->shouldSkipAnalytics($request)) {
            return false;
        }

        if ($this->botDetector->isBotOrPreviewCrawler($request)) {
            return false;
        }

        return $request->isMethod('POST');
    }

    private function sectionKeyForTarget(string $target): string
    {
        return $this->resolveTarget($target) === 'recipient' ? 'invoice_recipient' : 'invoice_buyer';
    }

    /**
     * @return array<string, int>
     */
    private function priceVariantMetadata(Request $request): array
    {
        $priceVariantId = $this->positiveInt($request->input('price_variant_id'));

        return $priceVariantId !== null
            ? ['price_variant_id' => $priceVariantId, 'has_price_variant' => true]
            : [];
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function clientUuidOrNull(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1
            ? $value
            : null;
    }

    private function whitelisted(string $value, array $whitelist): ?string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, $whitelist, true) ? $normalized : null;
    }
}
