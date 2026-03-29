@extends('layouts.app')

@section('title', 'Formularz zamówienia – ' . $course->title)

@push('styles')
<style>
    .order-form-section {
        background: linear-gradient(135deg, #f4f7fa 60%, #e3e9f3 100%);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(25, 118, 210, 0.07), 0 1.5px 8px 0 rgba(0,0,0,0.04);
        padding: 2.2rem 1.5rem 1.5rem 1.5rem;
        margin-bottom: 2.7rem;
        border: 2px solid #b0bec5;
        transition: box-shadow 0.2s, border-color 0.2s;
    }
    .order-form-section legend {
        display: block;
        float: none;
        font-size: 1.18rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 1.2rem;
        padding: 0 0.7rem;
        width: 100%;
        max-width: 100%;
        border-bottom: none;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(25, 118, 210, 0.04);
    }
    .order-form-section legend + * {
        clear: both;
    }
    .order-form-section .section-heading {
        font-size: 1.18rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 1.2rem;
        padding: 0 0.7rem;
        display: inline-block;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(25, 118, 210, 0.04);
    }
    .order-form-section .recipient-block .form-label {
        font-weight: 400;
    }
    .order-form-section label .text-danger {
        margin-left: 2px;
        font-size: 1.1em;
        vertical-align: middle;
    }
    .order-form-section .form-label {
        font-weight: 500;
    }
    .order-form-section input:not([type="checkbox"]):not([type="radio"]),
    .order-form-section textarea {
        border-radius: 7px;
        border: 1.5px solid #b0bec5;
        background: #fafdff;
        font-size: 1.07rem;
    }
    .order-form-section input:not([type="checkbox"]):not([type="radio"]):focus,
    .order-form-section textarea:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 2px #bbdefb;
        background: #fff;
    }
    /* Radio: Bootstrap domyślnie rysuje białą kropkę na niebieskim pierścieniu — tu całe kółko wypełnione. */
    .order-form-section .form-check-input[type="radio"]:checked {
        background-color: #1976d2;
        border-color: #0d47a1;
        background-image: none;
    }
    /* Checkbox: jednolite niebieskie tło + biały ptaszek (domyślny SVG Bootstrapa) */
    .order-form-section .form-check-input[type="checkbox"]:checked {
        background-color: #1976d2;
        border-color: #0d47a1;
    }
    .order-form-section .form-check-input[type="checkbox"]:not(:checked),
    .order-form-section .form-check-input[type="radio"]:not(:checked) {
        border: 2.5px solid #455a64;
        background-color: #fff;
    }
    .order-form-section .form-info-text {
        font-size: 0.9rem;
        color: #555;
        margin-top: 0.5rem;
        padding: 0.75rem;
        background-color: #f8f9fa;
        border-left: 3px solid #1976d2;
        border-radius: 4px;
        line-height: 1.5;
    }
    .order-form-section .row > .col-md-6 {
        margin-bottom: 0.5rem;
    }
    .order-form-section .form-check-label {
        font-weight: 400;
    }
    .order-form-section .form-check {
        margin-top: 1.2rem;
    }
    .order-form-section .btn-primary {
        font-size: 1.13rem;
        padding: 0.7rem 2.2rem;
        border-radius: 7px;
        font-weight: 600;
    }
    .order-form-section .btn-link {
        font-size: 1rem;
        color: #1976d2;
        text-decoration: underline;
    }
    .order-form-section:not(:last-child) {
        margin-bottom: 0;
    }
    .form-sections-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2.7rem;
    }
    .course-title-section {
        background: linear-gradient(135deg, #e3f2fd 60%, #f3e5f5 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid #bbdefb;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.1);
    }
    .course-title-section .course-topic-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        text-align: center;
    }
    .course-title-section .course-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    .course-title-section .course-title a {
        color: #1976d2;
        text-decoration: none;
        transition: color 0.2s;
    }
    .course-title-section .course-title a:hover {
        color: #0d47a1;
        text-decoration: underline;
    }
    .course-title-section .course-date {
        font-size: 1.1rem;
        color: #424242;
        font-weight: 500;
    }
    .course-title-section .course-trainer {
        font-size: 1rem;
        color: #666;
        font-weight: 500;
        margin-top: 0.3rem;
    }
    .organizer-section {
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .organizer-section .organizer-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 0.75rem;
        text-align: center;
    }
    .organizer-section .organizer-content {
        font-size: 0.95rem;
        color: #333;
        line-height: 1.6;
        text-align: center;
    }
    .organizer-section .organizer-content strong {
        color: #1976d2;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-12">
            <h1 class="mb-4 text-center">Formularz zamówienia</h1>
            <div class="course-title-section text-center">
                <div class="course-topic-label">TEMAT SZKOLENIA</div>
                <div class="course-title"><a href="{{ route('courses.show', $course->id) }}">{!! $course->title !!}</a></div>
                <div class="course-date">Data: {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}</div>
                @if(!empty($course->trainer))
                    <div class="course-trainer">{{ $course->trainer_title }}: {{ $course->trainer }}</div>
                @endif
                @php
                    $priceInfo = $course->getCurrentPrice();
                @endphp
                @if($priceInfo)
                    <div class="mt-3">
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
                <div class="mt-3" style="font-size: 0.95rem; color: #333; line-height: 1.4;">
                    Niepubliczny Ośrodek Doskonalenia Nauczycieli "Platforma Nowoczesnej Edukacji",
                    ul. A. Zamoyskiego 30/14, 09-320 Bieżuń, RSPO: 481379, NIP: 5691238763<br>
                    Kontakt: e-mail: kontakt@nowoczesna-edukacja.pl, tel. 501 654 274
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('payment.order-form.store', $course->id) }}">
                @csrf
                @php
                    $prefillBuyerType = old('buyer_type', $testData['buyer_type'] ?? 'organisation');
                    $prefillPaymentType = old('payment_type', $testData['payment_type'] ?? ($prefillBuyerType === 'person' ? 'online' : 'deferred'));
                @endphp
                <!-- Hidden fields for publigo integration -->
                {{-- Dla kursów z certgen_Publigo użyj id_old, w przeciwnym razie użyj publigo_product_id --}}
                <input type="hidden" name="publigo_product_id" value="{{ ($course->source_id_old === 'certgen_Publigo' && $course->id_old) ? $course->id_old : $course->publigo_product_id }}">
                <input type="hidden" name="publigo_price_id" value="{{ $course->publigo_price_id }}">
                @if(isset($testData['order_ident']))
                    <input type="hidden" name="order_ident" value="{{ $testData['order_ident'] }}">
                @endif
                <div class="form-sections-grid">
                <fieldset class="order-form-section">
                    <legend class="visually-hidden">DANE KONTAKTOWE ZAMAWIAJĄCEGO</legend>
                    <div class="section-heading">DANE KONTAKTOWE ZAMAWIAJĄCEGO</div>
                    <div class="mb-3">
                        <div class="form-label">Zamawiam jako <span class="text-danger">*</span></div>
                        <div class="d-flex flex-column flex-md-row gap-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="buyer_type"
                                    id="buyer_type_organisation"
                                    value="organisation"
                                    {{ $prefillBuyerType === 'organisation' ? 'checked' : '' }}
                                    required
                                >
                                <label class="form-check-label" for="buyer_type_organisation">
                                    Szkoła / Instytucja / Firma
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="buyer_type"
                                    id="buyer_type_person"
                                    value="person"
                                    {{ $prefillBuyerType === 'person' ? 'checked' : '' }}
                                    required
                                >
                                <label class="form-check-label" for="buyer_type_person">
                                    Osoba fizyczna
                                </label>
                            </div>
                        </div>
                        @error('buyer_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Ukryte pole wymagane przez obecną logikę zapisu (na razie) --}}
                    <input type="hidden" name="contact_name" id="contact_name" value="{{ $testData['contact_name'] ?? old('contact_name') }}">

                    <div class="row g-3" id="contact_inputs_row">
                        <div class="col-12 col-md-6" id="contact_col_a">
                            <div id="contact_name_group">
                                <label for="contact_name_display" class="form-label">Nazwa / imię nazwisko <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control @error('contact_name') is-invalid @enderror"
                                    id="contact_name_display"
                                    value="{{ $testData['contact_name'] ?? old('contact_name') }}"
                                    autocomplete="name"
                                >
                                @error('contact_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div id="contact_first_group" style="display:none;">
                                <label for="contact_first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="contact_first_name"
                                    name="contact_first_name"
                                    value="{{ $testData['contact_first_name'] ?? old('contact_first_name') }}"
                                    autocomplete="given-name"
                                >
                            </div>
                        </div>
                        <div class="col-12 col-md-3" id="contact_col_b" style="display:none;">
                            <div id="contact_last_group">
                                <label for="contact_last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="contact_last_name"
                                    name="contact_last_name"
                                    value="{{ $testData['contact_last_name'] ?? old('contact_last_name') }}"
                                    autocomplete="family-name"
                                >
                            </div>
                        </div>
                        <div class="col-12 col-md-3" id="contact_col_c">
                            <label for="contact_phone" class="form-label">Telefon kontaktowy <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone" name="contact_phone" value="{{ $testData['contact_phone'] ?? old('contact_phone') }}" required>
                            @error('contact_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-3" id="contact_col_d">
                            <label for="contact_email" class="form-label">E-mail do przesłania faktury <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email" name="contact_email" value="{{ $testData['contact_email'] ?? old('contact_email') }}" required>
                            @error('contact_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </fieldset>
                <fieldset class="order-form-section">
                    <legend class="visually-hidden">DANE DO FAKTURY</legend>
                    <div class="section-heading">DANE DO FAKTURY</div>
                    <h6 class="mb-3" style="font-weight: 700; font-size: 1.05rem; color: #1976d2;">NABYWCA</h6>
                    <div class="mb-3" id="buyer_person_group" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="buyer_person_first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="buyer_person_first_name"
                                    name="buyer_person_first_name"
                                    value="{{ $testData['buyer_person_first_name'] ?? old('buyer_person_first_name') }}"
                                    autocomplete="given-name"
                                >
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="buyer_person_last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="buyer_person_last_name"
                                    name="buyer_person_last_name"
                                    value="{{ $testData['buyer_person_last_name'] ?? old('buyer_person_last_name') }}"
                                    autocomplete="family-name"
                                >
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-1">
                            <div class="form-check text-end d-inline-flex align-items-center">
                                <label class="form-check-label me-2" for="buyer_person_name_independent">
                                    imię i nazwisko skopiuj z danych zamawiającego
                                </label>
                                <input
                                    class="form-check-input ms-2"
                                    type="checkbox"
                                    id="buyer_person_name_independent"
                                    name="buyer_person_name_independent"
                                    value="1"
                                    {{ old('buyer_person_name_independent', '1') ? 'checked' : '' }}
                                >
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="buyer_name_group">
                        <label for="buyer_name" class="form-label">Nazwa nabywcy <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('buyer_name') is-invalid @enderror" id="buyer_name" name="buyer_name" value="{{ $testData['buyer_name'] ?? old('buyer_name') }}">
                        @error('buyer_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-2">
                            <label for="buyer_postcode" class="form-label">Kod pocztowy <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('buyer_postcode') is-invalid @enderror" id="buyer_postcode" name="buyer_postcode" value="{{ $testData['buyer_postcode'] ?? old('buyer_postcode') }}" required>
                            @error('buyer_postcode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="buyer_city" class="form-label">Poczta / Miasto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('buyer_city') is-invalid @enderror" id="buyer_city" name="buyer_city" value="{{ $testData['buyer_city'] ?? old('buyer_city') }}" required>
                            @error('buyer_city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6" id="buyer_address_group">
                            <label for="buyer_address" class="form-label">Adres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('buyer_address') is-invalid @enderror" id="buyer_address" name="buyer_address" value="{{ $testData['buyer_address'] ?? old('buyer_address') }}" required>
                            @error('buyer_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row g-3 mb-3" id="buyer_nip_gus_row">
                        <div class="col-12 col-md-4">
                            <label for="buyer_nip" class="form-label">NIP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('buyer_nip') is-invalid @enderror" id="buyer_nip" name="buyer_nip" value="{{ $testData['buyer_nip'] ?? old('buyer_nip') }}">
                            @error('buyer_nip')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-8 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="buyer_gus_button">
                                Wpisz NIP i pobierz dane z GUS
                            </button>
                        </div>
                    </div>
                    <div class="recipient-wrapper">
                    <hr class="my-3">
                    <h6 class="mb-3 mt-2" style="font-weight: 700; font-size: 1.05rem; color: #1976d2;">
                        ODBIORCA
                        <span style="font-weight: 400; font-size: 0.9rem; color: #555;">
                            (uzupełnij jeżeli wymagane w Twojej organizacji)
                        </span>
                    </h6>
                    <div class="recipient-block">
                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Nazwa odbiorcy</label>
                            <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" id="recipient_name" name="recipient_name" value="{{ $testData['recipient_name'] ?? old('recipient_name') }}">
                            @error('recipient_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-2">
                                <label for="recipient_postcode" class="form-label">Kod pocztowy</label>
                                <input type="text" class="form-control @error('recipient_postcode') is-invalid @enderror" id="recipient_postcode" name="recipient_postcode" value="{{ $testData['recipient_postcode'] ?? old('recipient_postcode') }}">
                                @error('recipient_postcode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="recipient_city" class="form-label">Poczta / Miasto</label>
                                <input type="text" class="form-control @error('recipient_city') is-invalid @enderror" id="recipient_city" name="recipient_city" value="{{ $testData['recipient_city'] ?? old('recipient_city') }}">
                                @error('recipient_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="recipient_address" class="form-label">Adres</label>
                                <input type="text" class="form-control @error('recipient_address') is-invalid @enderror" id="recipient_address" name="recipient_address" value="{{ $testData['recipient_address'] ?? old('recipient_address') }}">
                                @error('recipient_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row g-3 mb-3" id="recipient_nip_gus_row">
                            <div class="col-12 col-md-4">
                                <label for="recipient_nip" class="form-label">NIP</label>
                                <input type="text" class="form-control @error('recipient_nip') is-invalid @enderror" id="recipient_nip" name="recipient_nip" value="{{ $testData['recipient_nip'] ?? old('recipient_nip') }}">
                                @error('recipient_nip')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-8 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="recipient_gus_button">
                                    Wpisz NIP i pobierz dane z GUS
                                </button>
                            </div>
                        </div>
                    </div>
                </fieldset>
                <fieldset class="order-form-section">
                    <legend class="visually-hidden">DANE UCZESTNIKA SZKOLENIA</legend>
                    <div class="section-heading">DANE UCZESTNIKA SZKOLENIA</div>
                    <div class="form-info-text mt-2">
                        Na poniższe dane zostaną przesłane dane dostępowe do szkolenia oraz wystawione i przesłane zaświadczenie.
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label for="participant_first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('participant_first_name') is-invalid @enderror" id="participant_first_name" name="participant_first_name" value="{{ $testData['participant_first_name'] ?? old('participant_first_name') }}" required>
                            @error('participant_first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="participant_last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('participant_last_name') is-invalid @enderror" id="participant_last_name" name="participant_last_name" value="{{ $testData['participant_last_name'] ?? old('participant_last_name') }}" required>
                            @error('participant_last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="participant_email" class="form-label">
                                E-mail uczestnika <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control @error('participant_email') is-invalid @enderror" id="participant_email" name="participant_email" value="{{ $testData['participant_email'] ?? old('participant_email', auth()->check() ? auth()->user()->email : '') }}" required autocomplete="email">
                            @error('participant_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-1 mb-2 d-flex justify-content-end" id="participant_copy_wrapper" style="display:none;">
                        <div class="form-check text-end d-inline-flex align-items-center">
                            <label class="form-check-label me-2" for="participant_copy_from_contact">
                                skopiuj z danych zamawiającego
                            </label>
                            <input
                                class="form-check-input ms-2"
                                type="checkbox"
                                id="participant_copy_from_contact"
                                name="participant_copy_from_contact"
                                value="1"
                                {{ old('participant_copy_from_contact', '1') ? 'checked' : '' }}
                            >
                        </div>
                    </div>
                    <div class="form-info-text mt-2" id="participant-email-info-text">
                        Zalecane jest podanie indywidualnego adresu e-mail uczestnika, a nie ogólnego adresu placówki – adres e-mail jest powiązany z danym uczestnikiem szkolenia; w przeciwnym razie mogą wystąpić błędy przy generowaniu zaświadczenia. Na podany adres zostanie utworzone konto na platformie z dostępem do zasobów szkolenia; jeśli konto już istnieje, zasoby zostaną do niego dodane.
                    </div>
                </fieldset>
                <div class="order-form-section form-section-full-width">
                    <div class="mb-3">
                        <label for="invoice_notes" class="form-label">Uwagi do faktury (opcjonalnie)</label>
                        <textarea class="form-control @error('invoice_notes') is-invalid @enderror" id="invoice_notes" name="invoice_notes" rows="2">{{ $testData['invoice_notes'] ?? old('invoice_notes') }}</textarea>
                        @error('invoice_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Sposób rozliczenia <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <div class="form-check">
                                <input
                                    class="form-check-input @error('payment_type') is-invalid @enderror"
                                    type="radio"
                                    name="payment_type"
                                    id="payment_type_deferred"
                                    value="deferred"
                                    {{ $prefillPaymentType === 'deferred' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="payment_type_deferred">
                                    Faktura z odroczonym terminem płatności
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                    class="form-check-input @error('payment_type') is-invalid @enderror"
                                    type="radio"
                                    name="payment_type"
                                    id="payment_type_online"
                                    value="online"
                                    {{ $prefillPaymentType === 'online' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="payment_type_online">
                                    Natychmiastowa płatność online
                                </label>
                            </div>
                        </div>
                        @error('payment_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="payment_gateway_group" style="display:none;">
                        <label class="form-label d-block">Bramka płatności <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <div class="form-check">
                                <input
                                    class="form-check-input @error('payment_gateway') is-invalid @enderror"
                                    type="radio"
                                    name="payment_gateway"
                                    id="payment_gateway_payu"
                                    value="payu"
                                    {{ old('payment_gateway', 'payu') === 'payu' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="payment_gateway_payu">
                                    PayU
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                    class="form-check-input @error('payment_gateway') is-invalid @enderror"
                                    type="radio"
                                    name="payment_gateway"
                                    id="payment_gateway_paynow"
                                    value="paynow"
                                    {{ old('payment_gateway') === 'paynow' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="payment_gateway_paynow">
                                    Paynow
                                </label>
                            </div>
                        </div>
                        @error('payment_gateway')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="payment_terms_group">
                        <label for="payment_terms" class="form-label">Termin płatności (dni) <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            class="form-control @error('payment_terms') is-invalid @enderror"
                            id="payment_terms"
                            name="payment_terms"
                            value="{{ $testData['payment_terms'] ?? old('payment_terms', 14) }}"
                            min="0"
                            max="31"
                            style="width: 8ch; max-width: 80px;"
                        >
                        @error('payment_terms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-3 mt-3 flex-wrap">
                        @if($isTestMode)
                            <button type="button" class="btn btn-outline-secondary" id="fill-test-data-btn" title="Wypełnij formularz danymi testowymi (tylko w środowisku deweloperskim)">
                                Wypełnij dane testowe
                            </button>
                        @endif
                        <button type="submit" class="btn btn-primary flex-fill">Wyślij zamówienie</button>
                        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-link flex-fill">Powrót do szczegółów szkolenia</a>
                    </div>
                </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    // UI: przełączanie pól dla "Zamawiam jako"
    var buyerOrg = document.getElementById('buyer_type_organisation');
    var buyerPerson = document.getElementById('buyer_type_person');
    var groupName = document.getElementById('contact_name_group');
    var groupFirst = document.getElementById('contact_first_group');
    var groupLast = document.getElementById('contact_last_group');
    var inputHiddenName = document.getElementById('contact_name');
    var inputNameDisplay = document.getElementById('contact_name_display');
    var inputFirst = document.getElementById('contact_first_name');
    var inputLast = document.getElementById('contact_last_name');
    var contactColA = document.getElementById('contact_col_a');
    var contactColB = document.getElementById('contact_col_b');
    var contactColC = document.getElementById('contact_col_c');
    var contactColD = document.getElementById('contact_col_d');
    var buyerNameGroup = document.getElementById('buyer_name_group');
    var buyerNipGusRow = document.getElementById('buyer_nip_gus_row');
    var buyerNipGroup = buyerNipGusRow;
    var buyerNameInput = document.getElementById('buyer_name');
    var buyerNipInput = document.getElementById('buyer_nip');
    var buyerAddressGroup = document.getElementById('buyer_address_group');
    var buyerPersonGroup = document.getElementById('buyer_person_group');
    var buyerPersonFirst = document.getElementById('buyer_person_first_name');
    var buyerPersonLast = document.getElementById('buyer_person_last_name');
    var buyerPersonIndependent = document.getElementById('buyer_person_name_independent');
    var recipientWrapper = document.querySelector('.recipient-wrapper');
    var paymentTypeDeferred = document.getElementById('payment_type_deferred');
    var paymentTypeOnline = document.getElementById('payment_type_online');
    var paymentTermsGroup = document.getElementById('payment_terms_group');
    var paymentTermsInput = document.getElementById('payment_terms');
    var paymentGatewayGroup = document.getElementById('payment_gateway_group');
    var paymentGatewayPayu = document.getElementById('payment_gateway_payu');
    var paymentGatewayPaynow = document.getElementById('payment_gateway_paynow');
    var participantCopyWrapper = document.getElementById('participant_copy_wrapper');
    var participantCopyCheckbox = document.getElementById('participant_copy_from_contact');
    var participantEmail = document.getElementById('participant_email');
    var participantFirst = document.getElementById('participant_first_name');
    var participantLast = document.getElementById('participant_last_name');
    var contactEmail = document.getElementById('contact_email');

    function normalizeSpaces(s) {
        return (s || '').replace(/\s+/g, ' ').trim();
    }

    function syncContactNameHidden() {
        if (!inputHiddenName) return;

        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (isPerson) {
            var full = normalizeSpaces([inputFirst && inputFirst.value, inputLast && inputLast.value].filter(Boolean).join(' '));
            inputHiddenName.value = full;
        } else {
            inputHiddenName.value = normalizeSpaces(inputNameDisplay && inputNameDisplay.value);
        }
    }

    function updateContactFieldsVisibility() {
        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (groupName) groupName.style.display = isPerson ? 'none' : '';
        if (groupFirst) groupFirst.style.display = isPerson ? '' : 'none';
        if (groupLast && contactColB) contactColB.style.display = isPerson ? '' : 'none';

        // wymagania HTML (na razie tylko UI; backend i tak waliduje contact_name)
        if (inputNameDisplay) inputNameDisplay.required = !isPerson;
        if (inputFirst) inputFirst.required = isPerson;
        if (inputLast) inputLast.required = isPerson;

        // Układ kolumn:
        // - Szkoła/Instytucja/Firma: 50% / 25% / 25% (kolumna A = 6, C = 3, D = 3; B ukryta)
        // - Osoba fizyczna: 25% / 25% / 25% / 25% (A=3, B=3, C=3, D=3)
        function setCol(el, mdSize) {
            if (!el) return;
            el.classList.remove('col-md-3', 'col-md-6');
            el.classList.add('col-md-' + mdSize);
        }

        if (isPerson) {
            setCol(contactColA, 3);
            if (contactColB) setCol(contactColB, 3);
            setCol(contactColC, 3);
            setCol(contactColD, 3);
        } else {
            setCol(contactColA, 6);
            setCol(contactColC, 3);
            setCol(contactColD, 3);
        }

        // NABYWCA: osoba fizyczna → bez nazwy i NIP
        if (buyerNameGroup) buyerNameGroup.style.display = isPerson ? 'none' : '';
        if (buyerNipGroup) buyerNipGroup.style.display = isPerson ? 'none' : '';
        if (buyerNipGusRow) buyerNipGusRow.style.display = isPerson ? 'none' : '';
        if (buyerNameInput) buyerNameInput.required = !isPerson;
        if (buyerNipInput) buyerNipInput.required = !isPerson;
        if (buyerAddressGroup) buyerAddressGroup.classList.toggle('mt-3', isPerson);
        if (buyerPersonGroup) buyerPersonGroup.style.display = isPerson ? '' : 'none';
        if (buyerPersonFirst) buyerPersonFirst.required = isPerson;
        if (buyerPersonLast) buyerPersonLast.required = isPerson;
        if (recipientWrapper) recipientWrapper.style.display = isPerson ? 'none' : '';

        // checkbox "kopiuj z danych zamawiającego" w DANE UCZESTNIKA SZKOLENIA tylko dla osoby fizycznej
        if (participantCopyWrapper) {
            if (isPerson) {
                participantCopyWrapper.classList.remove('d-none');
                participantCopyWrapper.classList.add('d-flex');
                participantCopyWrapper.style.display = '';
            } else {
                participantCopyWrapper.classList.remove('d-flex');
                participantCopyWrapper.classList.add('d-none');
                participantCopyWrapper.style.display = 'none';
            }
        }

        // Osoba fizyczna → skopiuj z danych zamawiającego (jeśli checkbox zaznaczony).
        // Szkoła/Instytucja/Firma → nie czyść pól uczestnika (mogą być wypełnione danymi testowymi lub przez użytkownika).
        if (isPerson) {
            copyContactToParticipantIfAllowed();
        }

        // Osoba fizyczna: nie nadpisuj pola "Nazwa nabywcy" (to pole dotyczy instytucji/firmy).
        // Czyścimy je, żeby po przełączeniu z powrotem na instytucję nie zostały przypadkowe dane.
        if (isPerson && buyerNameInput) {
            buyerNameInput.value = '';
        }

        syncContactNameHidden();

        updatePaymentTypeVisibility();
    }

    /**
     * Domyślny sposób rozliczenia tylko przy przełączeniu „Zamawiam jako” (nie przy pierwszym renderze / edycji).
     * Dzięki temu prefill z serwera (np. odroczona płatność + osoba fizyczna) nie jest nadpisywany.
     */
    function setDefaultPaymentTypeForCurrentBuyerType() {
        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (isPerson) {
            if (paymentTypeOnline) paymentTypeOnline.checked = true;
        } else {
            if (paymentTypeDeferred) paymentTypeDeferred.checked = true;
        }
        updatePaymentTypeVisibility();
    }

    // Kopiowanie imienia/nazwiska z DANYCH KONTAKTOWYCH do NABYWCY (dla osoby fizycznej),
    // tylko gdy checkbox "skopiuj imię i nazwisko zamawiającego" JEST zaznaczony.
    function copyContactToBuyerPersonIfAllowed() {
        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (!isPerson) return;
        if (buyerPersonIndependent && !buyerPersonIndependent.checked) return;
        if (!buyerPersonFirst || !buyerPersonLast) return;

        var cf = normalizeSpaces(inputFirst && inputFirst.value);
        var cl = normalizeSpaces(inputLast && inputLast.value);
        buyerPersonFirst.value = cf;
        buyerPersonLast.value = cl;
    }

    // Kopiowanie danych zamawiającego do DANYCH UCZESTNIKA (imię, nazwisko, e-mail),
    // tylko przy osobie fizycznej i zaznaczonym checkboxie w sekcji uczestnika.
    function copyContactToParticipantIfAllowed() {
        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (!isPerson) return;
        if (!participantCopyCheckbox || !participantCopyCheckbox.checked) return;
        if (!participantFirst || !participantLast || !participantEmail) return;

        var cf = normalizeSpaces(inputFirst && inputFirst.value);
        var cl = normalizeSpaces(inputLast && inputLast.value);
        var ce = normalizeSpaces(contactEmail && contactEmail.value);

        if (cf !== '') participantFirst.value = cf;
        if (cl !== '') participantLast.value = cl;
        if (ce !== '') participantEmail.value = ce;
    }

    function updatePaymentTypeVisibility() {
        var isDeferred = !!(paymentTypeDeferred && paymentTypeDeferred.checked);
        var isOnline = !!(paymentTypeOnline && paymentTypeOnline.checked);

        if (paymentTermsGroup) paymentTermsGroup.style.display = isDeferred ? '' : 'none';
        if (paymentTermsInput) paymentTermsInput.required = isDeferred;

        if (paymentGatewayGroup) paymentGatewayGroup.style.display = isOnline ? '' : 'none';
        if (paymentGatewayPayu) paymentGatewayPayu.required = isOnline;
        if (paymentGatewayPaynow) paymentGatewayPaynow.required = isOnline;
    }

    if (buyerOrg) buyerOrg.addEventListener('change', function () {
        updateContactFieldsVisibility();
        setDefaultPaymentTypeForCurrentBuyerType();
        copyContactToBuyerPersonIfAllowed();
        copyContactToParticipantIfAllowed();

        // Dla "Szkoła / Instytucja / Firma" ukryj checkbox i wyłącz kopiowanie danych uczestnika
        if (participantCopyWrapper) {
            participantCopyWrapper.style.display = 'none';
        }
        if (participantCopyCheckbox) {
            participantCopyCheckbox.checked = false;
        }
    });
    if (buyerPerson) buyerPerson.addEventListener('change', function () {
        updateContactFieldsVisibility();
        setDefaultPaymentTypeForCurrentBuyerType();
        copyContactToBuyerPersonIfAllowed();
        copyContactToParticipantIfAllowed();
    });
    if (inputNameDisplay) inputNameDisplay.addEventListener('input', syncContactNameHidden);
    if (inputFirst) inputFirst.addEventListener('input', function() {
        syncContactNameHidden();
        copyContactToBuyerPersonIfAllowed();
        copyContactToParticipantIfAllowed();
    });
    if (inputLast) inputLast.addEventListener('input', function() {
        syncContactNameHidden();
        copyContactToBuyerPersonIfAllowed();
        copyContactToParticipantIfAllowed();
    });
    if (contactEmail) contactEmail.addEventListener('input', function () {
        copyContactToParticipantIfAllowed();
    });
    if (buyerPersonFirst) buyerPersonFirst.addEventListener('input', updateContactFieldsVisibility);
    if (buyerPersonLast) buyerPersonLast.addEventListener('input', updateContactFieldsVisibility);
    if (buyerPersonIndependent) buyerPersonIndependent.addEventListener('change', function() {
        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (!isPerson || !buyerPersonFirst || !buyerPersonLast) {
            updateContactFieldsVisibility();
            return;
        }

        if (this.checked) {
            copyContactToBuyerPersonIfAllowed();
        } else {
            // odznaczone → pola imię/nazwisko w NABYWCA wyczyszczone
            buyerPersonFirst.value = '';
            buyerPersonLast.value = '';
        }
        updateContactFieldsVisibility();
    });

    if (participantCopyCheckbox) participantCopyCheckbox.addEventListener('change', function () {
        var isPerson = !!(buyerPerson && buyerPerson.checked);
        if (!isPerson || !participantFirst || !participantLast || !participantEmail) {
            return;
        }

        if (this.checked) {
            // zaznaczone → kopiujemy aktualne dane zamawiającego
            copyContactToParticipantIfAllowed();
        } else {
            // odznaczone → czyścimy imię, nazwisko oraz e-mail uczestnika
            participantFirst.value = '';
            participantLast.value = '';
            participantEmail.value = '';
        }
    });

    if (paymentTypeDeferred) paymentTypeDeferred.addEventListener('change', updatePaymentTypeVisibility);
    if (paymentTypeOnline) paymentTypeOnline.addEventListener('change', updatePaymentTypeVisibility);

    // Przed wysłaniem formularza – zsynchronizuj contact_name z widocznych pól
    var formEl = document.querySelector('form[action*="order-form"]');
    if (formEl) {
        formEl.addEventListener('submit', function() {
            syncContactNameHidden();
        });
    }

    // Inicjalizacja po załadowaniu
    updateContactFieldsVisibility();
    copyContactToBuyerPersonIfAllowed();
    copyContactToParticipantIfAllowed();

    var lookupPath = {!! json_encode(parse_url(route('courses.participant-lookup'), PHP_URL_PATH)) !!};
    if (!lookupPath) lookupPath = '/courses/participant-lookup-by-email';
    var emailInput = document.getElementById('participant_email');
    var infoEl = document.getElementById('participant-email-info-text');
    if (!emailInput || !infoEl) return;

    function runLookup() {
        var email = (emailInput.value || '').trim();
        if (!email || email.indexOf('@') === -1) {
            infoEl.classList.remove('text-danger');
            return;
        }
        fetch(lookupPath + '?email=' + encodeURIComponent(email), {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function(r) {
                return r.text().then(function(text) {
                    if (!r.ok) return null;
                    try { return JSON.parse(text); } catch (e) { return null; }
                });
            })
            .then(function(data) {
                if (data && data.found === true) {
                    infoEl.classList.add('text-danger');
                } else {
                    infoEl.classList.remove('text-danger');
                }
            })
            .catch(function() {
                infoEl.classList.remove('text-danger');
            });
    }

    var debounceTimer;
    function scheduleLookup() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runLookup, 500);
    }

    emailInput.addEventListener('blur', scheduleLookup);
    emailInput.addEventListener('input', scheduleLookup);
    emailInput.addEventListener('keyup', scheduleLookup);
    emailInput.addEventListener('paste', function() { setTimeout(scheduleLookup, 100); });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if ((emailInput.value || '').trim().indexOf('@') !== -1) runLookup();
            }, 300);
        });
    } else {
        setTimeout(function() {
            if ((emailInput.value || '').trim().indexOf('@') !== -1) runLookup();
        }, 300);
    }

    // Przycisk "Wypełnij dane testowe" (tylko w trybie testowym/local)
    var fillTestDataBtn = document.getElementById('fill-test-data-btn');
    var testDataJson = {!! json_encode($isTestMode ? [
        'buyer_type' => 'organisation',
        'contact_name' => 'Waldemar Grabowski',
        'contact_first_name' => 'Waldemar',
        'contact_last_name' => 'Grabowski',
        'contact_phone' => '501 654 274',
        'contact_email' => 'waldemar.grabowski@zdalna-lekcja.pl',
        'buyer_name' => 'Gmina Bieżuń',
        'buyer_address' => 'ul. Warszawska 5',
        'buyer_postcode' => '09-320',
        'buyer_city' => 'Bieżuń',
        'buyer_nip' => '5110265245',
        'recipient_name' => 'Szkoła Podstawowa im. Andrzeja Zamoyskiego',
        'recipient_address' => 'ul. Andrzeja Zamoyskiego 28',
        'recipient_postcode' => '09-320',
        'recipient_city' => 'Bieżuń',
        'recipient_nip' => '5261040828',
        'buyer_person_first_name' => 'Waldemar',
        'buyer_person_last_name' => 'Grabowski',
        'participant_first_name' => 'Waldemar',
        'participant_last_name' => 'Grabowski',
        'participant_email' => 'waldemar.grabowski@hostnet.pl',
        'invoice_notes' => 'Dane testowe - Waldek',
        'payment_type' => 'deferred',
        'payment_terms' => '14',
        'payment_gateway' => 'payu',
    ] : []) !!};
    if (fillTestDataBtn && Object.keys(testDataJson).length > 0) {
        fillTestDataBtn.addEventListener('click', function() {
            var form = document.querySelector('form[action*="order-form"]');
            if (!form) return;
            Object.keys(testDataJson).forEach(function(key) {
                var val = testDataJson[key];
                var els = form.querySelectorAll('[name="' + key + '"]');
                els.forEach(function(el) {
                    if (el.type === 'radio' || el.type === 'checkbox') {
                        el.checked = (String(val) === el.value || (el.type === 'checkbox' && val));
                    } else {
                        el.value = val || '';
                    }
                });
            });
            if (inputHiddenName) inputHiddenName.value = testDataJson.contact_name || '';
            if (inputNameDisplay) inputNameDisplay.value = testDataJson.contact_name || '';
            if (buyerOrg && testDataJson.buyer_type === 'organisation') buyerOrg.dispatchEvent(new Event('change', { bubbles: true }));
            if (buyerPerson && testDataJson.buyer_type === 'person') buyerPerson.dispatchEvent(new Event('change', { bubbles: true }));
            if (paymentTypeDeferred && testDataJson.payment_type === 'deferred') paymentTypeDeferred.dispatchEvent(new Event('change', { bubbles: true }));
            if (paymentTypeOnline && testDataJson.payment_type === 'online') paymentTypeOnline.dispatchEvent(new Event('change', { bubbles: true }));
            updateContactFieldsVisibility();
            copyContactToBuyerPersonIfAllowed();
            copyContactToParticipantIfAllowed();
            updatePaymentTypeVisibility();
            if (testDataJson.buyer_type === 'organisation' && testDataJson.buyer_name && buyerNameInput) buyerNameInput.value = testDataJson.buyer_name;
        });
    }
})();
</script>
@endsection

