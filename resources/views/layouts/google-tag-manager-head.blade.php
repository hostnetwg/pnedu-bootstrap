{{--
    resources/views/layouts/google-tag-manager-head.blade.php
    Google Tag Manager — skrypt w <head> (produkcja, GOOGLE_TAG_MANAGER_ID).
    Consent Mode v2: domyślna odmowa przed załadowaniem GTM; aktualizacja w cookie-consent.blade.php.
--}}
@production
    @php
        $gtmId = config('services.google_tag_manager.id');
    @endphp
    @if(!empty($gtmId))
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
        <!-- Google Tag Manager -->
        <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($gtmId));
        </script>
        <!-- End Google Tag Manager -->
    @endif
@endproduction
