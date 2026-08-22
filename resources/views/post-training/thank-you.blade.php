@extends($isAuthenticated ? 'layouts.app' : 'layouts.post-training-thanks')

@section('title', 'Dziękujemy za udział w szkoleniu — Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Dziękujemy za udział w szkoleniu. Materiały są już na koncie; nagranie i zaświadczenie pojawią się wkrótce.')

@if($isAuthenticated)
@push('styles')
<style>
    .post-training-thanks-page {
        background:
            radial-gradient(900px 420px at 15% -10%, #cfe2ff 0%, transparent 55%),
            linear-gradient(180deg, #f5f8fc 0%, #eef2f6 100%);
        padding: 2rem 1rem 2.5rem;
        display: flex;
        align-items: center;
        min-height: calc(100vh - 180px);
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
@endpush
@endif

@section('content')
<div class="post-training-thanks-page">
    <div class="post-training-thanks-card">
        @unless($isAuthenticated)
            <div class="post-training-thanks-brand mb-3">
                <img src="{{ asset('images/Logo_PNG.svg') }}"
                     alt="Logo Platforma Nowoczesnej Edukacji"
                     width="72"
                     height="72">
                <div class="post-training-thanks-brand-name fw-semibold mt-2">
                    Platforma Nowoczesnej Edukacji
                </div>
            </div>
        @endunless

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
            <strong>Materiały szkoleniowe są już dostępne na Twoim koncie.</strong>
            Nagranie i zaświadczenie pojawią się wkrótce — potrzebujemy chwili, by je przygotować i udostępnić.
            O gotowości poinformujemy Cię osobnym e-mailem.
        </p>

        <ul class="list-unstyled text-start post-training-thanks-list mb-3 mx-auto" style="max-width: 24rem;">
            <li>
                <i class="bi bi-folder2-open text-success me-2"></i>
                Materiały szkoleniowe — <strong>już dostępne</strong>
            </li>
            <li>
                <i class="bi bi-camera-video text-primary me-2"></i>
                Nagranie szkolenia — <span class="text-muted">wkrótce</span>
            </li>
            <li>
                <i class="bi bi-award text-primary me-2"></i>
                Zaświadczenie ukończenia — <span class="text-muted">wkrótce</span>
            </li>
        </ul>

        @if(!empty($surveyUrl))
            <div class="border rounded-3 bg-light px-3 py-3 mb-3 text-start mx-auto" style="max-width: 32rem;">
                <p class="mb-2 fw-semibold">A jeśli masz jeszcze minutę…</p>
                <p class="small text-muted mb-3 mb-sm-2">
                    Będzie nam bardzo miło, jeśli wypełnisz krótką ankietę po szkoleniu
                    @if(!empty($surveyTitle))
                        <span class="text-body">({{ $surveyTitle }})</span>
                    @endif
                    — Twoja opinia pomaga nam robić kolejne spotkania jeszcze lepiej.
                </p>
                <a href="{{ $surveyUrl }}" class="btn btn-outline-primary">
                    <i class="bi bi-clipboard2-check me-1"></i>
                    Wypełnij ankietę
                </a>
            </div>
        @endif

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
