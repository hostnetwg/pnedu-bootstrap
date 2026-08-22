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
            min-height: 100vh;
            padding: 2rem 1rem 2.5rem;
            display: flex;
            align-items: center;
        }
        .post-training-thanks-card {
            width: 100%;
            max-width: 860px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 1.25rem;
            padding: 2rem 2rem 2.25rem;
            text-align: center;
            box-shadow: 0 16px 40px rgba(26, 35, 50, .08);
        }
        .post-training-thanks-brand img {
            width: 4.5rem;
            height: 4.5rem;
            object-fit: contain;
        }
        .post-training-thanks-brand-name {
            font-size: .875rem;
            letter-spacing: .01em;
            color: #5c6778;
        }
        .post-training-thanks-course-title {
            font-size: 1.125rem;
            line-height: 1.45;
        }
        .post-training-thanks-meta {
            font-size: .8125rem;
            line-height: 1.4;
        }
        .post-training-thanks-list li {
            padding: .3rem 0;
        }
        .post-training-thanks-card .btn-lg {
            border-radius: .8rem;
            font-weight: 700;
        }
        @media (min-width: 768px) {
            .post-training-thanks-card {
                padding: 2.25rem 2.5rem 2.5rem;
            }
            .post-training-thanks-course-title {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
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
