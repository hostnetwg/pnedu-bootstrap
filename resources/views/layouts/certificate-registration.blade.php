<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Rejestracja zaświadczenia – ' . config('app.name'))</title>

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <header class="border-bottom bg-white py-2">
        <div class="container">
            <a href="{{ route('home') }}" class="d-inline-flex align-items-center text-decoration-none text-dark fw-semibold">
                <img src="{{ asset('images/Logo_PNG.svg') }}"
                    alt="Logo {{ config('app.name') }}"
                    width="40"
                    height="40"
                    class="me-2"
                    style="object-fit:contain;">
                <span class="d-none d-sm-inline">{{ config('app.name') }}</span>
            </a>
        </div>
    </header>

    <main class="flex-grow-1">
        @yield('content')
    </main>

    <footer class="border-top bg-white py-3 mt-auto">
        <div class="container text-center text-muted small">
            &copy; {{ date('Y') }} {{ config('app.name') }}
            · <a href="{{ route('polityka-prywatnosci') }}" class="text-muted">Polityka prywatności</a>
            · <a href="{{ route('rodo') }}" class="text-muted">RODO</a>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
