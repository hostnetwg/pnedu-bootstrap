@php
    $analyticsConsent = app(\App\Services\Analytics\AnalyticsConsentService::class);
    $analyticsConsentGranted = ! ($skipMarketingAnalytics ?? false)
        && $analyticsConsent->hasAnalyticsConsent(request());
    $gtmId = app()->environment('production') && ! ($skipMarketingAnalytics ?? false)
        ? config('services.google_tag_manager.id')
        : null;
    $gaId = app()->environment('production') && ! ($skipMarketingAnalytics ?? false)
        ? config('services.google_analytics.id')
        : null;
@endphp
<script>
(function () {
    'use strict';

    var CONSENT_COOKIE = @json($analyticsConsent->cookieName());
    var ANALYTICS_VALUE = @json(\App\Services\Analytics\AnalyticsConsentService::ANALYTICS);
    var ANALYTICS_ENDPOINT_PATH = @json('/'.ltrim(parse_url(route('analytics.client-events.store'), PHP_URL_PATH), '/'));
    var GTM_ID;
    var GA_ID;
    var GOOGLE_ENABLED;
    var googleLoaded = false;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

    function consentState(analyticsGranted) {
        return {
            analytics_storage: analyticsGranted ? 'granted' : 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied'
        };
    }

    window.gtag('consent', 'default', consentState(false));

    window.pneHasAnalyticsConsent = function () {
        try {
            var prefix = encodeURIComponent(CONSENT_COOKIE) + '=';
            var cookies = document.cookie ? document.cookie.split(';') : [];
            for (var i = 0; i < cookies.length; i++) {
                var item = cookies[i].trim();
                if (item.indexOf(prefix) === 0) {
                    return decodeURIComponent(item.substring(prefix.length)) === ANALYTICS_VALUE;
                }
            }
        } catch (e) {}
        return false;
    };

    function isAnalyticsEndpoint(input) {
        try {
            var value = input && input.url ? input.url : String(input);
            return new URL(value, window.location.href).pathname === ANALYTICS_ENDPOINT_PATH;
        } catch (e) {
            return false;
        }
    }

    function isPrivateOrLoopbackHost(hostname) {
        if (!hostname) { return false; }
        var host = String(hostname).toLowerCase();
        return host === 'localhost'
            || host.slice(-6) === '.local'
            || /^127\./.test(host)
            || /^10\./.test(host)
            || /^192\.168\./.test(host)
            || /^172\.(1[6-9]|2\d|3[0-1])\./.test(host);
    }

    function isLocalNetworkRequest(input) {
        try {
            var value = input && input.url ? input.url : String(input);
            var url = new URL(value, window.location.href);
            return (url.protocol === 'http:' || url.protocol === 'https:')
                && isPrivateOrLoopbackHost(url.hostname);
        } catch (e) {
            return false;
        }
    }

    function shouldBlock(input) {
        return (GOOGLE_ENABLED && isLocalNetworkRequest(input))
            || (isAnalyticsEndpoint(input) && !window.pneHasAnalyticsConsent());
    }

    if (typeof window.fetch === 'function') {
        var originalFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            if (shouldBlock(input)) {
                return Promise.reject(new Error(
                    isAnalyticsEndpoint(input) ? 'Analytics consent required' : 'Blocked local network request'
                ));
            }
            return originalFetch(input, init);
        };
    }

    if (typeof window.XMLHttpRequest === 'function' && window.XMLHttpRequest.prototype) {
        var originalOpen = window.XMLHttpRequest.prototype.open;
        window.XMLHttpRequest.prototype.open = function (method, url) {
            if (shouldBlock(url)) {
                throw new Error(isAnalyticsEndpoint(url) ? 'Analytics consent required' : 'Blocked local network request');
            }
            return originalOpen.apply(this, arguments);
        };
    }

    if (navigator.sendBeacon) {
        var originalBeacon = navigator.sendBeacon.bind(navigator);
        navigator.sendBeacon = function (url, data) {
            return shouldBlock(url) ? false : originalBeacon(url, data);
        };
    }

    if (typeof window.WebSocket === 'function') {
        var OriginalWebSocket = window.WebSocket;
        window.WebSocket = function (url, protocols) {
            if (GOOGLE_ENABLED && isLocalNetworkRequest(url)) {
                throw new Error('Blocked local network request');
            }
            return protocols === undefined
                ? new OriginalWebSocket(url)
                : new OriginalWebSocket(url, protocols);
        };
        window.WebSocket.prototype = OriginalWebSocket.prototype;
        window.WebSocket.CONNECTING = OriginalWebSocket.CONNECTING;
        window.WebSocket.OPEN = OriginalWebSocket.OPEN;
        window.WebSocket.CLOSING = OriginalWebSocket.CLOSING;
        window.WebSocket.CLOSED = OriginalWebSocket.CLOSED;
    }

    GTM_ID = @json($gtmId);
    GA_ID = @json($gaId);
    GOOGLE_ENABLED = Boolean(GTM_ID || GA_ID);

    function appendGoogleScript(src, id) {
        if (id && document.getElementById(id)) { return; }
        var script = document.createElement('script');
        script.async = true;
        script.src = src;
        if (id) { script.id = id; }
        document.head.appendChild(script);
    }

    function loadGoogleAnalytics() {
        if (googleLoaded || !window.pneHasAnalyticsConsent()) { return; }
        googleLoaded = true;

        @unless($skipMarketingAnalytics ?? false)
        if (GTM_ID) {
            window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            appendGoogleScript(
                'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(GTM_ID),
                'pne-gtm-script'
            );
        }

        if (GA_ID) {
            appendGoogleScript(
                'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID),
                'pne-ga-script'
            );
            window.gtag('config', GA_ID);
        }
        @endunless
    }

    window.pneSetAnalyticsConsent = function (granted) {
        window.gtag('consent', 'update', consentState(granted === true));
        if (granted === true) {
            loadGoogleAnalytics();
            document.dispatchEvent(new CustomEvent('pne:analytics-consent-granted'));
        }
    };

    @if($analyticsConsentGranted)
        window.pneSetAnalyticsConsent(true);
    @endif
})();
</script>
