@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>
                @include('dashboard.partials.sidebar-nav')
            </nav>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h2 class="h4 mb-3">Panel użytkownika</h2>
                    <p class="mb-3">
                        Witaj w swoim panelu. Z tego miejsca możesz przejść do listy zapisanych szkoleń i zaświadczeń — wybierz pozycję w menu po lewej (lub użyj linków poniżej).
                    </p>
                    <p class="text-muted mb-3">
                        Pełna lista Twoich szkoleń (z filtrem płatne / bezpłatne) znajduje się w zakładce
                        <a href="{{ route('dashboard.szkolenia') }}" class="fw-semibold text-decoration-none">Moje szkolenia</a>.
                        Materiały z kursów online (wcześniej m.in. na nowoczesna-edukacja.pl) są w zakładce
                        <a href="{{ route('dashboard.online-courses.index') }}" class="fw-semibold text-decoration-none">Kursy online</a> — przy tym samym adresie e-mail co konto.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('dashboard.szkolenia') }}" class="btn btn-primary btn-sm">Moje szkolenia</a>
                        <a href="{{ route('dashboard.online-courses.index') }}" class="btn btn-outline-primary btn-sm">Kursy online</a>
                        <a href="{{ route('dashboard.zaswiadczenia') }}" class="btn btn-outline-primary btn-sm">Zaświadczenia</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
@endpush
