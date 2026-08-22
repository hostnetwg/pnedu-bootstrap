<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dziękujemy — ' . config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Dziękujemy za udział w szkoleniu.')">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('styles')
    <style>
        .post-training-thanks-page {
            background:
                radial-gradient(900px 420px at 15% -10%, #cfe2ff 0%, transparent 55%),
                linear-gradient(180deg, #f5f8fc 0%, #eef2f6 100%);
            min-height: calc(100vh - 72px);
            padding: 2.5rem 1rem 3rem;
            display: flex;
            align-items: center;
        }
        .post-training-thanks-card {
            max-width: 640px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 1.25rem;
            padding: 2.5rem 1.75rem;
            text-align: center;
            box-shadow: 0 16px 40px rgba(26, 35, 50, .08);
        }
        .post-training-thanks-icon {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #198754, #20c997);
            color: #fff;
            font-size: 2rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 12px 28px rgba(25, 135, 84, .28);
        }
        .post-training-thanks-list li {
            padding: .35rem 0;
        }
        .post-training-thanks-card .btn-lg {
            border-radius: .8rem;
            font-weight: 700;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <header class="border-bottom bg-white py-2">
        <div class="container">
            <a href="{{ route('home') }}" class="d-inline-flex align-items-center text-decoration-none text-dark fw-semibold">
                <img src="{{ asset('images/Logo_PNG.svg') }}"
                     alt="Logo {{ config('app.name') }}"
                     width="40"
                     height="40"
                     class="me-2">
                Platforma Nowoczesnej Edukacji
            </a>
        </div>
    </header>

    <main class="flex-grow-1">
        @yield('content')
    </main>

    <footer class="border-top bg-white py-3 mt-auto">
        <div class="container text-center text-muted small">
            &copy; {{ date('Y') }} Platforma Nowoczesnej Edukacji
        </div>
    </footer>
</body>
</html>
