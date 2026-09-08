@php
    $gaCourseId = $courseId ?? ($course->id ?? null);
    $gaEvent = $gaEvent ?? 'course_view';
    $skipFunnelStats = $skipMarketingAnalytics ?? app(\App\Services\FunnelSkipService::class)->shouldSkipAnalytics(request());
    $analyticsConsentGranted = app(\App\Services\Analytics\AnalyticsConsentService::class)
        ->hasAnalyticsConsent(request());
    $gaCampaign = $analyticsConsentGranted
        ? app(\App\Services\MarketingAttributionService::class)->resolveCampaignCode(request())
        : null;
@endphp
@if(! $skipFunnelStats)
@push('scripts')
<script>
    (function () {
        var sent = false;

        function sendMarketingEvent() {
            if (sent
                || typeof window.pneHasAnalyticsConsent !== 'function'
                || !window.pneHasAnalyticsConsent()
                || typeof window.gtag !== 'function') {
            return;
        }
            sent = true;
            window.gtag('event', @json($gaEvent), {
                course_id: @json((string) $gaCourseId),
                @if($gaCampaign)
                campaign_id: @json($gaCampaign),
                @endif
            });
        }

        document.addEventListener('DOMContentLoaded', sendMarketingEvent);
        document.addEventListener('pne:analytics-consent-granted', sendMarketingEvent);
    })();
</script>
@endpush
@endif
