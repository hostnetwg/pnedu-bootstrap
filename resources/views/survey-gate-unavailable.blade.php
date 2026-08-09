@extends('layouts.survey')

@section('title', 'Ankieta niedostępna — Platforma Nowoczesnej Edukacji')

@push('styles')
<style>
    .survey-unavail {
        min-height: calc(100vh - 72px);
        display: flex;
        align-items: center;
        padding: 2rem 1rem 3rem;
    }
    .survey-unavail-card {
        max-width: 520px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #d9e2ec;
        border-radius: 1.1rem;
        padding: 2rem 1.5rem;
        box-shadow: 0 12px 32px rgba(26, 35, 50, .07);
    }
</style>
@endpush

@section('content')
<div class="survey-unavail">
    <div class="survey-unavail-card">
        <h1 class="h4 fw-bold mb-3">Ankieta jest obecnie niedostępna</h1>
        @if(!empty(trim((string) ($surveyTitle ?? ''))))
            <p class="text-muted mb-3">{{ trim((string) $surveyTitle) }}</p>
        @endif

        @if(empty($active))
            <p class="mb-3">Organizator wyłączył tę ankietę dla tego szkolenia.</p>
        @elseif($opensAt && now()->lt($opensAt))
            <p class="mb-2">Okno ankietowe jeszcze się nie rozpoczęło.</p>
            <p class="text-muted small mb-3">
                Udostępnienie planowane od:
                <strong>{{ $opensAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</strong>
            </p>
        @elseif($closesAt && now()->gte($closesAt))
            <p class="mb-3">Termin wypełnienia tej ankiety już minął.</p>
        @else
            <p class="mb-3">Ankieta nie jest teraz dostępna.</p>
        @endif

        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-house me-1"></i>
            Strona główna pnedu.pl
        </a>
    </div>
</div>
@endsection
