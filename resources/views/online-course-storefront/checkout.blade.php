@extends('layouts.app')

@php
    $course = $product->onlineCourse;
    $isEditMode = $isEditMode ?? false;
    $prefill = $prefill ?? [];
    $loggedInUser = $loggedInUser ?? auth()->user();
    $profile = old('customer_profile', $prefill['customer_profile'] ?? 'person');
    $canPayOnline = $offer->allow_payu || $offer->allow_paynow;
    $defaultPayment = $prefill['payment_type']
        ?? (($profile === 'person' && $canPayOnline) ? 'online' : 'deferred');
    if (! $offer->allow_deferred_invoice && $canPayOnline) {
        $defaultPayment = 'online';
    }
    $paymentType = old('payment_type', $defaultPayment);
    $oldParticipants = old('participants', $prefill['participants'] ?? [[
        'first_name' => $loggedInUser?->first_name ?? '',
        'last_name' => $loggedInUser?->last_name ?? '',
        'email' => $loggedInUser?->email ?? '',
    ]]);
    $hasMultiplePrices = $prices->count() > 1;
@endphp

@section('title', 'Zamów kurs online – '.$product->name.' | PNEDU')
@section('meta_description', 'Formularz zamówienia kursu online '.$product->name.'. Kup dostęp dla jednej osoby lub wielu uczestników.')
@section('robots', 'noindex,nofollow')

@push('styles')
<style>
    .order-v2 { --v2-primary: #14532d; --v2-soft: #f0fdf4; --v2-border: #d1d5db; max-width: 980px; }
    .order-v2__offer-summary { background: linear-gradient(135deg, #f0fdf4, #eff6ff); border: 1px solid #bbf7d0; border-radius: 1rem; }
    .order-v2__offer-eyebrow { letter-spacing: .04em; font-size: .7rem; }
    .order-v2__offer-title { font-size: 1.35rem; font-weight: 700; color: #1976d2; line-height: 1.3; }
    .order-v2__offer-title-link { color: inherit; text-decoration: none; }
    .order-v2__offer-title-link:hover,
    .order-v2__offer-title-link:focus { color: #0d47a1; text-decoration: none; }
    .order-v2__offer-meta-text { font-size: .8125rem; color: #555; line-height: 1.35; }
    .order-v2__offer-meta-text p { margin-bottom: .2rem; }
    .order-v2__offer-meta-text p:last-child { margin-bottom: 0; }
    .order-v2__offer-meta-text strong { color: #333; font-weight: 700; }
    .order-v2__offer-price { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
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
    .order-v2__summary { background: #f8fafc; border-left: 4px solid var(--v2-primary); }
    .order-v2__actions { position: sticky; bottom: 0; z-index: 5; background: rgba(255,255,255,.96); border-top: 1px solid #e5e7eb; }
    .order-v2 .btn-success { background-color: var(--v2-primary); border-color: var(--v2-primary); }
    .product-checkout .gus-nip-button,
    .order-v2 .gus-nip-button {
        background-color: #43a047;
        border-color: #43a047;
        color: #fff;
        font-weight: 600;
        white-space: normal;
        line-height: 1.25;
    }
    .product-checkout .gus-nip-button:hover,
    .product-checkout .gus-nip-button:focus,
    .order-v2 .gus-nip-button:hover,
    .order-v2 .gus-nip-button:focus {
        background-color: #2e7d32;
        border-color: #2e7d32;
        color: #fff;
    }
    .product-checkout .gus-nip-button:disabled,
    .order-v2 .gus-nip-button:disabled {
        background-color: #81c784;
        border-color: #81c784;
        color: #fff;
        opacity: 1;
    }
    .product-checkout #earlyPerformanceWrap .form-check-input { margin-top: .45em; }
    .product-checkout #earlyPerformanceWrap .form-check-label { line-height: 1.5; }
    @media (min-width: 768px) {
        .order-v2__steps { font-size: .9rem; }
        .order-v2__panel { padding: 2rem !important; }
    }
</style>
@endpush

@section('content')
<main class="container py-4 py-lg-5 order-v2 product-checkout">
    <nav aria-label="Okruszki">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.index') }}">Kursy</a></li>
            <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.show', $product->slug) }}">{{ $product->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Zamówienie</li>
        </ol>
    </nav>

    @if(!app()->environment('production'))
        <div class="alert alert-warning">
            Tryb developerski: checkout kursów cyfrowych korzysta obecnie z obowiązującego Regulaminu. Zapisy prawne dla treści cyfrowych będą dopracowane przed wdrożeniem produkcyjnym.
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert" id="product-checkout-errors">
            <strong>Sprawdź formularz:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('online-courses.checkout.store', $product->slug) }}"
          id="product-checkout-form"
          novalidate
          data-allows-multiple="{{ $offer->allow_multiple_recipients ? '1' : '0' }}"
          data-error-fields="{{ implode(',', $errors->keys()) }}"
          @if($loggedInUser) data-logged-in-email="{{ $loggedInUser->email }}" @endif>
        @csrf
        @if($isEditMode && $order)
            <input type="hidden" name="order_ident" value="{{ $order->ident }}">
            <input type="hidden" name="payment_type" value="{{ old('payment_type', $prefill['payment_type'] ?? 'deferred') }}">
        @endif

        @include('online-course-storefront.partials.checkout-offer-summary')

        <div class="mb-4" aria-label="Postęp zamówienia">
            <div class="progress order-v2__progress" role="progressbar" aria-label="Postęp formularza" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1">
                <div class="progress-bar bg-success" id="checkout-progress" style="width: 25%"></div>
            </div>
            <div class="order-v2__steps mt-2" aria-hidden="true">
                <span class="order-v2__step-label is-active">1. Profil</span>
                <span class="order-v2__step-label">2. Kontakt</span>
                <span class="order-v2__step-label">3. Faktura</span>
                <span class="order-v2__step-label">4. Płatność</span>
            </div>
        </div>

        <section class="order-v2__panel p-3 mb-4" data-checkout-step="1" aria-labelledby="checkout-step-1-title">
            <h2 class="h4 mb-2" id="checkout-step-1-title">Kto zamawia kurs?</h2>
            <p class="text-muted">Najpierw dopasujemy formularz do sposobu rozliczenia.</p>
            @if($hasMultiplePrices)
                <div class="mb-4">
                    <h3 class="h6">Wariant dostępu</h3>
                    <div class="row g-3">
                        @foreach($prices as $price)
                            <div class="col-md-6">
                                <label class="order-v2__choice d-block p-3" for="productPrice{{ $price->id }}">
                                    <input type="radio"
                                           class="form-check-input me-2"
                                           id="productPrice{{ $price->id }}"
                                           name="product_price_id"
                                           value="{{ $price->id }}"
                                           data-unit-price="{{ $price->currentPrice() }}"
                                           data-requires-waiver="{{ app(\App\Services\WithdrawalWindowService::class)->startsWithinWindow($price->access_starts_at) ? '1' : '0' }}"
                                           data-access-label="{{ $price->accessLabel() }}"
                                           data-access-start="{{ $price->accessStartLabel() ?? '' }}"
                                           data-variant-name="{{ $price->name }}"
                                           @checked((int) old('product_price_id', $selectedPrice->id) === $price->id)>
                                    <strong>{{ $price->name }}</strong>
                                    <span class="d-block small text-muted mt-1">{{ $price->accessLabel() }}</span>
                                    @if($price->isPromotionActive())
                                        <span class="d-block small text-decoration-line-through text-muted mt-2">{{ number_format((float) $price->price, 2, ',', ' ') }} PLN</span>
                                        <span class="d-block fs-5 fw-bold text-danger">{{ number_format((float) $price->currentPrice(), 2, ',', ' ') }} zł</span>
                                        <div class="mt-2">
                                            @include('online-course-storefront.partials.promotion-notice', ['price' => $price])
                                        </div>
                                    @else
                                        <span class="d-block fs-5 fw-bold mt-2">{{ number_format((float) $price->currentPrice(), 2, ',', ' ') }} zł</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <input type="hidden"
                       name="product_price_id"
                       value="{{ $selectedPrice->id }}"
                       data-unit-price="{{ $selectedPrice->currentPrice() }}"
                       data-requires-waiver="{{ app(\App\Services\WithdrawalWindowService::class)->startsWithinWindow($selectedPrice->access_starts_at) ? '1' : '0' }}"
                       data-access-label="{{ $selectedPrice->accessLabel() }}"
                       data-access-start="{{ $selectedPrice->accessStartLabel() ?? '' }}"
                       data-variant-name="{{ $selectedPrice->name }}">
            @endif
            <div class="row g-3">
                @foreach([
                    'school' => ['Szkoła publiczna / JST', 'Dane nabywcy oraz — jeśli wymaga tego faktura — dane odbiorcy. Wpisujesz je tak, jak przyjęte jest w Twojej placówce.'],
                    'organisation' => ['Placówka niepubliczna / firma', 'Dane nabywcy i — opcjonalnie — odbiorcy na fakturze.'],
                    'person' => ['Osoba prywatna', 'Proste dane nabywcy i płatność online.'],
                    'jdg' => ['JDG — zakup niezawodowy', 'Jednoosobowa działalność, gdy ten kurs nie ma charakteru zawodowego dla Twojej działalności.'],
                ] as $value => [$label, $description])
                    <div class="col-sm-6">
                        <label class="order-v2__choice d-block p-3" for="profile{{ ucfirst($value) }}">
                            <input type="radio" class="form-check-input me-2" name="customer_profile"
                                   id="profile{{ ucfirst($value) }}" value="{{ $value }}" @checked($profile === $value)>
                            <strong>{{ $label }}</strong>
                            <span class="d-block small text-muted mt-1">{{ $description }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="order-v2__panel p-3 mb-4" data-checkout-step="2" hidden aria-labelledby="checkout-step-2-title">
            <h2 class="h4 mb-2" id="checkout-step-2-title">Kontakt i uczestnicy</h2>
            <p class="text-muted">Na e-mail kontaktowy wyślemy potwierdzenie. Każdy uczestnik dostaje dostęp na swój adres.</p>
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label order-v2__required" for="contactName">Nazwa / imię i nazwisko zamawiającego</label>
                    <input id="contactName" name="contact_name" class="form-control" required value="{{ old('contact_name', $prefill['contact_name'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label order-v2__required" for="contactEmail">E-mail kontaktowy</label>
                    <input id="contactEmail" type="email" name="contact_email" class="form-control" required value="{{ old('contact_email', $prefill['contact_email'] ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="contactPhone">Telefon — opcjonalnie</label>
                    <input id="contactPhone" type="tel" name="contact_phone" class="form-control" value="{{ old('contact_phone', $prefill['contact_phone'] ?? '') }}">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                <div>
                    <h3 class="h6 mb-0">Uczestnicy kursu</h3>
                    <small class="text-muted">Adresy e-mail nie mogą się powtarzać.</small>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addProductParticipant"
                        @if(!$offer->allow_multiple_recipients) hidden @endif>Dodaj uczestnika</button>
            </div>
            <div id="productParticipantRows" class="d-grid gap-2">
                @foreach($oldParticipants as $index => $participant)
                    <div class="row g-2 align-items-end border rounded p-2 product-participant-row">
                        <div class="col-md-3">
                            <label class="form-label small order-v2__required">Imię</label>
                            <input name="participants[{{ $index }}][first_name]" class="form-control" required value="{{ $participant['first_name'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small order-v2__required">Nazwisko</label>
                            <input name="participants[{{ $index }}][last_name]" class="form-control" required value="{{ $participant['last_name'] ?? '' }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small order-v2__required">E-mail do konta</label>
                            <input type="email" name="participants[{{ $index }}][email]" class="form-control product-participant-email" required value="{{ $participant['email'] ?? '' }}">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100 product-participant-remove" aria-label="Usuń uczestnika">×</button>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($loggedInUser)
                <div id="loggedInEmailWarning" class="alert alert-warning mt-3 mb-0" hidden role="status">
                    Jesteś zalogowany jako <strong>{{ $loggedInUser->email }}</strong>.
                    Jeśli podasz inny adres uczestnika, dostęp do kursu trafi na to konto.
                    Na pnedu.pl powstanie wtedy osobne konto dla nowego adresu, a ten kurs nie pojawi się na Twoim obecnym koncie.
                </div>
            @endif
        </section>

        <section class="order-v2__panel p-3 mb-4" data-checkout-step="3" hidden aria-labelledby="checkout-step-3-title">
            <h2 class="h4 mb-2" id="checkout-step-3-title">Dane do faktury</h2>
            <p class="text-muted">Dane do wystawienia faktury.</p>
            <div id="organisationBuyerFields">
                <div class="row g-3 mb-3">
                    <div class="col-lg-7">
                        <label class="form-label order-v2__required" for="buyerNip">NIP nabywcy</label>
                        <div class="input-group">
                            <input id="buyerNip" name="buyer_nip" class="form-control" inputmode="numeric" autocomplete="off" value="{{ old('buyer_nip', $prefill['buyer_nip'] ?? '') }}">
                            <button class="btn gus-nip-button" type="button" data-gus-target="buyer">Wpisz NIP i pobierz dane z GUS</button>
                        </div>
                        <div class="form-text" id="buyer-gus-status" aria-live="polite"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label order-v2__required" for="buyerName">Nazwa nabywcy</label>
                        <input id="buyerName" name="buyer_name" class="form-control" value="{{ old('buyer_name', $prefill['buyer_name'] ?? '') }}">
                    </div>
                </div>
            </div>
            <div id="personBuyerFields" hidden>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label order-v2__required" for="buyerPersonFirstName">Imię nabywcy</label>
                        <input id="buyerPersonFirstName" name="buyer_person_first_name" class="form-control" value="{{ old('buyer_person_first_name', $prefill['buyer_person_first_name'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label order-v2__required" for="buyerPersonLastName">Nazwisko nabywcy</label>
                        <input id="buyerPersonLastName" name="buyer_person_last_name" class="form-control" value="{{ old('buyer_person_last_name', $prefill['buyer_person_last_name'] ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label order-v2__required" for="buyerAddress">Adres</label>
                    <input id="buyerAddress" name="buyer_address" class="form-control" required value="{{ old('buyer_address', $prefill['buyer_address'] ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label order-v2__required" for="buyerPostcode">Kod pocztowy</label>
                    <input id="buyerPostcode" name="buyer_postcode" class="form-control" required value="{{ old('buyer_postcode', $prefill['buyer_postcode'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label order-v2__required" for="buyerCity">Miejscowość</label>
                    <input id="buyerCity" name="buyer_city" class="form-control" required value="{{ old('buyer_city', $prefill['buyer_city'] ?? '') }}">
                </div>
            </div>

            <div class="form-check form-switch my-4" id="recipientToggleWrap">
                <input class="form-check-input" type="checkbox" id="hasRecipient" @checked(filled(old('recipient_name', $prefill['recipient_name'] ?? '')))>
                <label class="form-check-label" for="hasRecipient">Faktura ma mieć innego odbiorcę</label>
            </div>
            <div id="recipientFields" hidden>
                <hr>
                <h3 class="h6 text-uppercase text-success">Odbiorca</h3>
                <div class="row g-3">
                    <div class="col-lg-7">
                        <label class="form-label" for="recipientNip">NIP odbiorcy</label>
                        <div class="input-group">
                            <input id="recipientNip" name="recipient_nip" class="form-control" inputmode="numeric" autocomplete="off" placeholder="NIP odbiorcy" value="{{ old('recipient_nip', $prefill['recipient_nip'] ?? '') }}">
                            <button class="btn gus-nip-button" type="button" data-gus-target="recipient">Wpisz NIP i pobierz dane z GUS</button>
                        </div>
                        <div class="form-text" id="recipient-gus-status" aria-live="polite"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="recipientName">Nazwa odbiorcy</label>
                        <input id="recipientName" name="recipient_name" class="form-control" placeholder="Nazwa odbiorcy" value="{{ old('recipient_name', $prefill['recipient_name'] ?? '') }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="recipientAddress">Adres</label>
                        <input id="recipientAddress" name="recipient_address" class="form-control" placeholder="Adres" value="{{ old('recipient_address', $prefill['recipient_address'] ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="recipientPostcode">Kod pocztowy</label>
                        <input id="recipientPostcode" name="recipient_postcode" class="form-control" placeholder="Kod pocztowy" value="{{ old('recipient_postcode', $prefill['recipient_postcode'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="recipientCity">Miejscowość</label>
                        <input id="recipientCity" name="recipient_city" class="form-control" placeholder="Miejscowość" value="{{ old('recipient_city', $prefill['recipient_city'] ?? '') }}">
                    </div>
                </div>
            </div>
        </section>

        <section class="order-v2__panel p-3 mb-4" data-checkout-step="4" hidden aria-labelledby="checkout-step-4-title">
            <h2 class="h4 mb-2" id="checkout-step-4-title">Płatność i podsumowanie</h2>
            <div class="row g-3">
                @if($offer->allow_deferred_invoice)
                    <div class="col-md-6">
                        <label class="order-v2__choice d-block p-3" for="paymentDeferred">
                            <input type="radio" class="form-check-input me-2" name="payment_type" id="paymentDeferred" value="deferred"
                                   @checked($paymentType === 'deferred')
                                   @if($isEditMode) disabled @endif>
                            <strong>Faktura z odroczonym terminem</strong>
                            <span class="d-block small text-muted mt-1">Standardowy wybór dla szkół i organizacji.</span>
                        </label>
                    </div>
                @endif
                @if($offer->allow_payu || $offer->allow_paynow)
                    <div class="col-md-6">
                        <label class="order-v2__choice d-block p-3" for="paymentOnline">
                            <input type="radio" class="form-check-input me-2" name="payment_type" id="paymentOnline" value="online"
                                   @checked($paymentType === 'online')
                                   @if($isEditMode) disabled @endif>
                            <strong>Płatność online</strong>
                            <span class="d-block small text-muted mt-1">Szybkie przekierowanie do bezpiecznej bramki.</span>
                        </label>
                    </div>
                @endif
            </div>
            <div class="row g-3 mt-2">
                @if($offer->allow_deferred_invoice)
                    <div class="col-md-4" id="paymentTermsWrap">
                        <label class="form-label" for="paymentTerms">Termin płatności</label>
                        <div class="input-group">
                            <input id="paymentTerms" type="number" name="payment_terms" class="form-control" min="0" max="30" value="{{ old('payment_terms', $prefill['payment_terms'] ?? 14) }}">
                            <span class="input-group-text">dni</span>
                        </div>
                    </div>
                @endif
                <div class="col-md-8" id="paymentGatewayWrap" hidden>
                    <label class="form-label d-block">Bramka płatności</label>
                    @if($offer->allow_payu)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_gateway" id="gatewayPayu" value="payu" @checked(old('payment_gateway', 'payu') === 'payu')>
                            <label class="form-check-label" for="gatewayPayu">PayU</label>
                        </div>
                    @endif
                    @if($offer->allow_paynow)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_gateway" id="gatewayPaynow" value="paynow" @checked(old('payment_gateway') === 'paynow' || !$offer->allow_payu)>
                            <label class="form-check-label" for="gatewayPaynow">PayNow</label>
                        </div>
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label" for="invoiceNotes">Uwagi do faktury (opcjonalnie)</label>
                    <textarea id="invoiceNotes" name="invoice_notes" class="form-control" rows="2">{{ old('invoice_notes', $prefill['invoice_notes'] ?? '') }}</textarea>
                </div>
            </div>

            <div class="order-v2__summary p-3 mt-4 mb-3" id="checkoutSummaryBox">
                <div><strong>Kurs:</strong> {{ $product->name }}</div>
                <div><strong>Wariant:</strong> <span id="checkoutVariantName">{{ $selectedPrice->name }}</span></div>
                <div><strong>Uczestnicy:</strong> <span id="checkoutParticipantCount">1</span></div>
                <div><strong>Cena za osobę:</strong> <span id="checkoutUnitPrice">{{ number_format((float) $selectedPrice->currentPrice(), 2, ',', ' ') }}</span> zł</div>
                <div><strong>Razem:</strong> <span id="checkoutTotal">0,00</span> zł</div>
                <div><strong>Płatność:</strong> <span id="checkoutPaymentLabel">—</span></div>
                <div id="checkoutPaymentNote" class="small text-muted"></div>
                <div><strong>Start dostępu:</strong> <span id="checkoutAccessStart">{{ $selectedPrice->accessStartLabel() ?: 'po potwierdzeniu płatności' }}</span></div>
                <div><strong>Okres:</strong> <span id="checkoutAccessLabel">{{ $selectedPrice->accessLabel() }}</span></div>
                @if($viewerAccess?->canEnter() && ! $viewerAccess->isUnlimited())
                    <div class="mt-2">
                        To zamówienie przedłuży aktywny dostęp.
                        Dotychczasowy koniec: <strong>{{ $viewerAccess->endsLabel() }}</strong>.
                        Nowy okres dolicza się do tej daty, nie zaczyna się od dziś.
                    </div>
                @endif
            </div>

            <p class="small">
                Składając zamówienie, zawierasz odpłatną umowę na warunkach
                <a href="{{ route('regulamin.version', $termsVersion) }}" target="_blank" rel="noopener">Regulaminu w wersji {{ $termsVersion }}</a>.
                Przed zakupem możesz
                <a href="{{ route('regulamin.pdf', $termsVersion) }}" target="_blank" rel="noopener">zapisać jego treść</a>.
                Informacje o przetwarzaniu danych znajdziesz w
                <a href="{{ route('rodo') }}" target="_blank" rel="noopener">Informacji RODO</a>
                i
                <a href="{{ route('polityka-prywatnosci') }}" target="_blank" rel="noopener">Polityce prywatności</a>.
            </p>
            <p class="small" id="checkoutWithdrawalInfo" hidden>
                Co do zasady masz 14 dni od zawarcia umowy na odstąpienie. Zasady, wyjątki i formularz znajdziesz w informacji
                <a href="{{ route('withdrawal') }}" target="_blank" rel="noopener">Odstąpienie od umowy</a>.
            </p>
            @if($offer->hasSatisfactionGuarantee())
                <div class="mb-3">
                    @include('online-course-storefront.partials.satisfaction-guarantee', ['offer' => $offer])
                </div>
            @endif
            <div class="form-check" id="earlyPerformanceWrap" hidden>
                <input class="form-check-input" type="checkbox" name="early_performance_accepted" id="earlyPerformanceAccepted" value="1"
                       @checked((bool) old('early_performance_accepted'))>
                <label class="form-check-label" for="earlyPerformanceAccepted">
                    {{ $legalStatement }} <span class="text-danger">*</span>
                </label>
            </div>
        </section>

        <div class="order-v2__actions py-3 d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary" id="checkout-back" hidden>Wstecz</button>
            <a href="{{ route('online-courses.catalog.show', $product->slug) }}" class="btn btn-link text-secondary" id="checkout-cancel">Wróć do oferty</a>
            <button type="button" class="btn btn-success ms-auto px-4" id="checkout-next">Dalej</button>
            <button type="submit" class="btn btn-success btn-lg ms-auto" id="product-checkout-submit" hidden>
                {{ $isEditMode ? 'Zapisz poprawki' : 'Potwierdzam zakup' }}
            </button>
        </div>
    </form>
</main>

<template id="productParticipantTemplate">
    <div class="row g-2 align-items-end border rounded p-2 product-participant-row">
        <div class="col-md-3"><label class="form-label small order-v2__required">Imię</label><input data-field="first_name" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label small order-v2__required">Nazwisko</label><input data-field="last_name" class="form-control" required></div>
        <div class="col-md-5"><label class="form-label small order-v2__required">E-mail do konta</label><input type="email" data-field="email" class="form-control product-participant-email" required></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 product-participant-remove" aria-label="Usuń uczestnika">×</button></div>
    </div>
</template>
@endsection

@push('scripts')
@include('online-course-storefront.partials.promotion-countdown-script')
<script>
(function () {
    var form = document.getElementById('product-checkout-form');
    if (!form) return;
    var panels = Array.prototype.slice.call(form.querySelectorAll('[data-checkout-step]'));
    var current = 1;
    var rows = document.getElementById('productParticipantRows');
    var addButton = document.getElementById('addProductParticipant');
    var template = document.getElementById('productParticipantTemplate');
    var organisationFields = document.getElementById('organisationBuyerFields');
    var personFields = document.getElementById('personBuyerFields');
    var recipientToggleWrap = document.getElementById('recipientToggleWrap');
    var recipientToggle = document.getElementById('hasRecipient');
    var recipientFields = document.getElementById('recipientFields');
    var termsWrap = document.getElementById('paymentTermsWrap');
    var gatewayWrap = document.getElementById('paymentGatewayWrap');
    var earlyWrap = document.getElementById('earlyPerformanceWrap');
    var earlyInput = document.getElementById('earlyPerformanceAccepted');

    function profile() {
        return form.querySelector('[name="customer_profile"]:checked')?.value || 'person';
    }
    function selectedPriceInput() {
        return form.querySelector('[name="product_price_id"]:checked')
            || form.querySelector('[name="product_price_id"]');
    }
    function enableFields(container, enabled) {
        if (!container) return;
        container.hidden = !enabled;
        container.querySelectorAll('input, select, textarea').forEach(function (field) { field.disabled = !enabled; });
    }
    function showStep(step, focusHeading) {
        current = Math.max(1, Math.min(4, step));
        panels.forEach(function (panel) {
            panel.hidden = Number(panel.dataset.checkoutStep) !== current;
        });
        document.getElementById('checkout-progress').style.width = (current * 25) + '%';
        document.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', current);
        document.querySelectorAll('.order-v2__step-label').forEach(function (label, index) {
            label.classList.toggle('is-active', index + 1 === current);
        });
        document.getElementById('checkout-back').hidden = current === 1;
        document.getElementById('checkout-cancel').hidden = current !== 1;
        document.getElementById('checkout-next').hidden = current === 4;
        document.getElementById('product-checkout-submit').hidden = current !== 4;
        if (current === 4) updateTotal();
        if (focusHeading !== false) {
            var heading = panels[current - 1].querySelector('h2');
            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus({preventScroll: true});
            }
            window.scrollTo({ top: Math.max(0, form.offsetTop - 120), behavior: 'smooth' });
        }
    }
    function validateCurrent() {
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
    function stepForField(name) {
        if (name.indexOf('customer_profile') === 0 || name.indexOf('product_price_id') === 0) return 1;
        if (name.indexOf('contact_') === 0 || name.indexOf('participants') === 0) return 2;
        if (name.indexOf('buyer_') === 0 || name.indexOf('recipient_') === 0) return 3;
        return 4;
    }
    function reindex() {
        rows.querySelectorAll('.product-participant-row').forEach(function (row, index) {
            row.querySelectorAll('input[data-field], input[name*="[first_name]"], input[name*="[last_name]"], input[name*="[email]"]').forEach(function (input) {
                var field = input.dataset.field || input.name.match(/\[(first_name|last_name|email)\]/)?.[1];
                if (field) input.name = 'participants[' + index + '][' + field + ']';
            });
            var remove = row.querySelector('.product-participant-remove');
            if (remove) remove.disabled = rows.children.length <= 1;
        });
        updateTotal();
        updateLoggedInEmailWarning();
    }
    function updateTotal() {
        var selected = selectedPriceInput();
        var unit = parseFloat(selected?.dataset.unitPrice || '0');
        var count = Math.max(1, rows.querySelectorAll('.product-participant-row').length);
        var online = form.querySelector('[name="payment_type"]:checked')?.value === 'online';
        document.getElementById('checkoutParticipantCount').textContent = String(count);
        document.getElementById('checkoutUnitPrice').textContent = unit.toFixed(2).replace('.', ',');
        document.getElementById('checkoutTotal').textContent = (unit * count).toFixed(2).replace('.', ',');
        var offerPrice = document.getElementById('checkoutOfferPrice');
        if (offerPrice) offerPrice.textContent = unit.toFixed(2).replace('.', ',') + ' zł';
        if (selected) {
            document.getElementById('checkoutVariantName').textContent = selected.dataset.variantName || '';
            document.getElementById('checkoutAccessLabel').textContent = selected.dataset.accessLabel || '';
            var offerAccess = document.getElementById('checkoutOfferAccessLabel');
            if (offerAccess) offerAccess.textContent = selected.dataset.accessLabel || '';
            document.getElementById('checkoutAccessStart').textContent = selected.dataset.accessStart
                || (online ? 'po potwierdzeniu płatności' : 'faktura i dostęp są niezależne — dostęp po obsłudze zamówienia');
        }
        document.getElementById('checkoutPaymentLabel').textContent = online ? 'Płatność online' : 'Faktura z odroczonym terminem';
        document.getElementById('checkoutPaymentNote').textContent = online
            ? 'Po złożeniu zamówienia przejdziesz do wybranego operatora płatności.'
            : 'Zapłata nastąpi na podstawie wystawionej faktury, w terminie wskazanym w podsumowaniu zamówienia. Złożenie zamówienia nie powoduje natychmiastowego pobrania płatności.';
    }
    function updateProfile() {
        var isPerson = profile() === 'person';
        var allowsMultiple = form.dataset.allowsMultiple === '1';
        enableFields(organisationFields, !isPerson);
        enableFields(personFields, isPerson);
        recipientToggleWrap.hidden = isPerson;
        if (isPerson || !allowsMultiple) {
            while (rows.children.length > 1) rows.lastElementChild.remove();
        }
        if (isPerson) {
            recipientToggle.checked = false;
        }
        enableFields(recipientFields, !isPerson && recipientToggle.checked);
        addButton.hidden = isPerson || !allowsMultiple;
        var protectedProfile = ['person', 'jdg'].indexOf(profile()) !== -1;
        var selectedPrice = selectedPriceInput();
        var requiresWaiver = selectedPrice && selectedPrice.dataset.requiresWaiver === '1';
        var needsWaiver = protectedProfile && requiresWaiver;
        var withdrawalInfo = document.getElementById('checkoutWithdrawalInfo');
        if (withdrawalInfo) withdrawalInfo.hidden = !protectedProfile;
        earlyWrap.hidden = !needsWaiver;
        earlyInput.disabled = !needsWaiver;
        earlyInput.required = needsWaiver;
        if (!needsWaiver) earlyInput.checked = false;
        reindex();
    }
    function updatePayment() {
        var online = form.querySelector('[name="payment_type"]:checked')?.value === 'online';
        enableFields(termsWrap, !online);
        enableFields(gatewayWrap, online);
        updateTotal();
    }

    addButton.addEventListener('click', function () {
        if (rows.children.length >= 50) return;
        rows.appendChild(template.content.cloneNode(true));
        reindex();
    });
    rows.addEventListener('click', function (event) {
        var button = event.target.closest('.product-participant-remove');
        if (!button || rows.children.length <= 1) return;
        button.closest('.product-participant-row').remove();
        reindex();
    });
    recipientToggle.addEventListener('change', updateProfile);
    form.querySelectorAll('[name="customer_profile"]').forEach(function (input) { input.addEventListener('change', updateProfile); });
    form.querySelectorAll('[name="payment_type"]').forEach(function (input) { input.addEventListener('change', updatePayment); });
    form.querySelectorAll('[name="product_price_id"]').forEach(function (input) {
        input.addEventListener('change', function () {
            updateTotal();
            updateProfile();
        });
    });
    form.querySelectorAll('input, select, textarea').forEach(function (input) {
        input.addEventListener('input', function () { input.classList.remove('is-invalid'); });
    });
    document.getElementById('checkout-next').addEventListener('click', function () {
        if (validateCurrent()) showStep(current + 1);
    });
    document.getElementById('checkout-back').addEventListener('click', function () {
        showStep(current - 1);
    });
    form.addEventListener('submit', function (event) {
        var invalid = Array.prototype.find.call(form.querySelectorAll('input, select, textarea'), function (input) {
            return !input.disabled && !input.checkValidity();
        });
        if (invalid) {
            event.preventDefault();
            var panel = invalid.closest('[data-checkout-step]');
            if (panel) showStep(Number(panel.dataset.checkoutStep));
            invalid.classList.add('is-invalid');
            invalid.reportValidity();
            invalid.focus();
        }
    });

    function updateLoggedInEmailWarning() {
        var warning = document.getElementById('loggedInEmailWarning');
        var loggedEmail = (form.dataset.loggedInEmail || '').trim().toLowerCase();
        if (!warning || !loggedEmail) return;
        var hasDifferent = Array.from(form.querySelectorAll('.product-participant-email')).some(function (input) {
            var value = (input.value || '').trim().toLowerCase();
            return value !== '' && value !== loggedEmail;
        });
        warning.hidden = !hasDifferent;
    }

    updateProfile();
    updatePayment();
    reindex();
    updateLoggedInEmailWarning();
    form.addEventListener('input', function (event) {
        if (event.target.classList.contains('product-participant-email')) {
            updateLoggedInEmailWarning();
        }
    });

    var errorSteps = (form.dataset.errorFields || '').split(',').filter(Boolean).map(stepForField);
    showStep(errorSteps.length ? Math.min.apply(null, errorSteps) : 1, false);

    var gusFields = {
        buyer: { nip: 'buyerNip', name: 'buyerName', address: 'buyerAddress', postcode: 'buyerPostcode', city: 'buyerCity', status: 'buyer-gus-status' },
        recipient: { nip: 'recipientNip', name: 'recipientName', address: 'recipientAddress', postcode: 'recipientPostcode', city: 'recipientCity', status: 'recipient-gus-status' }
    };
    var gusUrl = @json(route('courses.gus-lookup'));
    var csrf = form.querySelector('input[name="_token"]')?.value || '';

    function gusLookup(target, button) {
        var map = gusFields[target];
        var nip = map ? document.getElementById(map.nip) : null;
        var status = map ? document.getElementById(map.status) : null;
        if (!nip || !status) return;
        if (!nip.value.trim()) {
            nip.setCustomValidity('Wpisz NIP przed pobraniem danych.');
            nip.reportValidity();
            nip.setCustomValidity('');
            return;
        }
        button.disabled = true;
        status.textContent = 'Pobieranie danych…';
        fetch(gusUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ nip: nip.value, target: target })
        }).then(function (response) {
            return response.json().then(function (body) { return { ok: response.ok, body: body }; });
        }).then(function (result) {
            if (!result.ok || !result.body.success) {
                throw new Error(result.body.message || 'Nie znaleziono danych.');
            }
            var data = result.body.data || {};
            ['name', 'postcode', 'city', 'address', 'nip'].forEach(function (key) {
                var input = document.getElementById(map[key]);
                if (input && data[key]) input.value = data[key];
            });
            status.textContent = 'Dane pobrane z GUS.';
        }).catch(function (error) {
            status.textContent = error.message || 'Nie udało się pobrać danych. Wpisz je ręcznie.';
        }).finally(function () {
            button.disabled = false;
        });
    }

    form.querySelectorAll('[data-gus-target]').forEach(function (button) {
        button.addEventListener('click', function () { gusLookup(button.dataset.gusTarget, button); });
    });
})();
</script>
@endpush
