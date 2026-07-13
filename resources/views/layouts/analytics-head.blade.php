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
                // Chrome/Firefox can show a permission prompt ("Apps on device" / Local Network Access)
                // when a page attempts to connect to localhost or private network ranges. Some third-party
                // tags may probe local endpoints; blocking these avoids the prompt without disabling analytics.
                (function () {
                    function isPrivateOrLoopbackHost(hostname) {
                        if (!hostname) { return false; }
                        var h = String(hostname).toLowerCase();
                        if (h === 'localhost') { return true; }
                        if (h.endsWith('.local')) { return true; }
                        // IPv4 private/loopback ranges
                        if (/^127\./.test(h)) { return true; }
                        if (/^10\./.test(h)) { return true; }
                        if (/^192\.168\./.test(h)) { return true; }
                        if (/^172\.(1[6-9]|2\d|3[0-1])\./.test(h)) { return true; }
                        return false;
                    }

                    function shouldBlockUrl(input) {
                        try {
                            // Support Request objects + relative URLs.
                            var urlString = (input && input.url) ? input.url : String(input);
                            var u = new URL(urlString, window.location.href);
                            if (u.protocol !== 'http:' && u.protocol !== 'https:') { return false; }
                            return isPrivateOrLoopbackHost(u.hostname);
                        } catch (e) {
                            return false;
                        }
                    }

                    // fetch()
                    if (typeof window.fetch === 'function') {
                        var _fetch = window.fetch.bind(window);
                        window.fetch = function (input, init) {
                            if (shouldBlockUrl(input)) {
                                return Promise.reject(new Error('Blocked local network request'));
                            }
                            return _fetch(input, init);
                        };
                    }

                    // XMLHttpRequest
                    if (typeof window.XMLHttpRequest === 'function' && window.XMLHttpRequest.prototype) {
                        var _open = window.XMLHttpRequest.prototype.open;
                        window.XMLHttpRequest.prototype.open = function (method, url) {
                            if (shouldBlockUrl(url)) {
                                throw new Error('Blocked local network request');
                            }
                            return _open.apply(this, arguments);
                        };
                    }
                })();
            </script>

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
