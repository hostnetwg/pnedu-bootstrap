@php
    $analyticsConsent = app(\App\Services\Analytics\AnalyticsConsentService::class);
@endphp
<div id="cookie-consent-banner" class="fixed-bottom bg-dark text-white p-3 d-none" style="z-index: 2000;" role="dialog" aria-live="polite" aria-label="Ustawienia cookies">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="mb-2 mb-md-0 opacity-75">
            Niezbędne cookies zapewniają działanie serwisu. Analityczne pomagają nam go ulepszać.
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <button id="reject-cookies" type="button" class="btn btn-outline-light btn-sm">Tylko niezbędne</button>
            <button id="accept-cookies" type="button" class="btn btn-primary btn-sm">Akceptuję analityczne</button>
        </div>
    </div>
</div>

@push('scripts')
<script id="cookie-consent-script">
(function () {
    'use strict';

    var COOKIE_NAME = @json($analyticsConsent->cookieName());
    var ANALYTICS_VALUE = @json(\App\Services\Analytics\AnalyticsConsentService::ANALYTICS);
    var NECESSARY_VALUE = @json(\App\Services\Analytics\AnalyticsConsentService::NECESSARY);
    var MAX_AGE = {{ max(1, (int) config('consent.cookie.days', 180)) * 86400 }};
    var banner = document.getElementById('cookie-consent-banner');
    var acceptBtn = document.getElementById('accept-cookies');
    var rejectBtn = document.getElementById('reject-cookies');

    function readChoice() {
        try {
            var prefix = encodeURIComponent(COOKIE_NAME) + '=';
            var cookies = document.cookie ? document.cookie.split(';') : [];
            for (var i = 0; i < cookies.length; i++) {
                var item = cookies[i].trim();
                if (item.indexOf(prefix) === 0) {
                    return decodeURIComponent(item.substring(prefix.length));
                }
            }
        } catch (e) {}
        return null;
    }

    function saveChoice(value) {
        var cookie = encodeURIComponent(COOKIE_NAME) + '=' + encodeURIComponent(value)
            + '; Path=/; Max-Age=' + MAX_AGE + '; SameSite=Lax';
        if (window.location.protocol === 'https:') {
            cookie += '; Secure';
        }
        document.cookie = cookie;

        try {
            localStorage.removeItem('cookie_consent');
        } catch (e) {}
    }

    function clearAnalyticsCookies() {
        var hostParts = window.location.hostname.split('.');
        var domains = ['', window.location.hostname];
        if (hostParts.length > 1) {
            domains.push('.' + hostParts.slice(-2).join('.'));
        }
        var cookies = document.cookie ? document.cookie.split(';') : [];
        cookies.forEach(function (item) {
            var name = decodeURIComponent(item.split('=')[0].trim());
            if (name !== '_ga' && name !== '_gid' && name.indexOf('_ga_') !== 0 && name.indexOf('_gat') !== 0) {
                return;
            }
            domains.forEach(function (domain) {
                document.cookie = encodeURIComponent(name) + '=; Path=/; Max-Age=0; SameSite=Lax'
                    + (domain ? '; Domain=' + domain : '');
            });
        });
    }

    function showBanner(event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        if (!banner) { return; }
        banner.classList.remove('d-none');
        document.body.style.paddingBottom = banner.offsetHeight + 'px';
        if (acceptBtn) { acceptBtn.focus(); }
    }

    function hideBanner() {
        if (!banner) { return; }
        banner.classList.add('d-none');
        document.body.style.paddingBottom = '';
    }

    function applyChoice(value) {
        saveChoice(value);
        hideBanner();
        if (value === NECESSARY_VALUE) {
            clearAnalyticsCookies();
        }
        if (typeof window.pneSetAnalyticsConsent === 'function') {
            window.pneSetAnalyticsConsent(value === ANALYTICS_VALUE);
        }
    }

    var choice = readChoice();
    if (choice !== ANALYTICS_VALUE && choice !== NECESSARY_VALUE) {
        showBanner();
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            applyChoice(ANALYTICS_VALUE);
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            applyChoice(NECESSARY_VALUE);
        });
    }

    var settingsLinks = document.querySelectorAll('[data-cookie-settings]');
    for (var i = 0; i < settingsLinks.length; i++) {
        settingsLinks[i].addEventListener('click', showBanner);
    }
})();
</script>
@endpush
