{{-- GTM noscript może wykonać request tylko po zapisanej zgodzie analitycznej. --}}
@production
    @unless($skipMarketingAnalytics ?? false)
        @php
            $gtmId = config('services.google_tag_manager.id');
            $analyticsConsentGranted = app(\App\Services\Analytics\AnalyticsConsentService::class)
                ->hasAnalyticsConsent(request());
        @endphp
        @if(!empty($gtmId) && $analyticsConsentGranted)
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
        @endif
    @endunless
@endproduction
