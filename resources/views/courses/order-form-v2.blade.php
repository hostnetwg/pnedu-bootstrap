@extends('layouts.app')

@section('title', 'Zamów szkolenie – formularz dla szkół i instytucji | ' . strip_tags($course->title))
@section('meta_description', 'Zamów szkolenie online dla szkoły, placówki, firmy lub osoby prywatnej. Wybierz dane do faktury i wygodny sposób płatności.')

@push('styles')
<style>
    .order-v2 { --v2-primary: #14532d; --v2-soft: #f0fdf4; --v2-border: #d1d5db; max-width: 980px; }
    .order-v2__hero { background: linear-gradient(135deg, #f0fdf4, #eff6ff); border: 1px solid #bbf7d0; border-radius: 1rem; }
    .order-v2__offer-summary { background: linear-gradient(135deg, #f0fdf4, #eff6ff); border: 1px solid #bbf7d0; border-radius: 1rem; }
    .order-v2__offer-eyebrow { letter-spacing: .04em; font-size: .7rem; }
    .order-v2__offer-title { font-size: 1.35rem; font-weight: 700; color: #1976d2; line-height: 1.3; }
    .order-v2__offer-title-link { color: inherit; text-decoration: none; }
    .order-v2__offer-title-link:hover,
    .order-v2__offer-title-link:focus { color: #0d47a1; text-decoration: none; }
    .order-v2__offer-price-box { margin-top: .75rem; }
    .order-v2__offer-price-box--mobile { margin-top: .65rem; text-align: center; }
    @media (min-width: 992px) {
        .order-v2__offer-price-box { margin-top: 1.35rem; }
    }
    .order-v2__offer-meta-row { display: flex; align-items: flex-start; gap: 1rem; }
    .order-v2__offer-meta-text { font-size: .8125rem; color: #555; line-height: 1.35; }
    .order-v2__offer-meta-text p { margin-bottom: .2rem; }
    .order-v2__offer-meta-text p:last-child { margin-bottom: 0; }
    .order-v2__offer-meta-text strong { color: #333; font-weight: 700; }
    .order-v2__offer-trainer-photo { display: block; max-width: 88px; width: 100%; height: auto; border-radius: 12px; }
    .order-v2__offer-price { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
    .order-v2__offer-price-label,
    .order-v2__offer-compare,
    .order-v2__offer-variant { line-height: 1.3; }
    .order-v2__offer-course-link { text-decoration: underline; text-underline-offset: 2px; }
    @media (min-width: 768px) {
        .order-v2__offer-trainer-photo { max-width: 110px; }
        .order-v2__offer-meta-text { font-size: .875rem; }
    }
    @media (max-width: 575.98px) {
        .order-v2__offer-meta-row { flex-direction: column; }
    }
    .order-v2__progress { height: .65rem; }
    .order-v2__steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: .35rem; font-size: .75rem; }
    .order-v2__step-label { color: #6b7280; text-align: center; }
    .order-v2__step-label.is-active { color: var(--v2-primary); font-weight: 700; }
    .order-v2__panel { background: #fff; border: 1px solid var(--v2-border); border-radius: 1rem; box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .07); }
    .order-v2__panel h2:focus { outline: none; box-shadow: none; }
    .order-v2__choice { border: 2px solid var(--v2-border); border-radius: .85rem; cursor: pointer; height: 100%; transition: border-color .15s, background-color .15s; }
    .order-v2__choice:has(input:checked) { border-color: var(--v2-primary); background: var(--v2-soft); }
    .order-v2 .form-label, .order-v2 legend { font-weight: 600; }
    .order-v2 .form-control, .order-v2 .form-select { min-height: 46px; }
    .order-v2__required::after { content: " *"; color: #b91c1c; }
    .order-v2__participant-fields--org-profile { margin-top: 1.75rem; }
    .order-v2__summary { background: #f8fafc; border-left: 4px solid var(--v2-primary); }
    .order-v2__actions { position: sticky; bottom: 0; z-index: 5; background: rgba(255,255,255,.96); border-top: 1px solid #e5e7eb; }
    .order-v2 .btn-success { background-color: var(--v2-primary); border-color: var(--v2-primary); }
    @media (min-width: 768px) {
        .order-v2__steps { font-size: .9rem; }
        .order-v2__panel { padding: 2rem !important; }
    }
</style>
@endpush

@section('content')
@php
    use App\Support\OrderFormV2ParticipantDefaults;

    $field = static fn (string $name, mixed $default = '') => old($name, $testData[$name] ?? $default);
    $profile = old('customer_profile', 'school');
    $participantIsContact = $profile === 'person' && (bool) old(
        'participant_is_contact',
        OrderFormV2ParticipantDefaults::isParticipantSameAsContactDefault($profile)
    );
    $hasRecipientFieldData = collect([
        $field('recipient_name'), $field('recipient_nip'), $field('recipient_address'),
    ])->contains(fn ($value) => filled($value));
    if ($errors->any()) {
        $hasOptionalRecipient = (bool) old('has_optional_recipient');
    } elseif ($hasRecipientFieldData) {
        $hasOptionalRecipient = true;
    } else {
        $hasOptionalRecipient = $profile === 'school';
    }
    $paymentType = old('payment_type', $testData['payment_type'] ?? ($profile === 'person' ? 'online' : 'deferred'));
    $rawVariantId = old('price_variant_id', $prefillPriceVariantId ?? ($testData['price_variant_id'] ?? null));
    $priceInfo = $course->getPriceInfoForOrderFormHeader(filled($rawVariantId) ? (int) $rawVariantId : null);
    $contactName = $field('contact_name');
    $contactFirstName = $field('contact_first_name');
    $contactLastName = $field('contact_last_name');
    if ($profile === 'person' && $contactName !== '' && $contactFirstName === '' && $contactLastName === '') {
        $nameParts = preg_split('/\s+/u', trim($contactName), 2) ?: [];
        $contactFirstName = $nameParts[0] ?? '';
        $contactLastName = $nameParts[1] ?? '';
    }
@endphp

<main class="container py-4 py-lg-5 order-v2" id="order-form-v2-main">
    @include('courses.partials.order-form-v2-offer-summary', ['course' => $course, 'priceInfo' => $priceInfo])

    @if($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1" id="order-v2-errors">
            <h2 class="h6">Sprawdź zaznaczone pola:</h2>
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    @include('courses.partials.checkout-resume-banner')

    <div class="mb-4" aria-label="Postęp zamówienia">
        <div class="progress order-v2__progress" role="progressbar" aria-label="Postęp formularza" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1">
            <div class="progress-bar bg-success" id="order-v2-progress" style="width: 25%"></div>
        </div>
        <div class="order-v2__steps mt-2" aria-hidden="true">
            <span class="order-v2__step-label is-active">1. Profil</span>
            <span class="order-v2__step-label">2. Kontakt</span>
            <span class="order-v2__step-label">3. Faktura</span>
            <span class="order-v2__step-label">4. Płatność</span>
        </div>
    </div>

    <form method="POST" action="{{ route('payment.order-form-v2.store', $course->id) }}" id="order-form-v2" novalidate>
        @csrf
        <input type="hidden" name="form_variant" value="v2">
        <input type="hidden" name="buyer_type" id="v2-buyer-type" value="{{ $profile === 'person' ? 'person' : 'organisation' }}">
        <input type="hidden" name="contact_name" id="v2-contact-name" value="{{ $field('contact_name') }}">
        <input type="hidden" name="publigo_product_id" value="{{ ($course->source_id_old === 'certgen_Publigo' && $course->id_old) ? $course->id_old : $course->publigo_product_id }}">
        <input type="hidden" name="publigo_price_id" value="{{ $course->publigo_price_id }}">
        <input type="hidden" name="price_variant_id" value="{{ $rawVariantId }}">
        <input type="hidden" name="fb_source" value="{{ $field('fb_source', $fbSourceDefault ?? '') }}">
        <input type="hidden" name="conversion_placement" value="{{ $field('conversion_placement', $conversionPlacementDefault ?? '') }}">

        <section class="order-v2__panel p-3 mb-4" data-v2-step="1" data-analytics-section="buyer_data" data-analytics-section-v2="contact" aria-labelledby="v2-step-1-title">
            <h2 class="h4 mb-2" id="v2-step-1-title">Kto zamawia szkolenie?</h2>
            <p class="text-muted">Najpierw dopasujemy formularz do sposobu rozliczenia.</p>
            <div class="row g-3">
                @foreach([
                    'school' => ['Szkoła publiczna / JST', 'Dane nabywcy oraz — jeśli wymaga tego faktura — dane odbiorcy. Wpisujesz je tak, jak przyjęte jest w Twojej placówce.', 'bi-building'],
                    'organisation' => ['Placówka niepubliczna / firma', 'Dane nabywcy i — opcjonalnie — odbiorcy na fakturze.', 'bi-briefcase'],
                    'person' => ['Osoba prywatna', 'Proste dane nabywcy i płatność online.', 'bi-person'],
                ] as $value => [$label, $description, $icon])
                    <div class="col-12 col-md-4">
                        <label class="order-v2__choice d-block p-3" for="profile-{{ $value }}">
                            <input class="form-check-input me-2" type="radio" name="customer_profile" id="profile-{{ $value }}" value="{{ $value }}" @checked($profile === $value)>
                            <i class="bi {{ $icon }} me-1" aria-hidden="true"></i>
                            <strong>{{ $label }}</strong>
                            <span class="d-block small text-muted mt-2">{{ $description }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="order-v2__panel p-3 mb-4" data-v2-step="2" data-analytics-section="participants" data-analytics-section-v2="participants" aria-labelledby="v2-step-2-title" hidden>
            <h2 class="h4 mb-2" id="v2-step-2-title">Kontakt i uczestnik</h2>
            <p class="text-muted">Osoba kontaktowa w sprawie zamówienia — na podany e-mail wyślemy fakturę.</p>
            <div class="row g-3" id="v2-contact-inputs-row">
                <div class="col-12 col-md-6" id="v2-contact-col-a">
                    <div id="v2-contact-name-group">
                        <label class="form-label order-v2__required" for="contact_name_display">Nazwa / imię nazwisko zamawiającego</label>
                        <input
                            class="form-control @error('contact_name') is-invalid @enderror"
                            id="contact_name_display"
                            value="{{ $profile === 'person' ? '' : $contactName }}"
                            autocomplete="name"
                        >
                        @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div id="v2-contact-first-group" hidden>
                        <label class="form-label order-v2__required" for="contact_first_name">Imię</label>
                        <input class="form-control" id="contact_first_name" name="contact_first_name" value="{{ $contactFirstName }}" autocomplete="given-name">
                    </div>
                </div>
                <div class="col-12 col-md-6" id="v2-contact-col-b" hidden>
                    <div id="v2-contact-last-group">
                        <label class="form-label order-v2__required" for="contact_last_name">Nazwisko</label>
                        <input class="form-control" id="contact_last_name" name="contact_last_name" value="{{ $contactLastName }}" autocomplete="family-name">
                    </div>
                </div>
                <div class="col-12 col-md-6" id="v2-contact-col-c">
                    <label class="form-label order-v2__required" for="contact_email">E-mail kontaktowy</label>
                    <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email" name="contact_email" value="{{ $field('contact_email') }}" autocomplete="{{ ($isTestMode ?? false) ? 'off' : 'email' }}" required>
                    @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6" id="v2-contact-col-d">
                    <label class="form-label order-v2__required" for="contact_phone">Telefon kontaktowy</label>
                    <input type="tel" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone" name="contact_phone" value="{{ $field('contact_phone') }}" autocomplete="tel" inputmode="tel" required>
                    @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-check form-switch my-4" id="v2-participant-toggle-wrap" @if($profile !== 'person') hidden @endif>
                <input class="form-check-input" type="checkbox" role="switch" id="participant_is_contact" name="participant_is_contact" value="1" @checked($participantIsContact)>
                <label class="form-check-label fw-semibold" for="participant_is_contact">Zamawiający jest równocześnie uczestnikiem szkolenia</label>
                <p class="form-text mb-0 mt-1" id="v2-participant-toggle-hint">Dane uczestnika są kopiowane z kontaktu. Odznacz, jeśli na szkoleniu będzie inna osoba — pola wyczyszczą się, gdy nadal są takie same.</p>
            </div>
            <div id="v2-participant-fields" @class(['order-v2__participant-fields--org-profile' => $profile !== 'person'])>
                <h3 class="h6">Dane uczestnika</h3>
                <p class="text-muted small mb-3">Na te dane zostaną przesłane dane dostępowe do szkolenia oraz zaświadczenie.</p>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label order-v2__required" for="participant_first_name">Imię</label>
                        <input class="form-control" id="participant_first_name" name="participant_first_name" value="{{ $field('participant_first_name') }}" autocomplete="given-name" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label order-v2__required" for="participant_last_name">Nazwisko</label>
                        <input class="form-control" id="participant_last_name" name="participant_last_name" value="{{ $field('participant_last_name') }}" autocomplete="family-name" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label order-v2__required" for="participant_email">E-mail</label>
                        <input type="email" class="form-control" id="participant_email" name="participant_email" value="{{ $field('participant_email') }}" autocomplete="{{ ($isTestMode ?? false) ? 'off' : 'email' }}" required>
                    </div>
                </div>
            </div>
        </section>

        <section class="order-v2__panel p-3 mb-4" data-v2-step="3" data-analytics-section="buyer_data" data-analytics-section-v2="invoice_buyer" aria-labelledby="v2-step-3-title" hidden>
            <h2 class="h4 mb-2" id="v2-step-3-title">Dane do faktury</h2>
            <p class="text-muted" id="v2-invoice-copy">Dane do wystawienia faktury.</p>

            <div id="v2-organisation-invoice">
                <h3 class="h6 text-uppercase text-success" id="v2-buyer-heading">Nabywca</h3>
                <div class="row g-3">
                    <div class="col-12 col-lg-7">
                        <label class="form-label order-v2__required" for="buyer_nip">NIP</label>
                        <div class="input-group">
                            <input class="form-control @error('buyer_nip') is-invalid @enderror" id="buyer_nip" name="buyer_nip" value="{{ $field('buyer_nip') }}" inputmode="numeric" autocomplete="off">
                            <button class="btn btn-outline-success" type="button" data-gus-target="buyer">Pobierz z GUS</button>
                        </div>
                        <div class="form-text" id="buyer-gus-status" aria-live="polite"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label order-v2__required" for="buyer_name">Nazwa</label>
                        <input class="form-control" id="buyer_name" name="buyer_name" value="{{ $field('buyer_name') }}" autocomplete="organization">
                    </div>
                </div>
            </div>

            <div id="v2-person-invoice" hidden>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label order-v2__required" for="buyer_person_first_name">Imię</label>
                        <input class="form-control" id="buyer_person_first_name" name="buyer_person_first_name" value="{{ $field('buyer_person_first_name') }}" autocomplete="given-name">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label order-v2__required" for="buyer_person_last_name">Nazwisko</label>
                        <input class="form-control" id="buyer_person_last_name" name="buyer_person_last_name" value="{{ $field('buyer_person_last_name') }}" autocomplete="family-name">
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-md-3">
                    <label class="form-label order-v2__required" for="buyer_postcode">Kod pocztowy</label>
                    <input class="form-control" id="buyer_postcode" name="buyer_postcode" value="{{ $field('buyer_postcode') }}" autocomplete="postal-code" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label order-v2__required" for="buyer_city">Miejscowość</label>
                    <input class="form-control" id="buyer_city" name="buyer_city" value="{{ $field('buyer_city') }}" autocomplete="address-level2" required>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label order-v2__required" for="buyer_address">Ulica i numer</label>
                    <input class="form-control" id="buyer_address" name="buyer_address" value="{{ $field('buyer_address') }}" autocomplete="street-address" required>
                </div>
            </div>

            <div class="form-check form-switch my-4" id="v2-optional-recipient-toggle">
                <input class="form-check-input" type="checkbox" role="switch" id="has_optional_recipient" name="has_optional_recipient" value="1" @checked($hasOptionalRecipient)>
                <label class="form-check-label fw-semibold" for="has_optional_recipient">Faktura ma mieć innego odbiorcę</label>
            </div>

            <div id="v2-recipient" data-analytics-section="recipient_data" data-analytics-section-v2="invoice_recipient">
                <hr>
                <h3 class="h6 text-uppercase text-success" id="v2-recipient-heading">Odbiorca</h3>
                <div class="alert alert-light border small py-2 mb-3" id="v2-recipient-copy" role="note">
                    Pola odbiorcy są opcjonalne. Uzupełnij je tylko wtedy, gdy na fakturze ma zostać wskazany inny podmiot niż nabywca.
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-7">
                        <label class="form-label" for="recipient_nip">NIP</label>
                        <div class="input-group">
                            <input class="form-control" id="recipient_nip" name="recipient_nip" value="{{ $field('recipient_nip') }}" inputmode="numeric" autocomplete="off">
                            <button class="btn btn-outline-success" type="button" data-gus-target="recipient">Pobierz z GUS</button>
                        </div>
                        <div class="form-text" id="recipient-gus-status" aria-live="polite"></div>
                    </div>
                    @if(config('order_form.show_recipient_internal_id'))
                        <div class="col-12 col-lg-5">
                            <label class="form-label" for="recipient_internal_id">Identyfikator wewnętrzny KSeF</label>
                            <input class="form-control" id="recipient_internal_id" name="recipient_internal_id" value="{{ $field('recipient_internal_id') }}" maxlength="20">
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label" for="recipient_name">Nazwa</label>
                        <input class="form-control" id="recipient_name" name="recipient_name" value="{{ $field('recipient_name') }}" autocomplete="organization">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="recipient_postcode">Kod pocztowy</label>
                        <input class="form-control" id="recipient_postcode" name="recipient_postcode" value="{{ $field('recipient_postcode') }}" autocomplete="postal-code">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="recipient_city">Miejscowość</label>
                        <input class="form-control" id="recipient_city" name="recipient_city" value="{{ $field('recipient_city') }}" autocomplete="address-level2">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="recipient_address">Ulica i numer</label>
                        <input class="form-control" id="recipient_address" name="recipient_address" value="{{ $field('recipient_address') }}" autocomplete="street-address">
                    </div>
                </div>
            </div>
        </section>

        <section class="order-v2__panel p-3 mb-4" data-v2-step="4" data-analytics-section="payment_method" data-analytics-section-v2="payment" aria-labelledby="v2-step-4-title" hidden>
            <h2 class="h4 mb-2" id="v2-step-4-title">Płatność i podsumowanie</h2>
            <fieldset class="mb-4">
                <legend class="h6">Wybierz sposób płatności</legend>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="order-v2__choice d-block p-3" for="payment_type_deferred">
                            <input class="form-check-input me-2" type="radio" name="payment_type" id="payment_type_deferred" value="deferred" data-analytics-cta="select_deferred_invoice" @checked($paymentType === 'deferred')>
                            <strong>Faktura z odroczonym terminem</strong>
                            <span class="d-block small text-muted mt-1">Standardowy wybór dla szkół i organizacji.</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="order-v2__choice d-block p-3" for="payment_type_online">
                            <input class="form-check-input me-2" type="radio" name="payment_type" id="payment_type_online" value="online" data-analytics-cta="select_online_payment" @checked($paymentType === 'online')>
                            <strong>Płatność online</strong>
                            <span class="d-block small text-muted mt-1">Szybkie przekierowanie do bezpiecznej bramki.</span>
                        </label>
                    </div>
                </div>
            </fieldset>
            <div class="row g-3">
                <div class="col-12 col-md-5" id="v2-payment-terms">
                    <label class="form-label order-v2__required" for="payment_terms">Termin płatności (dni)</label>
                    <input type="number" class="form-control @error('payment_terms') is-invalid @enderror" id="payment_terms" name="payment_terms" value="{{ $field('payment_terms', 14) }}" min="0" max="30">
                    <div class="form-text">Od 0 do 30 dni.</div>
                    @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-7" id="v2-payment-gateway" hidden>
                    <label class="form-label order-v2__required">Bramka płatności</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_gateway" id="payment_gateway_payu" value="payu" @checked(old('payment_gateway', 'payu') === 'payu')>
                            <label class="form-check-label" for="payment_gateway_payu">PayU</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_gateway" id="payment_gateway_paynow" value="paynow" @checked(old('payment_gateway') === 'paynow')>
                            <label class="form-check-label" for="payment_gateway_paynow">Paynow</label>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="invoice_notes">Uwagi do faktury (opcjonalnie)</label>
                    <textarea class="form-control" id="invoice_notes" name="invoice_notes" rows="2">{{ $field('invoice_notes') }}</textarea>
                </div>
            </div>
            <div class="order-v2__summary rounded p-3 mt-4" aria-live="polite">
                <h3 class="h6">Podsumowanie</h3>
                <p class="mb-1"><strong>Szkolenie:</strong> {{ strip_tags($course->title) }}</p>
                <p class="mb-1"><strong>Zamawiający:</strong> <span id="v2-review-profile"></span></p>
                <p class="mb-0"><strong>Płatność:</strong> <span id="v2-review-payment"></span></p>
            </div>
        </section>

        <div class="order-v2__actions py-3 d-flex justify-content-between gap-2" data-analytics-section-v2="submit">
            <button type="button" class="btn btn-outline-secondary" id="v2-back" hidden>Wstecz</button>
            <a href="{{ route('courses.show', $course->id) }}" class="btn btn-link text-secondary" id="v2-cancel" data-analytics-cta="back_to_course">Wróć do szkolenia</a>
            @if($isTestMode)
                <button type="button" class="btn btn-outline-secondary" id="v2-fill-test">Wypełnij dane testowe</button>
            @endif
            <button type="button" class="btn btn-success ms-auto px-4" id="v2-next">Dalej</button>
            <button type="submit" class="btn btn-success ms-auto px-4" id="order-form-submit-btn" data-analytics-cta="submit_order" data-submitting-text="Wysyłanie zamówienia…" hidden>Zamawiam i przechodzę dalej</button>
        </div>
    </form>
</main>

<script>
(function () {
    'use strict';
    var form = document.getElementById('order-form-v2');
    if (!form) return;
    var panels = Array.prototype.slice.call(form.querySelectorAll('[data-v2-step]'));
    var current = {{ $errors->any() ? 1 : 1 }};
    var profileInputs = form.querySelectorAll('[name="customer_profile"]');
    var participantToggle = document.getElementById('participant_is_contact');
    var participantToggleWrap = document.getElementById('v2-participant-toggle-wrap');
    var participantFields = document.getElementById('v2-participant-fields');
    var recipientToggle = document.getElementById('has_optional_recipient');
    var orgInvoice = document.getElementById('v2-organisation-invoice');
    var personInvoice = document.getElementById('v2-person-invoice');
    var recipient = document.getElementById('v2-recipient');
    var recipientToggleWrap = document.getElementById('v2-optional-recipient-toggle');
    var csrf = form.querySelector('[name="_token"]').value;
    var testData = @json(($isTestMode ?? false) ? \App\Support\OrderFormTestData::defaults() : []);

    function selectedProfile() {
        var input = form.querySelector('[name="customer_profile"]:checked');
        return input ? input.value : 'school';
    }
    function setEnabled(container, enabled) {
        container.querySelectorAll('input, select, textarea, button').forEach(function (input) {
            input.disabled = !enabled;
        });
    }
    function setRequired(ids, required) {
        ids.forEach(function (id) {
            var input = document.getElementById(id);
            if (input) input.required = required;
        });
    }
    var contactNameDisplay = document.getElementById('contact_name_display');
    var contactNameHidden = document.getElementById('v2-contact-name');
    var contactFirst = document.getElementById('contact_first_name');
    var contactLast = document.getElementById('contact_last_name');
    var contactEmail = document.getElementById('contact_email');
    var contactNameGroup = document.getElementById('v2-contact-name-group');
    var contactFirstGroup = document.getElementById('v2-contact-first-group');
    var contactLastGroup = document.getElementById('v2-contact-last-group');
    var contactColA = document.getElementById('v2-contact-col-a');
    var contactColB = document.getElementById('v2-contact-col-b');
    var contactColC = document.getElementById('v2-contact-col-c');
    var contactColD = document.getElementById('v2-contact-col-d');

    function normalizeSpaces(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    }
    function contactIsPersonProfile() {
        return selectedProfile() === 'person';
    }
    function syncContactNameHidden() {
        if (!contactNameHidden) return;
        if (contactIsPersonProfile()) {
            contactNameHidden.value = normalizeSpaces([
                contactFirst && contactFirst.value,
                contactLast && contactLast.value,
            ].filter(Boolean).join(' '));
            return;
        }
        contactNameHidden.value = normalizeSpaces(contactNameDisplay && contactNameDisplay.value);
    }
    function contactValuesForParticipantCopy() {
        return {
            first: normalizeSpaces(contactFirst && contactFirst.value),
            last: normalizeSpaces(contactLast && contactLast.value),
            email: normalizeSpaces(contactEmail && contactEmail.value),
        };
    }
    function updateContactFieldsVisibility() {
        var isPerson = contactIsPersonProfile();
        if (contactNameGroup) contactNameGroup.hidden = isPerson;
        if (contactFirstGroup) contactFirstGroup.hidden = !isPerson;
        if (contactLastGroup) contactLastGroup.hidden = !isPerson;
        if (contactColB) contactColB.hidden = !isPerson;
        if (contactNameDisplay) contactNameDisplay.required = !isPerson;
        if (contactFirst) contactFirst.required = isPerson;
        if (contactLast) contactLast.required = isPerson;
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
        syncContactNameHidden();
    }
    function syncContact() {
        syncContactNameHidden();
        if (!contactIsPersonProfile() || !participantToggle.checked) return;
        var values = contactValuesForParticipantCopy();
        document.getElementById('participant_first_name').value = values.first;
        document.getElementById('participant_last_name').value = values.last;
        document.getElementById('participant_email').value = values.email;
        document.getElementById('buyer_person_first_name').value = values.first;
        document.getElementById('buyer_person_last_name').value = values.last;
    }
    function participantDefaultForProfile(profile) {
        return profile === 'person';
    }
    function recipientDefaultForProfile(profile) {
        return profile === 'school';
    }
    function applyRecipientDefaultForProfile() {
        if (!recipientToggle || selectedProfile() === 'person') {
            return;
        }
        recipientToggle.checked = recipientDefaultForProfile(selectedProfile());
    }
    function applyParticipantDefaultForProfile() {
        participantToggle.checked = participantDefaultForProfile(selectedProfile());
        syncParticipant();
    }
    function syncProfile() {
        var profile = selectedProfile();
        var person = profile === 'person';
        document.getElementById('v2-buyer-type').value = person ? 'person' : 'organisation';
        orgInvoice.hidden = person;
        personInvoice.hidden = !person;
        setEnabled(orgInvoice, !person);
        setEnabled(personInvoice, person);
        setRequired(['buyer_nip', 'buyer_name'], !person);
        setRequired(['buyer_person_first_name', 'buyer_person_last_name'], person);
        recipientToggleWrap.hidden = person;
        var showRecipient = !person && recipientToggle.checked;
        recipient.hidden = !showRecipient;
        setEnabled(recipient, showRecipient);
        setRequired(['recipient_nip', 'recipient_name', 'recipient_postcode', 'recipient_city', 'recipient_address'], false);
        document.getElementById('v2-invoice-copy').textContent = person
            ? 'Podaj dane potrzebne do wystawienia faktury.'
            : 'Dane do wystawienia faktury.';
        updateContactFieldsVisibility();
        syncParticipant();
        syncPaymentDefault(false);
    }
    function participantMirrorsContact() {
        if (!contactIsPersonProfile()) return false;
        var values = contactValuesForParticipantCopy();
        return ['participant_first_name', 'participant_last_name', 'participant_email'].every(function (id, index) {
            var participant = document.getElementById(id);
            var expected = [values.first, values.last, values.email][index];
            return participant && participant.value.trim() === expected;
        });
    }
    function clearParticipantFields() {
        ['participant_first_name', 'participant_last_name', 'participant_email'].forEach(function (id) {
            var input = document.getElementById(id);
            if (!input) return;
            input.value = '';
            input.classList.remove('is-invalid');
        });
    }
    function syncParticipant() {
        var isPerson = contactIsPersonProfile();
        if (participantToggleWrap) participantToggleWrap.hidden = !isPerson;
        if (participantFields) participantFields.classList.toggle('order-v2__participant-fields--org-profile', !isPerson);
        if (!isPerson) {
            participantToggle.checked = false;
        }
        if (isPerson && !participantToggle.checked && participantMirrorsContact()) {
            clearParticipantFields();
        }
        document.querySelectorAll('#v2-participant-fields input').forEach(function (input) {
            input.readOnly = isPerson && participantToggle.checked;
        });
        syncContact();
    }
    function syncPaymentDefault(forceDefault) {
        var deferred = document.getElementById('payment_type_deferred');
        var online = document.getElementById('payment_type_online');
        if (forceDefault) {
            deferred.checked = selectedProfile() !== 'person';
            online.checked = selectedProfile() === 'person';
        }
        var isDeferred = deferred.checked;
        document.getElementById('v2-payment-terms').hidden = !isDeferred;
        document.getElementById('payment_terms').disabled = !isDeferred;
        document.getElementById('payment_terms').required = isDeferred;
        document.getElementById('v2-payment-gateway').hidden = isDeferred;
        form.querySelectorAll('[name="payment_gateway"]').forEach(function (input) { input.disabled = isDeferred; });
        document.getElementById('v2-review-payment').textContent = isDeferred ? 'faktura z odroczonym terminem' : 'płatność online';
    }
    function updateReview() {
        var labels = {school: 'szkoła publiczna / JST', organisation: 'placówka niepubliczna / firma', person: 'osoba prywatna'};
        document.getElementById('v2-review-profile').textContent = labels[selectedProfile()];
        syncPaymentDefault(false);
    }
    function showStep(step, focusHeading) {
        current = Math.max(1, Math.min(4, step));
        panels.forEach(function (panel) { panel.hidden = Number(panel.dataset.v2Step) !== current; });
        document.getElementById('order-v2-progress').style.width = (current * 25) + '%';
        document.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', current);
        document.querySelectorAll('.order-v2__step-label').forEach(function (label, index) {
            label.classList.toggle('is-active', index + 1 === current);
        });
        document.getElementById('v2-back').hidden = current === 1;
        document.getElementById('v2-cancel').hidden = current !== 1;
        document.getElementById('v2-next').hidden = current === 4;
        document.getElementById('order-form-submit-btn').hidden = current !== 4;
        if (current === 4) updateReview();
        if (focusHeading !== false) {
            var heading = panels[current - 1].querySelector('h2');
            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus({preventScroll: true});
            }
        }
        window.scrollTo({top: Math.max(0, form.offsetTop - 120), behavior: 'smooth'});
    }
    function validateCurrent() {
        syncContact();
        var invalid = Array.prototype.find.call(panels[current - 1].querySelectorAll('input, select, textarea'), function (input) {
            return !input.disabled && !input.checkValidity();
        });
        if (invalid) {
            invalid.classList.add('is-invalid');
            invalid.reportValidity();
            invalid.focus();
            return false;
        }
        return true;
    }
    function gusLookup(target, button) {
        var nip = document.getElementById(target + '_nip');
        var status = document.getElementById(target + '-gus-status');
        if (!nip.value.trim()) {
            nip.setCustomValidity('Wpisz NIP przed pobraniem danych.');
            nip.reportValidity();
            nip.setCustomValidity('');
            return;
        }
        button.disabled = true;
        status.textContent = 'Pobieranie danych…';
        fetch(@json(route('courses.gus-lookup')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({nip: nip.value, target: target})
        }).then(function (response) {
            return response.json().then(function (body) { return {ok: response.ok, body: body}; });
        }).then(function (result) {
            if (!result.ok || !result.body.success) throw new Error(result.body.message || 'Nie znaleziono danych.');
            var data = result.body.data;
            ['name', 'postcode', 'city', 'address', 'nip'].forEach(function (key) {
                var input = document.getElementById(target + '_' + key);
                if (input && data[key]) input.value = data[key];
            });
            status.textContent = 'Dane pobrane z GUS.';
        }).catch(function (error) {
            status.textContent = error.message || 'Nie udało się pobrać danych. Wpisz je ręcznie.';
        }).finally(function () {
            button.disabled = false;
        });
    }

    profileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            applyRecipientDefaultForProfile();
            syncProfile();
            applyParticipantDefaultForProfile();
            syncPaymentDefault(true);
        });
    });
    ['contact_first_name', 'contact_last_name', 'contact_email'].forEach(function (id) {
        var input = document.getElementById(id);
        if (input) input.addEventListener('input', syncContact);
    });
    if (contactNameDisplay) contactNameDisplay.addEventListener('input', syncContact);
    participantToggle.addEventListener('change', syncParticipant);
    recipientToggle.addEventListener('change', syncProfile);
    form.querySelectorAll('[name="payment_type"]').forEach(function (input) { input.addEventListener('change', function () { syncPaymentDefault(false); }); });
    form.querySelectorAll('[data-gus-target]').forEach(function (button) {
        button.addEventListener('click', function () { gusLookup(button.dataset.gusTarget, button); });
    });
    form.querySelectorAll('input, select, textarea').forEach(function (input) {
        input.addEventListener('input', function () { input.classList.remove('is-invalid'); });
    });
    document.getElementById('v2-next').addEventListener('click', function () {
        if (validateCurrent()) showStep(current + 1);
    });
    document.getElementById('v2-back').addEventListener('click', function () { showStep(current - 1); });
    var fillTestButton = document.getElementById('v2-fill-test');
    function applyTestDataToForm() {
        if (!testData || Object.keys(testData).length === 0) {
            return;
        }
        Object.keys(testData).forEach(function (name) {
            if (['_token', 'form_variant'].indexOf(name) !== -1) {
                return;
            }
            form.querySelectorAll('[name="' + name + '"]').forEach(function (input) {
                if (input.type === 'radio') {
                    input.checked = String(testData[name]) === input.value;
                    return;
                }
                if (input.type === 'checkbox') {
                    input.checked = testData[name] === true
                        || testData[name] === 1
                        || testData[name] === '1';
                    return;
                }
                input.value = testData[name] == null ? '' : testData[name];
            });
        });
        var profile = testData.customer_profile
            || (testData.buyer_type === 'person' ? 'person' : 'school');
        var profileRadio = form.querySelector('[name="customer_profile"][value="' + profile + '"]');
        if (profileRadio) {
            profileRadio.checked = true;
        }
        if (recipientToggle) {
            if (profile === 'school') {
                recipientToggle.checked = true;
            } else if (profile === 'organisation') {
                recipientToggle.checked = !!(testData.recipient_name || testData.recipient_nip || testData.recipient_address);
            } else {
                recipientToggle.checked = false;
            }
        }
        if (contactNameDisplay) {
            contactNameDisplay.value = testData.contact_name || '';
        }
        if (contactNameHidden) {
            contactNameHidden.value = testData.contact_name || '';
        }
        syncProfile();
        applyParticipantDefaultForProfile();
        if (testData.payment_type === 'online') {
            var online = document.getElementById('payment_type_online');
            if (online) online.checked = true;
        } else if (testData.payment_type === 'deferred') {
            var deferred = document.getElementById('payment_type_deferred');
            if (deferred) deferred.checked = true;
        }
        syncPaymentDefault(false);
        syncParticipant();
        updateReview();
    }
    if (fillTestButton && Object.keys(testData).length > 0) {
        fillTestButton.addEventListener('click', applyTestDataToForm);
    }
    form.addEventListener('submit', function (event) {
        syncContactNameHidden();
        syncContact();
        if (!form.checkValidity()) {
            event.preventDefault();
            var invalid = form.querySelector(':invalid');
            var panel = invalid ? invalid.closest('[data-v2-step]') : null;
            if (panel) showStep(Number(panel.dataset.v2Step));
            if (invalid) { invalid.classList.add('is-invalid'); invalid.reportValidity(); invalid.focus(); }
        }
    });

    updateContactFieldsVisibility();
    syncProfile();
    syncParticipant();
    syncPaymentDefault(false);
    var offerTitleLink = document.querySelector('.order-v2__offer-title-link');
    if (offerTitleLink && window.bootstrap && bootstrap.Tooltip) {
        new bootstrap.Tooltip(offerTitleLink);
    }
    @if($errors->any())
        var serverInvalid = form.querySelector('.is-invalid');
        var serverPanel = serverInvalid ? serverInvalid.closest('[data-v2-step]') : null;
        if (serverPanel) current = Number(serverPanel.dataset.v2Step);
        document.getElementById('order-v2-errors').focus();
    @endif
    showStep(current, false);
})();
</script>

@include('courses.partials.order-form-submit-guard')
@include('courses.partials.marketing-ga-event', ['course' => $course, 'gaEvent' => 'order_form_view'])

@if(config('analytics.enabled', true))
    @php
        $analyticsPriceVariantId = is_numeric($rawVariantId) ? (int) $rawVariantId : null;
        $analyticsFormSessionId = app(\App\Services\Analytics\OrderFormSessionService::class)->id(request(), (int) $course->id);
    @endphp
    <div id="order-form-analytics-config" hidden
        data-endpoint="{{ route('analytics.client-events.store') }}"
        data-course-id="{{ (int) $course->id }}"
        data-price-variant-id="{{ $analyticsPriceVariantId ?? '' }}"
        data-form-session-id="{{ $analyticsFormSessionId ?? '' }}"
        data-form-variant="v2"
        data-tracking-schema-version="2"
        data-max-batch="{{ (int) config('analytics.client_events.max_events_per_batch', 20) }}"></div>
    @include('courses.partials.order-form-client-tracking')
@endif
@endsection
