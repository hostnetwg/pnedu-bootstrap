{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <title>@yield('title', 'Platforma Nowoczesnej Edukacji')</title>

    @include('layouts.analytics-head')

    {{-- Meta Pixel tymczasowo wyłączony (test). Przywróć: include layouts.facebook-pixel --}}

    @php
        $seoTitle = trim($__env->yieldContent('title')) ?: 'Platforma Nowoczesnej Edukacji';
        $seoDesc = trim($__env->yieldContent('meta_description')) ?: config('seo.default_description');
        $seoCanonical = \Illuminate\Support\Facades\View::hasSection('canonical')
            ? trim($__env->yieldContent('canonical'))
            : url()->current();
        $seoDefaultNoindex = request()->routeIs(
            'login',
            'register',
            'password.*',
            'verification.*',
            'dashboard*',
            'profile.*',
            'payment.*',
            'orders.*',
            'certificates.*',
            'certificate-registration.*',
            'survey.*',
            'survey.gate.*',
            'post-training.*'
        );
        $seoRobots = \Illuminate\Support\Facades\View::hasSection('robots')
            ? trim($__env->yieldContent('robots'))
            : (config('seo.block_search_indexing')
                ? 'noindex, nofollow'
                : ($seoDefaultNoindex
                    ? 'noindex, follow'
                    : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'));
        $seoImage = trim($__env->yieldContent('og_image')) ?: config('seo.default_og_image');
    @endphp

    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    @yield('pagination_links')

    <meta property="og:locale" content="pl_PL">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:title" content="@yield('og_title', $seoTitle)">
    <meta property="og:description" content="@yield('og_description', $seoDesc)">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @if($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:alt" content="@yield('og_image_alt', $seoTitle)">
    @endif
    @yield('article_meta')

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', $seoTitle)">
    <meta name="twitter:description" content="@yield('twitter_description', $seoDesc)">
    @if($seoImage)
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif

    @include('layouts.partials.global-structured-data', ['seoCanonical' => $seoCanonical])
    @stack('structured-data')

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @include('layouts.google-tag-manager-body')

    @include('layouts.navigation')

    @include('layouts.email-verification-notice')

    @yield('banner')

    @include('layouts.alerts')

    <main class="flex-grow-1">
        @yield('content')
    </main>
    @hasSection('hide_site_footer')
    @else
        @include('layouts.footer')
    @endif
    @include('layouts.cookie-consent')
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>AOS.init({once: true});</script>
    @stack('scripts')
</body>
</html>
