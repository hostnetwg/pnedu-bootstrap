{{-- Czysty layout ankiet: bez menu i stopki pnedu.pl --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ankieta — Platforma Nowoczesnej Edukacji')</title>

    @include('layouts.analytics-head')

    <meta name="robots" content="noindex, nofollow">
    @php
        $seoDesc = trim($__env->yieldContent('meta_description')) ?: 'Ankieta ewaluacyjna po szkoleniu NODN Platforma Nowoczesnej Edukacji.';
    @endphp
    <meta name="description" content="{{ $seoDesc }}">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <style>
        body.survey-layout {
            font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            margin: 0;
            background: #eef2f6;
        }
        .survey-layout-brand {
            display: flex;
            justify-content: center;
            padding: 1.1rem 1rem 0;
        }
        .survey-layout-brand img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        .survey-layout-brand span {
            font-weight: 700;
            font-size: .95rem;
            color: #1a2332;
            line-height: 1.2;
        }
    </style>
    @stack('styles')
</head>
<body class="survey-layout d-flex flex-column">
    @include('layouts.google-tag-manager-body')

    <div class="survey-layout-brand">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/Logo_PNG.svg') }}" alt="Platforma Nowoczesnej Edukacji" width="40" height="40">
            <span>Platforma Nowoczesnej Edukacji</span>
        </div>
    </div>

    <main class="flex-grow-1">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>AOS.init({ once: true, duration: 450 });</script>
    @stack('scripts')
</body>
</html>
