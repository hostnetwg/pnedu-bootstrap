@php
    $gaCourseId = $courseId ?? ($course->id ?? null);
    $gaEvent = $gaEvent ?? 'course_view';
    $gaCampaign = app(\App\Services\MarketingAttributionService::class)->resolveCampaignCode(request());
    $skipFunnelStats = $skipMarketingAnalytics ?? app(\App\Services\FunnelSkipService::class)->shouldSkipAnalytics(request());
@endphp
@if(! $skipFunnelStats)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.gtag !== 'function') {
            return;
        }
        window.gtag('event', @json($gaEvent), {
            course_id: @json((string) $gaCourseId),
            @if($gaCampaign)
            campaign_id: @json($gaCampaign),
            @endif
        });
    });
</script>
@endpush
@endif
