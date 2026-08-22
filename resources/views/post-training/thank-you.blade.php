@extends('layouts.post-training-thanks')

@section('title', 'Dziękujemy za udział w szkoleniu — Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Dziękujemy za udział w szkoleniu. Na pnedu.pl znajdziesz nagranie, materiały i zaświadczenie, gdy będą dostępne.')

@section('content')
<div class="post-training-thanks-page">
    <div class="post-training-thanks-card" data-aos="zoom-in">
        <div class="post-training-thanks-brand mb-3">
            <img src="{{ asset('images/Logo_PNG.svg') }}"
                 alt="Logo Platforma Nowoczesnej Edukacji"
                 width="72"
                 height="72">
            <div class="post-training-thanks-brand-name fw-semibold mt-2">
                Platforma Nowoczesnej Edukacji
            </div>
        </div>

        <h1 class="h4 fw-bold mb-3">Dziękujemy za udział w szkoleniu!</h1>

        @if(!empty($courseTitle))
            <p class="post-training-thanks-course-title fw-semibold mb-2">{{ $courseTitle }}</p>
        @endif

        @if(!empty($instructorLine) || !empty($startDateTimeLine))
            <p class="post-training-thanks-meta text-muted mb-3">
                @if(!empty($startDateTimeLine))
                    Data: {{ $startDateTimeLine }}
                @endif
                @if(!empty($startDateTimeLine) && !empty($instructorLine))
                    <span class="mx-1">|</span>
                @endif
                @if(!empty($instructorLine))
                    {{ $instructorLine }}
                @endif
            </p>
        @endif

        <p class="text-muted mb-3">
            Spotkanie na żywo dobiegło końca. Materiały ze szkolenia — nagranie, pliki i zaświadczenie —
            udostępniamy na koncie uczestnika na <strong>pnedu.pl</strong>, gdy tylko będą gotowe.
        </p>

        <ul class="list-unstyled text-start post-training-thanks-list mb-3 mx-auto" style="max-width: 22rem;">
            <li><i class="bi bi-camera-video text-primary me-2"></i>Nagranie szkolenia</li>
            <li><i class="bi bi-folder2-open text-primary me-2"></i>Materiały szkoleniowe</li>
            <li><i class="bi bi-award text-primary me-2"></i>Zaświadczenie ukończenia</li>
        </ul>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            @if($isAuthenticated)
                <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-grid me-1"></i>
                    Przejdź do Twoich szkoleń
                </a>
            @else
                <a href="{{ $loginUrl }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Zaloguj się na pnedu.pl
                </a>
            @endif
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-house me-1"></i>
                Strona główna
            </a>
        </div>

        @if(!$isAuthenticated)
            <p class="small text-muted mt-3 mb-0">
                Zaloguj się tym samym adresem e-mail, który podałeś/aś przy zapisie na szkolenie.
            </p>
        @endif
    </div>
</div>
@endsection
