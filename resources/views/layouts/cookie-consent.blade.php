{{-- 
    resources/views/layouts/cookie-consent.blade.php – baner zgody na używanie plików cookies
    Google Consent Mode v2 (advanced): gtag ładuje się w produkcji z domyślną odmową,
    a poniższy baner aktualizuje zgodę po wyborze użytkownika.
--}}

<div id="cookie-consent-banner" class="fixed-bottom bg-dark text-white p-3 d-none" style="z-index: 2000;">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="mb-2 mb-md-0 opacity-75">
            Na naszej stronie używamy plików cookies w celu zapewnienia prawidłowego działania serwisu oraz analiz statystycznych. Szczegóły znajdziesz w <a href="{{ route('polityka-prywatnosci') }}" class="text-light text-decoration-underline">Polityce prywatności</a>.
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <button id="reject-cookies" class="btn btn-outline-light btn-sm">Odrzucam</button>
            <button id="accept-cookies" class="btn btn-primary btn-sm">Akceptuję</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var STORAGE_KEY = 'cookie_consent';
    var IS_PROD = @json(app()->environment('production'));

    var banner = document.getElementById('cookie-consent-banner');
    var acceptBtn = document.getElementById('accept-cookies');
    var rejectBtn = document.getElementById('reject-cookies');

    function showBanner() {
        banner.classList.remove('d-none');
        document.body.style.paddingBottom = banner.offsetHeight + 'px';
    }

    function hideBanner() {
        banner.classList.add('d-none');
        document.body.style.paddingBottom = '';
    }

    function updateAnalyticsConsent(value) {
        if (!IS_PROD) return;
        if (typeof window.gtag === 'function') {
            window.gtag('consent', 'update', { analytics_storage: value });
        }
        if (typeof window.fbq === 'function') {
            window.fbq('consent', value === 'granted' ? 'grant' : 'revoke');
        }
    }

    var consent = null;
    try { consent = localStorage.getItem(STORAGE_KEY); } catch (e) {}

    if (consent === 'accepted') {
        updateAnalyticsConsent('granted');
    } else if (consent !== 'rejected') {
        showBanner();
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            try { localStorage.setItem(STORAGE_KEY, 'accepted'); } catch (e) {}
            hideBanner();
            updateAnalyticsConsent('granted');
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            try { localStorage.setItem(STORAGE_KEY, 'rejected'); } catch (e) {}
            hideBanner();
            updateAnalyticsConsent('denied');
        });
    }
})();
</script>
@endpush
