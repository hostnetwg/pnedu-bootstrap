@extends('layouts.app')

@section('title', $course->title . ' – Szczegóły szkolenia')

@push('styles')
<style>
    .course-main-row {
        display: flex;
        flex-wrap: wrap;
        gap: 2.5rem;
    }
    .course-details-col {
        flex: 1 1 380px;
        min-width: 0;
        max-width: 700px;
    }
    .course-pay-col {
        flex: 0 0 340px;
        max-width: 340px;
    }
    .course-hero-img {
        max-width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(13,110,253,0.08);
        background: #fff;
        margin-bottom: 1.5rem;
    }
    .course-title {
        font-size: 2.1rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 0.5rem;
    }
    .course-meta {
        font-size: 1.1rem;
        color: #555;
        margin-bottom: 1.2rem;
    }
    .course-details-section {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        padding: 2rem 1.5rem;
        margin-bottom: 2.5rem;
    }
    .course-details-section h4 {
        color: #1976d2;
        font-weight: 600;
        margin-bottom: 1.2rem;
    }
    .course-pay-box {
        background: linear-gradient(135deg, #e3f0ff 60%, #f8fbff 100%);
        border-radius: 18px;
        box-shadow: 0 6px 32px 0 rgba(25, 118, 210, 0.10), 0 1.5px 8px 0 rgba(0,0,0,0.04);
        padding: 2.5rem 1.5rem 2rem 1.5rem;
        margin-bottom: 2.5rem;
        text-align: center;
        position: sticky;
        top: 2rem;
        border: 1.5px solid #bbdefb;
        transition: box-shadow 0.2s;
    }
    .course-pay-box:hover {
        box-shadow: 0 10px 40px 0 rgba(25, 118, 210, 0.18), 0 2px 12px 0 rgba(0,0,0,0.07);
    }
    .course-pay-box h3 {
        font-size: 1.35rem;
        font-weight: 800;
        color: #1a237e;
        margin-bottom: 1.5rem;
        letter-spacing: 0.5px;
    }
    .course-pay-box .btn-lg {
        font-size: 1.15rem;
        padding: 0.85rem 2.5rem;
        border-radius: 8px;
        margin: 0.5rem 0 0 0;
        min-width: 220px;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
        transition: background 0.18s, color 0.18s, box-shadow 0.18s, transform 0.18s;
    }
    .btn-primary-custom {
        background: #1976d2;
        color: #fff;
        border: none;
    }
    .btn-primary-custom:hover, .btn-primary-custom:focus {
        background: #0d47a1;
        color: #fff;
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 4px 16px rgba(25, 118, 210, 0.13);
    }
    .btn-orange {
        background: #ff9800;
        color: #fff;
        border: none;
    }
    .btn-orange:hover, .btn-orange:focus {
        background: #ef6c00;
        color: #fff;
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 4px 16px rgba(255,152,0,0.18);
    }
    .course-pay-box .text-muted {
        font-size: 0.98rem;
    }
    .pay-or-text {
        margin: 0.5rem 0 0.5rem 0;
        color: #1976d2;
        font-weight: 600;
        font-size: 1.08rem;
        letter-spacing: 0.2px;
        opacity: 0.95;
    }
    @media (max-width: 991px) {
        .course-main-row {
            flex-direction: column;
        }
        .course-pay-col, .course-details-col {
            max-width: 100%;
        }
        .course-pay-box {
            position: static;
            margin-bottom: 2.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="course-main-row">
        <div class="course-details-col">
            @if(!empty($course->image))
                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" class="course-hero-img" alt="{{ $course->title }}">
            @endif
            <div class="course-title">{{ $course->title }}</div>
            <div class="course-meta">
                <strong>Data:</strong> {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}<br>
                @if($course->end_date)
                    <strong>Koniec:</strong> {{ \Carbon\Carbon::parse($course->end_date)->format('d.m.Y H:i') }}<br>
                @endif
                <strong>Trener:</strong> {{ $course->trainer }}
            </div>
            <span class="badge bg-success mb-3">Szkolenie online</span>
            <div class="course-details-section mb-4">
                <h4>Dlaczego warto wziąć udział?</h4>
                <ul class="mb-3">
                    <li>Praktyczne umiejętności do natychmiastowego wykorzystania w pracy.</li>
                    <li>Materiały szkoleniowe i certyfikat ukończenia.</li>
                    <li>Możliwość zadawania pytań i konsultacji z ekspertem.</li>
                    <li>Nowoczesna, interaktywna forma prowadzenia zajęć.</li>
                </ul>
                <h4>Program szkolenia (przykład)</h4>
                <ol class="mb-3">
                    <li>Wprowadzenie i cele szkolenia</li>
                    <li>Najważniejsze zagadnienia tematyczne</li>
                    <li>Praca warsztatowa i przykłady praktyczne</li>
                    <li>Sesja pytań i odpowiedzi</li>
                </ol>
                <h4>Dla kogo?</h4>
                <p>Szkolenie przeznaczone jest dla nauczycieli, dyrektorów szkół oraz wszystkich osób zainteresowanych nowoczesną edukacją.</p>
            </div>
        </div>
        <div class="course-pay-col">
            <div class="course-pay-box">
                <h3>Wybierz formę płatności i&nbsp;zarezerwuj miejsce!</h3>
                <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                    <a href="{{ route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online</a>
                    <div class="pay-or-text">lub wypełnij</div>
                    <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z odroczonym terminem płatności</a>
                </div>
                <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
            </div>
        </div>
    </div>
</div>
@endsection 