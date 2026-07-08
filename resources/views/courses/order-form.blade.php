@extends('layouts.app')

@section('title', 'Formularz zamówienia – ' . $course->title)

@push('styles')
<style>
    .order-form-section {
        background: linear-gradient(135deg, #e4e9ef 58%, #d5dde8 100%);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.07), 0 1.5px 8px 0 rgba(0,0,0,0.05);
        padding: 2.2rem 1.5rem 1.5rem 1.5rem;
        margin-bottom: 2.7rem;
        border: 2px solid #90a4ae;
        transition: box-shadow 0.2s, border-color 0.2s;
    }
    .order-form-section legend {
        display: block;
        float: none;
        font-size: 1.18rem;
        font-weight: 700;
        color: #0d47a1;
        margin-bottom: 1.2rem;
        padding: 0 0.7rem;
        width: 100%;
        max-width: 100%;
        border-bottom: none;
        background: #dce3ec;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.07);
    }
    .order-form-section legend + * {
        clear: both;
    }
    .order-form-section .section-heading {
        font-size: 1.18rem;
        font-weight: 700;
        color: #0d47a1;
        margin-bottom: 1.2rem;
        padding: 0.35rem 0.85rem;
        display: inline-block;
        background: #dce3ec;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.07);
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
    /* Tylko wiersz z bramkami / pole liczby dni — przesunięcie w prawo; etykiety bez zmiany */
    #payment_gateway_group .payment-gateway-visual-row {
        margin-top: 10px;
        margin-left: 40px;
    }
    #payment_terms_group #payment_terms {
        margin-top: 10px;
        margin-left: 40px;
    }
    .order-form-section .payment-gateway-logo {
        height: 32px;
        width: auto;
        max-width: 150px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .order-form-section .payment-gateway-logo--payu {
        height: 40px;
        max-width: 190px;
    }
    .order-form-section .payment-gateway-fallback {
        line-height: 1.2;
    }
    /* Wybór bramki: tylko grafiki (ukryte radio zachowują wysyłkę jak wcześniej) */
    #payment_gateway_group .payment-gateway-card {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        cursor: pointer;
        border: 2px solid #b0bec5;
        border-radius: 0;
        padding: 0.5rem 0.85rem;
        background: transparent;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        min-height: 3.25rem;
    }
    #payment_gateway_group .payment-gateway-card--payu {
        background: #fff;
    }
    #payment_gateway_group .payment-gateway-card--paynow {
        background: #000;
    }
    #payment_gateway_group .payment-gateway-card--paynow:hover {
        border-color: #9e9e9e;
    }
    #payment_gateway_group .payment-gateway-card--paynow .payment-gateway-fallback {
        color: #fff;
    }
    #payment_gateway_group .payment-gateway-card:hover:not(.payment-gateway-card--paynow) {
        border-color: #90a4ae;
    }
    #payment_gateway_group .payment-gateway-card:has(input:checked) {
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.22);
    }
    #payment_gateway_group .payment-gateway-card:focus-within {
        outline: 2px solid rgba(25, 118, 210, 0.55);
        outline-offset: 2px;
    }
    #payment_gateway_group .payment-gateway-card-inner {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    .order-form-section .payment-gateway-section-title {
        font-size: 1.05rem;
        font-weight: 500;
        margin-bottom: 0.35rem !important;
    }
    .order-form-section .payment-gateway-label-lock {
        color: #1976d2;
        font-size: 1.1em;
        vertical-align: -0.08em;
    }
    .order-form-section .payment-terms-label-icon {
        color: #1976d2;
        font-size: 1.05rem;
        vertical-align: -0.08em;
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
    .course-title-section .course-ended-access-notice {
        font-size: 0.9rem;
        font-weight: 400;
        margin-top: 0.75rem;
        margin-bottom: 0;
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
                @php
                    $courseEnded = $course->end_date
                        && \Carbon\Carbon::parse($course->end_date)->timezone(config('app.timezone'))->isPast();
                    $courseStartFormatted = \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i');
                    $rawHeaderVid = old('price_variant_id', $prefillPriceVariantId ?? $testData['price_variant_id'] ?? null);
                    $headerVariantId = ($rawHeaderVid !== null && $rawHeaderVid !== '') ? (int) $rawHeaderVid : null;

                    $postEndAccessNotice = null;
                    if ($courseEnded) {
                        $unitLabel = static function (int $value, ?string $unit): string {
                            $unit = $unit ?? 'months';
                            $mod10 = $value % 10;
                            $mod100 = $value % 100;
                            $few = $mod10 >= 2 && $mod10 <= 4 && ! ($mod100 >= 12 && $mod100 <= 14);

                            return match ($unit) {
                                'days' => $value === 1 ? 'dzień' : 'dni',
                                'weeks' => $value === 1 ? 'tydzień' : ($few ? 'tygodnie' : 'tygodni'),
                                'years' => $value === 1 ? 'rok' : ($few ? 'lata' : 'lat'),
                                default => $value === 1 ? 'miesiąc' : ($few ? 'miesiące' : 'miesięcy'),
                            };
                        };

                        $buildDurationNotice = static function (?int $value, ?string $unit) use ($unitLabel): ?string {
                            if (! is_int($value) || $value < 1) {
                                return null;
                            }

                            return $value.' '.$unitLabel($value, $unit).' dostępu do nagrania oraz materiałów od daty zakupu';
                        };

                        $settings = \App\Models\PaymentDisplayOption::getForCoursePage();

                        $variant = null;
                        if ($headerVariantId !== null) {
                            $candidate = \App\Models\CoursePriceVariant::query()
                                ->where('course_id', $course->id)
                                ->where('id', $headerVariantId)
                                ->where('is_active', true)
                                ->first();

                            if ($candidate && $candidate->isAvailableForCourseEndState(true)) {
                                $variant = $candidate;
                            }
                        }

                        $variantRule = $variant?->post_end_access_rule;
                        if ($variantRule === 'unlimited') {
                            $postEndAccessNotice = 'Bezterminowy dostęp do nagrania oraz materiałów';
                        } elseif ($variantRule === 'duration') {
                            $postEndAccessNotice = $buildDurationNotice(
                                $variant?->post_end_access_duration_value,
                                $variant?->post_end_access_duration_unit
                            );
                        }

                        if ($postEndAccessNotice === null) {
                            $postEndAccessNotice = $buildDurationNotice(
                                $course->post_end_access_duration_value,
                                $course->post_end_access_duration_unit
                            );
                        }

                        if ($postEndAccessNotice === null) {
                            $postEndAccessNotice = $buildDurationNotice(
                                $settings['default_post_end_access_duration_value'] ?? 2,
                                $settings['default_post_end_access_duration_unit'] ?? 'months'
                            );
                        }
                    }
                @endphp
                @if($postEndAccessNotice)
                    <p class="course-ended-access-notice text-danger mb-0">{{ $postEndAccessNotice }}</p>
                @endif
                <div class="course-date">
                    @if($courseEnded)
                        Szkolenie online odbyło się: {{ $courseStartFormatted }}
                    @else
                        Data szkolenia: {{ $courseStartFormatted }}
                    @endif
                </div>
                @if(!empty($course->trainer))
                    <div class="course-trainer">{{ $course->trainer_title }}: {{ $course->trainer }}</div>
                @endif
                @php
                    $priceInfo = $course->getPriceInfoForOrderFormHeader($headerVariantId);
                @endphp
                @if($priceInfo && $headerVariantId !== null)
                    <div class="mt-2 small text-muted">
                        Wybrany wariant:
                        @if(! empty($priceInfo['variant_name']))
                            <strong class="text-dark">{{ $priceInfo['variant_name'] }}</strong>
                        @else
                            <strong class="text-dark">#{{ $priceInfo['price_variant_id'] }}</strong>
                        @endif
                    </div>
                @endif
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
                    Kontakt: e-mail: kontakt@pnedu.pl, tel. 501 654 274
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

            @include('courses.partials.checkout-resume-banner')

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
                @php
                    $orderIdentForForm = old('order_ident', $testData['order_ident'] ?? '');
                @endphp
                @if($orderIdentForForm !== '')
                    <input type="hidden" name="order_ident" value="{{ $orderIdentForForm }}">
                @endif
                {{-- Wariant wybrany na stronie kursu (?price_variant_id=) lub jedyny aktywny; przy edycji zamówienia z testData --}}
                <input type="hidden" name="price_variant_id" value="{{ old('price_variant_id', $prefillPriceVariantId ?? $testData['price_variant_id'] ?? '') }}">
                {{-- Źródło marketingowe (jak stary ?fb=1134) – przekazywane w fb_source do form_orders --}}
                <input type="hidden" name="fb_source" value="{{ old('fb_source', $testData['fb_source'] ?? ($fbSourceDefault ?? '')) }}">
                <input type="hidden" name="conversion_placement" value="{{ old('conversion_placement', $testData['conversion_placement'] ?? ($conversionPlacementDefault ?? '')) }}">
                <div class="form-sections-grid">
                <fieldset class="order-form-section" data-analytics-section="buyer_data">
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
                <fieldset class="order-form-section" data-analytics-section="buyer_data">
                    <legend class="visually-hidden">DANE DO FAKTURY</legend>
                    <div class="section-heading">DANE DO FAKTURY</div>
                    <h6 class="mb-3" style="font-weight: 700; font-size: 1.05rem; color: #1976d2;">NABYWCA</h6>
                    <div class="row g-3 mb-3" id="buyer_nip_gus_row">
                        <div class="col-12 col-lg-7">
                            <label for="buyer_nip" class="form-label">NIP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('buyer_nip') is-invalid @enderror" id="buyer_nip" name="buyer_nip" value="{{ $testData['buyer_nip'] ?? old('buyer_nip') }}" placeholder="np. 0001234562" inputmode="numeric" autocomplete="off" aria-describedby="buyer_nip_hint">
                                <button type="button" class="btn btn-primary" id="buyer_gus_button">
                                    Pobierz dane z GUS
                                </button>
                            </div>
                            <div class="form-text" id="buyer_nip_hint">Wpisz NIP, kliknij obok, a dane poniżej uzupełnią się automatycznie.</div>
                            @error('buyer_nip')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="small mt-1" id="buyer_gus_message" aria-live="polite"></div>
                        </div>
                    </div>
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
                    <div class="recipient-wrapper" data-analytics-section="recipient_data">
                        <hr class="my-3">
                        <h6 class="mb-3 mt-2" style="font-weight: 700; font-size: 1.05rem; color: #1976d2;">
                            ODBIORCA
                            <span class="text-danger" style="font-weight: 400; font-size: 0.9rem;">
                                (uzupełnij tylko, jeżeli jest to wymagane w Twojej organizacji)
                            </span>
                        </h6>
                        <div class="recipient-block">
                            <div class="row g-3 mb-3" id="recipient_nip_gus_row">
                                <div class="col-12 col-lg-7">
                                    <label for="recipient_nip" class="form-label">NIP</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('recipient_nip') is-invalid @enderror" id="recipient_nip" name="recipient_nip" value="{{ $testData['recipient_nip'] ?? old('recipient_nip') }}" placeholder="np. 0009876544" inputmode="numeric" autocomplete="off" aria-describedby="recipient_nip_hint">
                                        <button type="button" class="btn btn-primary" id="recipient_gus_button">
                                            Pobierz dane z GUS
                                        </button>
                                    </div>
                                    <div class="form-text" id="recipient_nip_hint">Wpisz NIP, kliknij obok, a dane odbiorcy uzupełnią się automatycznie.</div>
                                    @error('recipient_nip')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <div class="small mt-1" id="recipient_gus_message" aria-live="polite"></div>
                                </div>
                            </div>
                            @if(config('order_form.show_recipient_internal_id'))
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-lg-7">
                                    <label for="recipient_internal_id" class="form-label">Identyfikator wewnętrzny (KSeF, opcjonalnie)</label>
                                    <input type="text" class="form-control @error('recipient_internal_id') is-invalid @enderror" id="recipient_internal_id" name="recipient_internal_id" value="{{ $testData['recipient_internal_id'] ?? old('recipient_internal_id') }}" placeholder="np. 00001 lub 1234567890-00001" maxlength="20" inputmode="numeric" autocomplete="off" aria-describedby="recipient_internal_id_hint">
                                    <div class="form-text" id="recipient_internal_id_hint">Dla oddziału lub jednostki wewnętrznej nabywcy — IDWew z Aplikacji Podatnika KSeF (NIP nabywcy + 5 cyfr, np. 1234567890-00001). Możesz wpisać sam suffix (00001).</div>
                                    @error('recipient_internal_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif
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
                        </div>
                    </div>
                </fieldset>
                <fieldset class="order-form-section" data-analytics-section="participants">
                    <legend class="visually-hidden">DANE UCZESTNIKÓW SZKOLENIA</legend>
                    <div class="section-heading">DANE UCZESTNIKÓW SZKOLENIA</div>
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
                <div class="order-form-section form-section-full-width" data-analytics-section="payment_method">
                    <div class="mb-3" data-analytics-section="invoice">
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
                                    data-analytics-cta="select_deferred_invoice"
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
                                    data-analytics-cta="select_online_payment"
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
                        <label class="form-label payment-gateway-section-title d-block mb-0"><i class="bi bi-lock-fill payment-gateway-label-lock me-2" aria-hidden="true"></i>Bramka płatności <span class="text-danger">*</span></label>
                        <div class="payment-gateway-visual-row d-flex flex-column flex-sm-row flex-wrap align-items-stretch gap-3 @error('payment_gateway') is-invalid @enderror">
                            <label class="payment-gateway-card payment-gateway-card--payu" for="payment_gateway_payu">
                                <input
                                    type="radio"
                                    name="payment_gateway"
                                    id="payment_gateway_payu"
                                    value="payu"
                                    class="visually-hidden"
                                    {{ old('payment_gateway', 'payu') === 'payu' ? 'checked' : '' }}
                                >
                                <span class="payment-gateway-card-inner">
                                    <img
                                        src="{{ asset('payu.png') }}"
                                        alt="PayU"
                                        class="payment-gateway-logo payment-gateway-logo--payu"
                                        width="190"
                                        height="40"
                                        decoding="async"
                                        onerror="this.classList.add('d-none'); var n=this.nextElementSibling; if(n) n.classList.remove('d-none');"
                                    >
                                    <span class="payment-gateway-fallback d-none fw-medium">PayU</span>
                                </span>
                            </label>
                            <label class="payment-gateway-card payment-gateway-card--paynow" for="payment_gateway_paynow">
                                <input
                                    type="radio"
                                    name="payment_gateway"
                                    id="payment_gateway_paynow"
                                    value="paynow"
                                    class="visually-hidden"
                                    {{ old('payment_gateway') === 'paynow' ? 'checked' : '' }}
                                >
                                <span class="payment-gateway-card-inner">
                                    <img
                                        src="{{ asset('paynow.png') }}"
                                        alt="Paynow"
                                        class="payment-gateway-logo"
                                        width="150"
                                        height="32"
                                        decoding="async"
                                        onerror="this.classList.add('d-none'); var n=this.nextElementSibling; if(n) n.classList.remove('d-none');"
                                    >
                                    <span class="payment-gateway-fallback d-none fw-medium">Paynow</span>
                                </span>
                            </label>
                        </div>
                        @error('payment_gateway')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="payment_terms_group">
                        <label for="payment_terms" class="form-label"><i class="bi bi-calendar3 payment-terms-label-icon me-2" aria-hidden="true"></i>Termin płatności (dni) <span class="text-danger">*</span></label>
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
                        <button type="submit" class="btn btn-primary flex-fill" id="order-form-submit-btn" data-analytics-cta="submit_order" data-submitting-text="{{ ($prefillPaymentType ?? 'deferred') === 'online' ? 'Przekierowanie do płatności…' : 'Wysyłanie…' }}">{{ ($prefillPaymentType ?? 'deferred') === 'online' ? 'Przejdź do płatności online' : 'Wyślij zamówienie' }}</button>
                        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-link flex-fill" data-analytics-cta="back_to_course">Powrót do szczegółów szkolenia</a>
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
    var buyerPostcodeInput = document.getElementById('buyer_postcode');
    var buyerCityInput = document.getElementById('buyer_city');
    var buyerAddressInput = document.getElementById('buyer_address');
    var buyerGusButton = document.getElementById('buyer_gus_button');
    var buyerGusMessage = document.getElementById('buyer_gus_message');
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
    var orderFormSubmitBtn = document.getElementById('order-form-submit-btn');
    var participantCopyWrapper = document.getElementById('participant_copy_wrapper');
    var participantCopyCheckbox = document.getElementById('participant_copy_from_contact');
    var participantEmail = document.getElementById('participant_email');
    var participantFirst = document.getElementById('participant_first_name');
    var participantLast = document.getElementById('participant_last_name');
    var contactEmail = document.getElementById('contact_email');
    var recipientName = document.getElementById('recipient_name');
    var recipientPostcode = document.getElementById('recipient_postcode');
    var recipientCity = document.getElementById('recipient_city');
    var recipientAddress = document.getElementById('recipient_address');
    var recipientNip = document.getElementById('recipient_nip');
    var recipientNipLabel = document.querySelector('label[for="recipient_nip"]');
    var recipientInternalId = document.getElementById('recipient_internal_id');
    var recipientGusButton = document.getElementById('recipient_gus_button');
    var recipientGusMessage = document.getElementById('recipient_gus_message');

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

        // checkbox "kopiuj z danych zamawiającego" w DANE UCZESTNIKÓW SZKOLENIA tylko dla osoby fizycznej
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

        if (orderFormSubmitBtn) {
            orderFormSubmitBtn.textContent = isOnline ? 'Przejdź do płatności online' : 'Wyślij zamówienie';
        }
    }

    function isRecipientBlockFilled() {
        var fields = [recipientName, recipientPostcode, recipientCity, recipientAddress, recipientNip, recipientInternalId];
        return fields.some(function (el) {
            return el && String(el.value || '').trim() !== '';
        });
    }

    function updateRecipientNipRequired() {
        if (!recipientNip) return;

        var isOrg = !!(buyerOrg && buyerOrg.checked);
        var shouldRequire = isOrg && isRecipientBlockFilled();
        recipientNip.required = shouldRequire;

        if (recipientNipLabel) {
            var base = 'NIP';
            recipientNipLabel.innerHTML = shouldRequire ? (base + ' <span class="text-danger">*</span>') : base;
        }
    }

    function normalizeNipValue(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function setGusMessage(target, message, isError) {
        var el = target === 'recipient' ? recipientGusMessage : buyerGusMessage;
        if (!el) return;

        el.textContent = message || '';
        el.classList.toggle('text-danger', !!isError);
        el.classList.toggle('text-success', !!message && !isError);
    }

    function setGusLoading(target, isLoading) {
        var button = target === 'recipient' ? recipientGusButton : buyerGusButton;
        if (!button) return;

        if (!button.dataset.defaultText) {
            button.dataset.defaultText = button.textContent;
        }

        button.disabled = isLoading;
        button.textContent = isLoading ? 'Pobieranie danych z GUS…' : button.dataset.defaultText;
    }

    function fillFieldsFromGus(target, data) {
        if (!data) return;

        if (target === 'recipient') {
            if (recipientName && data.name) recipientName.value = data.name;
            if (recipientPostcode && data.postcode) recipientPostcode.value = data.postcode;
            if (recipientCity && data.city) recipientCity.value = data.city;
            if (recipientAddress && data.address) recipientAddress.value = data.address;
            if (recipientNip && data.nip) recipientNip.value = data.nip;
            updateRecipientNipRequired();

            return;
        }

        if (buyerNameInput && data.name) buyerNameInput.value = data.name;
        if (buyerPostcodeInput && data.postcode) buyerPostcodeInput.value = data.postcode;
        if (buyerCityInput && data.city) buyerCityInput.value = data.city;
        if (buyerAddressInput && data.address) buyerAddressInput.value = data.address;
        if (buyerNipInput && data.nip) buyerNipInput.value = data.nip;
    }

    function csrfToken() {
        var tokenInput = document.querySelector('form[action*="order-form"] input[name="_token"]');

        return tokenInput ? tokenInput.value : '';
    }

    function lookupGus(target) {
        var nipInput = target === 'recipient' ? recipientNip : buyerNipInput;
        var nip = normalizeNipValue(nipInput && nipInput.value);
        var gusLookupPath = {!! json_encode(parse_url(route('courses.gus-lookup'), PHP_URL_PATH)) !!} || '/courses/gus-lookup-by-nip';

        setGusMessage(target, '', false);

        if (nip.length !== 10) {
            setGusMessage(target, 'Wpisz poprawny NIP składający się z 10 cyfr.', true);
            if (nipInput) nipInput.focus();
            return;
        }

        setGusLoading(target, true);

        fetch(gusLookupPath, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                nip: nip,
                target: target
            })
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return {};
                }).then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || result.data.success !== true) {
                    setGusMessage(target, result.data && result.data.message ? result.data.message : 'Nie udało się pobrać danych z GUS.', true);
                    return;
                }

                fillFieldsFromGus(target, result.data.data);
                setGusMessage(target, 'Dane pobrane z GUS i wpisane do formularza.', false);
            })
            .catch(function () {
                setGusMessage(target, 'Nie udało się połączyć z GUS. Wpisz dane ręcznie albo spróbuj ponownie.', true);
            })
            .finally(function () {
                setGusLoading(target, false);
            });
    }

    if (buyerGusButton) buyerGusButton.addEventListener('click', function () {
        lookupGus('buyer');
    });
    if (recipientGusButton) recipientGusButton.addEventListener('click', function () {
        lookupGus('recipient');
    });

    if (buyerOrg) buyerOrg.addEventListener('change', function () {
        updateContactFieldsVisibility();
        setDefaultPaymentTypeForCurrentBuyerType();
        copyContactToBuyerPersonIfAllowed();
        copyContactToParticipantIfAllowed();
        updateRecipientNipRequired();

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
        updateRecipientNipRequired();
    });

    [recipientName, recipientPostcode, recipientCity, recipientAddress, recipientNip, recipientInternalId].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', updateRecipientNipRequired);
        el.addEventListener('blur', updateRecipientNipRequired);
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
        'buyer_name' => 'Platforma Nowoczesnej Edukacji Waldemar Grabowski',
        'buyer_address' => 'ul. Andrzeja Zamoyskiego 30/14',
        'buyer_postcode' => '09-320',
        'buyer_city' => 'Bieżuń',
        'buyer_nip' => '7392137630',
        'recipient_name' => 'NOWATORNIA Łukasz Grabowski',
        'recipient_address' => 'UL. HANSA CHRISTIANA ANDERSENA 2/230',
        'recipient_postcode' => '01-911',
        'recipient_city' => 'WARSZAWA',
        'recipient_nip' => '1182307502',
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
@include('courses.partials.order-form-submit-guard')
@include('courses.partials.marketing-ga-event', ['course' => $course, 'gaEvent' => 'order_form_view'])

{{-- Etap B2 — lekki, fail-silent JS collector analityki formularza. Ładowany TYLKO tu (nie globalnie).
     Backend (B1/B1a) wymusza tryby i RODO; JS wysyła wyłącznie techniczne klucze z whitelisty. --}}
@if(config('analytics.enabled', true))
    @php
        $analyticsPriceVariantId = old('price_variant_id', $prefillPriceVariantId ?? ($testData['price_variant_id'] ?? null));
        $analyticsPriceVariantId = is_numeric($analyticsPriceVariantId) ? (int) $analyticsPriceVariantId : null;
    @endphp
    <div id="order-form-analytics-config"
        hidden
        data-endpoint="{{ route('analytics.client-events.store') }}"
        data-course-id="{{ (int) $course->id }}"
        data-price-variant-id="{{ $analyticsPriceVariantId !== null ? $analyticsPriceVariantId : '' }}"
        data-max-batch="{{ (int) config('analytics.client_events.max_events_per_batch', 20) }}"></div>
    @include('courses.partials.order-form-client-tracking')
@endif
@endsection

