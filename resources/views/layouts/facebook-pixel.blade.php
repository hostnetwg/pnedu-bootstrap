{{--
    resources/views/layouts/facebook-pixel.blade.php
    Meta (Facebook) Pixel — ładuje się tylko w produkcji, gdy ustawione jest FACEBOOK_PIXEL_ID.
    Zintegrowany z bannerem zgody na cookies (analogicznie do Google Analytics).
    Domyślnie po załadowaniu wywoływany jest fbq('consent', 'revoke') – PageView wystrzeli
    dopiero po akceptacji cookies (cookie-consent.blade.php → 'grant').
--}}
@production
    @php
        $fbPixelId = config('services.facebook_pixel.id');
    @endphp
    @if(!empty($fbPixelId))
        <!-- Meta Pixel Code -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');

        (function () {
            var consent = null;
            try { consent = localStorage.getItem('cookie_consent'); } catch (e) {}
            if (consent !== 'accepted') {
                fbq('consent', 'revoke');
            }
        })();

        fbq('init', @json($fbPixelId));
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"
            alt=""/></noscript>
        <!-- End Meta Pixel Code -->
    @endif
@endproduction
