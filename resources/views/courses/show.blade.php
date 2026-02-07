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
    }
    .course-pay-col {
        flex: 0 0 340px;
        max-width: 340px;
    }
    .course-hero-img {
        max-width: 300px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(13,110,253,0.08);
        background: #fff;
        margin-right: 2rem;
        display: none; /* Tymczasowo ukryte do sprawdzenia układu */
    }
    .instructor-photo-header {
        flex-shrink: 0;
        margin-right: 1.5rem;
    }
    .instructor-photo-header-img {
        max-width: 120px;
        width: auto;
        height: auto;
        border-radius: 12px;
    }
    .course-header-row {
        display: flex;
        align-items: flex-start;
        gap: 2rem;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .course-header-content {
        flex: 1;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .course-meta-row {
        display: flex;
        align-items: flex-start;
        gap: 1.5rem;
        margin-top: 0.5rem;
    }
    .course-meta-content {
        flex: 1;
    }
    @media (max-width: 768px) {
        .course-header-row {
            flex-direction: column;
        }
        .course-meta-row {
            flex-direction: column;
        }
        .course-hero-img {
            max-width: 100%;
            margin-right: 0;
        margin-bottom: 1.5rem;
        }
        .instructor-photo-header {
            margin-right: 0;
            margin-bottom: 1rem;
        }
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
        margin-bottom: 1.5rem;
    }
    .course-details-section {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        padding: 2rem 1.5rem;
        margin-bottom: 2.5rem;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
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

    /* Style dla pola email w formularzu zapisu */
    .course-email-input {
        padding: 16px;
        font-size: 1.1rem;
        border: 2px solid #dee2e6;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

    .course-email-input:focus {
        border-color: #0d6efd;
        background-color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        outline: none;
    }

    .course-email-input::placeholder {
        color: #6c757d;
        font-weight: 500;
    }

    /* Style dla checkboxów zgód RODO */
    .course-pay-box .form-check {
        margin-bottom: 0.5rem;
    }

    .course-pay-box .form-check-label {
        font-size: 0.85rem;
        line-height: 1.5;
        color: #495057;
    }

    .course-pay-box .form-check-label a {
        color: #1976d2;
        text-decoration: underline;
    }

    .course-pay-box .form-check-label a:hover {
        color: #0d47a1;
    }

    .course-pay-box .form-check-input:checked {
        background-color: #1976d2;
        border-color: #1976d2;
    }

    .course-pay-box .form-check-input:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funkcja walidacji formularza
    function validateForm(formElement) {
        const rodoCheckbox = formElement.querySelector('input[name="rodo_consent"]');
        if (!rodoCheckbox || !rodoCheckbox.checked) {
            alert('Musisz wyrazić zgodę na przetwarzanie danych osobowych, aby zapisać się na szkolenie.');
            return false;
        }
        return true;
    }

    // Obsługa formularza zapisu (desktop)
    const form = document.getElementById('courseRegistrationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm(form)) return;
            
            const email = document.getElementById('registrationEmail').value;
            const newsletterConsent = document.getElementById('newsletter_consent')?.checked || false;
            alert('Formularz zapisu jest w przygotowaniu.\n\nTwój adres e-mail: ' + email + '\nZgoda na newsletter: ' + (newsletterConsent ? 'Tak' : 'Nie') + '\n\nFunkcjonalność zostanie wkrótce uruchomiona.');
        });
    }

    // Obsługa formularza zapisu (mobile top)
    const formMobile = document.getElementById('courseRegistrationFormMobile');
    if (formMobile) {
        formMobile.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm(formMobile)) return;
            
            const email = document.getElementById('registrationEmailMobile').value;
            const newsletterConsent = document.getElementById('newsletter_consent_mobile')?.checked || false;
            alert('Formularz zapisu jest w przygotowaniu.\n\nTwój adres e-mail: ' + email + '\nZgoda na newsletter: ' + (newsletterConsent ? 'Tak' : 'Nie') + '\n\nFunkcjonalność zostanie wkrótce uruchomiona.');
        });
    }

    // Obsługa formularza zapisu (mobile bottom)
    const formMobileBottom = document.getElementById('courseRegistrationFormMobileBottom');
    if (formMobileBottom) {
        formMobileBottom.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm(formMobileBottom)) return;
            
            const email = document.getElementById('registrationEmailMobileBottom').value;
            const newsletterConsent = document.getElementById('newsletter_consent_mobile_bottom')?.checked || false;
            alert('Formularz zapisu jest w przygotowaniu.\n\nTwój adres e-mail: ' + email + '\nZgoda na newsletter: ' + (newsletterConsent ? 'Tak' : 'Nie') + '\n\nFunkcjonalność zostanie wkrótce uruchomiona.');
        });
    }
});
</script>
@endpush

@section('content')
<div class="container py-5">
    <!-- MOBILE: Płatności pod grafiką, tytułem i datą -->
    <div class="pay-mobile">
        @if(!$course->is_paid)
            <!-- Formularz zapisu dla bezpłatnych szkoleń -->
            <div class="course-pay-box mb-4">
                <h3>Zapisz się na <br>bezpłatne<br>szkolenie online</h3>
                    <form id="courseRegistrationFormMobile" class="text-center">
                        <div class="mb-3">
                            <input type="email" 
                                   class="form-control text-center course-email-input" 
                                   id="registrationEmailMobile" 
                                   name="email" 
                                   placeholder="Twój adres e-mail" 
                                   required>
                        </div>
                        <div class="mb-3 text-start">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rodo_consent" id="rodo_consent_mobile" value="1" required>
                                <label class="form-check-label small" for="rodo_consent_mobile">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu zapisu na szkolenie zgodnie z <a href="{{ route('rodo') }}" target="_blank">klauzulą informacyjną RODO</a> oraz <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="newsletter_consent" id="newsletter_consent_mobile" value="1">
                                <label class="form-check-label small" for="newsletter_consent_mobile">
                                    Wyrażam zgodę na otrzymywanie newslettera z materiałami edukacyjnymi i informacjami o nowych usługach (zgoda dobrowolna, można ją wycofać w każdej chwili).
                                </label>
                            </div>
                        </div>
                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-primary fw-bold" style="padding: 12px; font-size: 1rem;">
                                Zapisz Mnie!
                            </button>
                        </div>
                    </form>
            </div>
        @else
            <!-- Płatne szkolenie - standardowe okienko -->
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
                                    <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span class="text-danger" style="font-size: 1.2rem;">(brutto)</span>
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
                            <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span style="font-size: 1.2rem; color: #1976d2;">(brutto)</span>
                        @endif
                    </div>
                @endif
                <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                    <a href="{{ $course->getPubligoPaymentUrl() ?? route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online PUBLIGO</a>
                    <div class="pay-or-text">lub wypełnij</div>
                    <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
                    @if(!empty($course->id_old))
                        <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-link mt-2" style="font-size: 0.95rem;">Alternatywny formularz zamówienia</a>
                    @endif
                </div>
                <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
            </div>
        @endif
    </div>
    <div class="course-main-row">
        <div class="course-details-col">
            <div class="course-header-row">
                <div class="course-header-content">
                    <div class="course-title">{!! $course->title !!}</div>
                    <div class="course-meta-row">
                        @if($course->instructor && !empty($course->instructor->photo))
                            <div class="instructor-photo-header">
                                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->instructor->photo, '/') }}" 
                                     alt="{{ $course->instructor->full_name }}" 
                                     class="instructor-photo-header-img">
                            </div>
            @endif
                        <div class="course-meta-content">
            <div class="course-meta">
                @php
                            $startDate = \Carbon\Carbon::parse($course->start_date)->locale('pl');
                            $dayOfWeek = $startDate->translatedFormat('l');
                            $formattedDate = $startDate->translatedFormat('j F Y') . ' ' . $startDate->format('H:i');
                            
                    $duration = null;
                    if ($course->end_date) {
                        $end = \Carbon\Carbon::parse($course->end_date);
                                $diff = $startDate->diff($end);
                        $duration = ($diff->h ? $diff->h . 'h ' : '') . ($diff->i ? $diff->i . 'min' : '');
                    }
                @endphp
                        <strong>Data:</strong> {{ $formattedDate }} ({{ $dayOfWeek }})@if($duration) | <strong>Czas trwania:</strong> {{ $duration }}@endif<br>
                        <strong>{{ $course->trainer_title }}:</strong> {{ $course->trainer }}<br>
                        <strong>Forma:</strong> {{ ucfirst($course->type ?? 'online') }} | 
                        @if($course->onlineDetail && !empty($course->onlineDetail->platform))
                            <strong>Platforma:</strong> {{ $course->onlineDetail->platform }}<br>
                        @else
                            <strong>Platforma:</strong> Zoom<br>
                @endif
                        <strong>Dodatkowo:</strong> {{ $course->additional_info ?? 'Materiały do pobrania, zaświadczenie' }}, sesja pytań i odpowiedzi<br>
                        <strong>Dostęp do nagrania:</strong> {{ $course->recording_access ?? '2 miesiące' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                @if(!$course->is_paid)
                    <!-- Formularz zapisu dla bezpłatnych szkoleń -->
                    <div class="course-pay-box mb-4">
                        <h3>Zapisz się na <br>bezpłatne<br>szkolenie online</h3>
                        <form id="courseRegistrationFormMobileBottom" class="text-center">
                            <div class="mb-3">
                                <input type="email" 
                                       class="form-control text-center course-email-input" 
                                       id="registrationEmailMobileBottom" 
                                       name="email" 
                                       placeholder="Twój adres e-mail" 
                                       required>
                            </div>
                            <div class="mb-3 text-start">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="rodo_consent" id="rodo_consent_mobile_bottom" value="1" required>
                                    <label class="form-check-label small" for="rodo_consent_mobile_bottom">
                                        Wyrażam zgodę na przetwarzanie moich danych osobowych w celu zapisu na szkolenie zgodnie z <a href="{{ route('rodo') }}" target="_blank">klauzulą informacyjną RODO</a> oraz <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="newsletter_consent" id="newsletter_consent_mobile_bottom" value="1">
                                    <label class="form-check-label small" for="newsletter_consent_mobile_bottom">
                                        Wyrażam zgodę na otrzymywanie newslettera z materiałami edukacyjnymi i informacjami o nowych usługach (zgoda dobrowolna, można ją wycofać w każdej chwili).
                                    </label>
                                </div>
                            </div>
                            <div class="d-grid mb-2">
                                <button type="submit" class="btn btn-primary fw-bold" style="padding: 12px; font-size: 1rem;">
                                    Zapisz Mnie!
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <!-- Płatne szkolenie - standardowe okienko -->
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
                                            <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span class="text-danger" style="font-size: 1.2rem;">(brutto)</span>
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
                                    <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span style="font-size: 1.2rem; color: #1976d2;">(brutto)</span>
                                @endif
                            </div>
                        @endif
                        <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                            <a href="{{ $course->getPubligoPaymentUrl() ?? route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online PUBLIGO</a>
                            <div class="pay-or-text">lub wypełnij</div>
                            <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
                            @if(!empty($course->id_old))
                                <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-link mt-2" style="font-size: 0.95rem;">Alternatywny formularz zamówienia</a>
                            @endif
                        </div>
                        <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
                    </div>
                @endif
            </div>
        </div>
        <div class="course-pay-col">
            @if(!$course->is_paid)
                <!-- Formularz zapisu dla bezpłatnych szkoleń -->
                <div class="course-pay-box">
                    <h3>Zapisz się na <br>bezpłatne<br>szkolenie online</h3>
                    <form id="courseRegistrationForm" class="text-center">
                        <div class="mb-3">
                            <input type="email" 
                                   class="form-control text-center course-email-input" 
                                   id="registrationEmail" 
                                   name="email" 
                                   placeholder="Twój adres e-mail" 
                                   required>
                        </div>
                        <div class="mb-3 text-start">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rodo_consent" id="rodo_consent" value="1" required>
                                <label class="form-check-label small" for="rodo_consent">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu zapisu na szkolenie zgodnie z <a href="{{ route('rodo') }}" target="_blank">klauzulą informacyjną RODO</a> oraz <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="newsletter_consent" id="newsletter_consent" value="1">
                                <label class="form-check-label small" for="newsletter_consent">
                                    Wyrażam zgodę na otrzymywanie newslettera z materiałami edukacyjnymi i informacjami o nowych usługach (zgoda dobrowolna, można ją wycofać w każdej chwili).
                                </label>
                            </div>
                        </div>
                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-primary fw-bold" style="padding: 12px; font-size: 1rem;">
                                Zapisz Mnie!
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <!-- Płatne szkolenie - standardowe okienko -->
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
                                        <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span class="text-danger" style="font-size: 1.2rem;">(brutto)</span>
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
                                <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span style="font-size: 1.2rem; color: #1976d2;">(brutto)</span>
                            @endif
                        </div>
                    @endif
                    <div class="d-flex flex-column gap-2 mb-3 align-items-center">
                        <a href="{{ $course->getPubligoPaymentUrl() ?? route('payment.online', $course->id) }}" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online PUBLIGO</a>
                        <div class="pay-or-text">lub wypełnij</div>
                        <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
                        @if(!empty($course->id_old))
                            <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-link mt-2" style="font-size: 0.95rem;">Alternatywny formularz zamówienia</a>
                        @endif
                    </div>
                    <div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 