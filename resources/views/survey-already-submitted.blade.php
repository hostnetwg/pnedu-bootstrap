@extends('layouts.survey')

@section('title', 'Ankieta już wypełniona — Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Ta ankieta została już wcześniej wypełniona.')

@push('styles')
<style>
    .survey-thanks-page {
        background:
            radial-gradient(900px 420px at 15% -10%, #cfe2ff 0%, transparent 55%),
            linear-gradient(180deg, #f5f8fc 0%, #eef2f6 100%);
        min-height: calc(100vh - 72px);
        padding: 2.5rem 1rem 3rem;
        display: flex;
        align-items: center;
    }
    .survey-thanks-card {
        max-width: 560px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #d9e2ec;
        border-radius: 1.25rem;
        padding: 2.5rem 1.75rem;
        text-align: center;
        box-shadow: 0 16px 40px rgba(26, 35, 50, .08);
    }
    .survey-thanks-icon {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6c757d, #adb5bd);
        color: #fff;
        font-size: 2rem;
        margin-bottom: 1.25rem;
    }
    .survey-thanks-card .btn-primary {
        border-radius: .8rem;
        font-weight: 700;
        padding: .85rem 1.4rem;
    }
</style>
@endpush

@section('content')
<div class="survey-thanks-page">
    <div class="survey-thanks-card" data-aos="zoom-in">
        <div class="survey-thanks-icon" aria-hidden="true">
            <i class="bi bi-check2-all"></i>
        </div>
        <h1 class="h3 fw-bold mb-3">Ankieta już wypełniona</h1>
        <p class="lead text-muted mb-4">
            @if(!empty($isAnonymous))
                Z tej przeglądarki wysłano już odpowiedź na ankietę
                @if(!empty($surveyTitle)) „{{ $surveyTitle }}”@endif.
                Dziękujemy — nie trzeba wypełniać jej ponownie.
            @else
                Dla tego konta / adresu e-mail odpowiedź na ankietę
                @if(!empty($surveyTitle)) „{{ $surveyTitle }}”@endif
                jest już zapisana. Dziękujemy!
            @endif
        </p>
        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-house me-1"></i>
            Przejdź na stronę główną pnedu.pl
        </a>
    </div>
</div>
@endsection
