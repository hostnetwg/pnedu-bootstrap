<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @production
        @php
            $gaId = config('services.google_analytics.id');
        @endphp
        @if(!empty($gaId))
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                // Consent Mode v2 (advanced): default deny, update after user choice
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

                gtag('config', @json($gaId), { anonymize_ip: true });
            </script>
        @endif
    @endproduction

    @if(config('seo.block_search_indexing'))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    @endif
    <meta name="description" content="{{ config('seo.default_description') }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap CSS (CDN - niezależne od Vite) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Custom Styles (Vite - opcjonalne, jeśli są zbudowane) -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center fw-bold">
                    <img src="{{ asset('images/Logo_PNG.svg') }}"
                         alt="Logo Platforma Nowoczesnej Edukacji"
                         width="52"
                         height="52"
                         class="me-2"
                         style="object-fit:contain; margin-top: -8px; margin-bottom: -8px;">
                    <span>Platforma&nbsp;Nowoczesnej&nbsp;Edukacji</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">

                    </ul>
                </div>
            </div>
        </nav>        
        <main class="py-4">
            @yield('content')
        </main>
    </div>

    @include('layouts.cookie-consent')

    <!-- Bootstrap JS (CDN - niezależne od Vite) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
