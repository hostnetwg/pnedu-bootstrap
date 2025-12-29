{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Platforma Nowoczesnej Edukacji')</title>

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

    {{-- **Tutaj dodajemy meta‐tagi noindex/nofollow** --}}
    @if(app()->environment() !== 'production')
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow">
    @endif    
</head>
<body class="d-flex flex-column min-vh-100">
    @include('layouts.navigation')

    @include('layouts.alerts')

    <main class="flex-grow-1">
        @yield('content')
    </main>
    @include('layouts.footer')
    @include('layouts.cookie-consent')
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>AOS.init({once: true});</script>
    @stack('scripts')
</body>
</html>
