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
        background: #28a745;
        color: #fff;
        border: none;
    }
    .btn-orange:hover, .btn-orange:focus {
        background: #218838;
        color: #fff;
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 4px 16px rgba(40,167,69,0.18);
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
    .pay-mobile {
        display: none;
    }
    .pay-mobile-bottom {
        display: none;
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
        .pay-mobile {
            display: block;
        }
        .pay-mobile-bottom {
            display: block;
        }
        .course-pay-col {
            display: none;
        }
    }

    /* Style dla sekcji z informacjami o prowadzącym - inline w opisie kursu */
    .instructor-section-inline {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        padding: 2rem 1.5rem;
        margin-bottom: 2.5rem;
        border-top: 3px solid #1976d2;
    }

    .instructor-section-title-inline {
        color: #1976d2;
        font-weight: 600;
        font-size: 1.3rem;
        margin-bottom: 1.2rem;
        margin-top: 0;
    }

    .instructor-section-title-inline i {
        color: #1976d2;
    }


    .instructor-photo {
        text-align: left;
        margin-bottom: 1.5rem;
    }

    .instructor-photo-img {
        max-width: 200px;
        width: 100%;
        height: auto;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: 3px solid #fff;
    }

    .instructor-bio-inline {
        line-height: 1.7;
    }

    .instructor-bio-inline h1,
    .instructor-bio-inline h2,
    .instructor-bio-inline h3,
    .instructor-bio-inline h4,
    .instructor-bio-inline h5,
    .instructor-bio-inline h6 {
        color: #1976d2;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .instructor-bio-inline h1:first-child,
    .instructor-bio-inline h2:first-child,
    .instructor-bio-inline h3:first-child,
    .instructor-bio-inline h4:first-child,
    .instructor-bio-inline h5:first-child,
    .instructor-bio-inline h6:first-child {
        margin-top: 0;
    }

    .instructor-bio-inline p {
        margin-bottom: 1.2rem;
        color: #333;
    }

    .instructor-bio-inline ul,
    .instructor-bio-inline ol {
        margin-bottom: 1.2rem;
        padding-left: 1.5rem;
    }

    .instructor-bio-inline li {
        margin-bottom: 0.5rem;
        color: #333;
    }

    .instructor-bio-inline strong {
        color: #1976d2;
        font-weight: 600;
    }

    .instructor-bio-inline em {
        color: #666;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .instructor-photo-img {
            max-width: 150px;
        }
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <!-- MOBILE: Płatności pod grafiką, tytułem i datą -->
    <div class="pay-mobile">
        <div class="course-pay-box mb-4">
            <h3>Wybierz formę płatności i&nbsp;zarezerwuj miejsce!</h3>
            @php
                $priceInfo = $course->getCurrentPrice();
            @endphp
            @if($priceInfo)
                <div class="text-center mb-3">
                    @if($priceInfo['is_promotion'] && $priceInfo['original_price'])
                        <div class="d-flex flex-column align-items-center gap-1">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span class="text-muted text-decoration-line-through" style="font-size: 0.9rem;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</span>
                                <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span>
                            </div>
                            @if($priceInfo['promotion_end'] && $priceInfo['promotion_type'] === 'time_limited')
                                <small style="font-size: 0.85rem; color: #000;">
                                    Promocja trwa do: {{ \Carbon\Carbon::parse($priceInfo['promotion_end'])->format('d.m.Y H:i') }}
                                </small>
                            @endif
                            <small style="font-size: 0.75rem; color: #aaa;">
                                Najniższa cena z ostatnich 30 dni przed obniżką wynosiła: <strong style="color: #aaa;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</strong>
                            </small>
                        </div>
                    @else
                        <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span>
                    @endif
                </div>
            @endif
            <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                <a href="{{ route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online</a>
                <div class="pay-or-text">lub wypełnij</div>
                <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
                @if(!empty($course->id_old))
                    <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-link mt-2" style="font-size: 0.95rem;">Alternatywny formularz zamówienia</a>
                @endif
            </div>
            <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
        </div>
    </div>
    <div class="course-main-row">
        <div class="course-details-col">
            @if(!empty($course->image))
                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" class="course-hero-img" alt="{{ $course->title }}">
            @endif
            <div class="course-title">{{ $course->title }}</div>
            <div class="course-meta">
                <strong>Data:</strong> {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}<br>
                @php
                    $duration = null;
                    if ($course->end_date) {
                        $start = \Carbon\Carbon::parse($course->start_date);
                        $end = \Carbon\Carbon::parse($course->end_date);
                        $diff = $start->diff($end);
                        $duration = ($diff->h ? $diff->h . 'h ' : '') . ($diff->i ? $diff->i . 'min' : '');
                    }
                @endphp
                @if($duration)
                    <strong>Czas trwania:</strong> {{ $duration }}<br>
                @endif
                <strong>{{ $course->trainer_title }}:</strong> {{ $course->trainer }}
            </div>
            <span class="badge bg-success mb-3">Szkolenie online</span>
            <div class="course-details-section mb-4">
                @if(!empty($course->offer_description_html))
                    {!! $course->offer_description_html !!}
                @else
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
                @endif
            </div>
            
            <!-- Sekcja z informacjami o prowadzącym - kontynuacja opisu -->
            @if($course->instructor && !empty($course->instructor->bio_html))
                <div class="instructor-section-inline">
                    <h4 class="instructor-section-title-inline">
                        <i class="fas fa-user-tie me-2"></i>
                        Informacja o {{ $course->trainer_title == 'Prowadzący' ? 'prowadzącym' : ($course->trainer_title == 'Prowadząca' ? 'prowadzącej' : 'trenerze') }}: 
                        <strong>
                            @if(!empty($course->instructor->title))
                                {{ $course->instructor->title }} 
                            @endif
                            {{ $course->instructor->full_name }}
                        </strong>
                    </h4>
                    
                    @if(!empty($course->instructor->photo))
                        <div class="instructor-photo">
                            <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->instructor->photo, '/') }}" 
                                 alt="{{ $course->instructor->full_name }}" 
                                 class="instructor-photo-img">
                        </div>
                    @endif
                    
                    <div class="instructor-bio-inline">
                        {!! $course->instructor->bio_html !!}
                    </div>
                </div>
            @endif
            
            <!-- MOBILE: Płatności na samym dole po opisie -->
            <div class="pay-mobile-bottom">
                <div class="course-pay-box mb-4">
                    <h3>Wybierz formę płatności i&nbsp;zarezerwuj miejsce!</h3>
                    @php
                        $priceInfo = $course->getCurrentPrice();
                    @endphp
                    @if($priceInfo)
                        <div class="text-center mb-3">
                            @if($priceInfo['is_promotion'] && $priceInfo['original_price'])
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="text-muted text-decoration-line-through" style="font-size: 0.9rem;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</span>
                                        <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span>
                                    </div>
                                    @if($priceInfo['promotion_end'] && $priceInfo['promotion_type'] === 'time_limited')
                                        <small style="font-size: 0.85rem; color: #000;">
                                            Promocja trwa do: {{ \Carbon\Carbon::parse($priceInfo['promotion_end'])->format('d.m.Y H:i') }}
                                        </small>
                                    @endif
                                    <small style="font-size: 0.75rem; color: #aaa;">
                                        Najniższa cena z ostatnich 30 dni przed obniżką wynosiła: <strong style="color: #aaa;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</strong>
                                    </small>
                                </div>
                            @else
                                <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span>
                            @endif
                        </div>
                    @endif
                    <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                        <a href="{{ route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online</a>
                        <div class="pay-or-text">lub wypełnij</div>
                        <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
                        @if(!empty($course->id_old))
                            <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-link mt-2" style="font-size: 0.95rem;">Alternatywny formularz zamówienia</a>
                        @endif
                    </div>
                    <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
                </div>
            </div>
        </div>
        <div class="course-pay-col">
            <div class="course-pay-box">
                <h3>Wybierz formę płatności i&nbsp;zarezerwuj miejsce!</h3>
                @php
                    $priceInfo = $course->getCurrentPrice();
                @endphp
                @if($priceInfo)
                    <div class="text-center mb-3">
                        @if($priceInfo['is_promotion'] && $priceInfo['original_price'])
                            <div class="d-flex flex-column align-items-center gap-1">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="text-muted text-decoration-line-through" style="font-size: 0.9rem;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</span>
                                    <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span>
                                </div>
                                @if($priceInfo['promotion_end'] && $priceInfo['promotion_type'] === 'time_limited')
                                    <small style="font-size: 0.85rem; color: #000;">
                                        Promocja trwa do: {{ \Carbon\Carbon::parse($priceInfo['promotion_end'])->format('d.m.Y H:i') }}
                                    </small>
                                @endif
                                <small style="font-size: 0.75rem; color: #aaa;">
                                    Najniższa cena z ostatnich 30 dni przed obniżką wynosiła: <strong style="color: #aaa;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</strong>
                                </small>
                            </div>
                        @else
                            <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span>
                        @endif
                    </div>
                @endif
                <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                    <a href="{{ route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online</a>
                    <div class="pay-or-text">lub wypełnij</div>
                    <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
                    @if(!empty($course->id_old))
                        <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-link mt-2" style="font-size: 0.95rem;">Alternatywny formularz zamówienia</a>
                    @endif
                </div>
                <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
            </div>
        </div>
    </div>
</div>
@endsection 