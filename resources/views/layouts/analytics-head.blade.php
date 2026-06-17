{{--
    resources/views/layouts/analytics-head.blade.php
    Consent Mode v2 + opcjonalnie GTM (agencja) oraz własne GA4 (GOOGLE_ANALYTICS_ID).
    Ładuje się tylko w produkcji. Przy opt-out lejka (pne_skip_funnel) — bez GA/GTM.
--}}
@production
    @unless($skipMarketingAnalytics ?? false)
        @php
            $gtmId = config('services.google_tag_manager.id');
            $gaId = config('services.google_analytics.id');
        @endphp
        @if(!empty($gtmId) || !empty($gaId))
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}

                gtag('consent', 'default', {
                    analytics_storage: 'denied',
                    functionality_storage: 'granted',
                    security_storage: 'granted',
                    wait_for_update: 500
                });

                (function () {
                    try {
                        var consent = localStorage.getItem('cookie_consent');
                        if (consent === 'accepted') {
                            gtag('consent', 'update', { analytics_storage: 'granted' });
                        }
                    } catch (e) {}
                })();
            </script>

            @if(!empty($gtmId))
                <!-- Google Tag Manager (agencja) -->
                <script>
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer',@json($gtmId));
                </script>
                <!-- End Google Tag Manager -->
            @endif

            @if(!empty($gaId))
                <!-- Google Analytics 4 (właściciel) -->
                <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
                <script>
                    gtag('config', @json($gaId));
                </script>
                <!-- End Google Analytics 4 -->
            @endif
        @endif
    @endunless
@endproduction
