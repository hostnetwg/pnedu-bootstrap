@extends('layouts.app')

@php
    $course = $product->onlineCourse;
    $isEditMode = $isEditMode ?? false;
    $prefill = $prefill ?? [];
    $loggedInUser = $loggedInUser ?? auth()->user();
    $profile = old('customer_profile', $prefill['customer_profile'] ?? 'school');
    $oldParticipants = old('participants', $prefill['participants'] ?? [[
        'first_name' => $loggedInUser?->first_name ?? '',
        'last_name' => $loggedInUser?->last_name ?? '',
        'email' => $loggedInUser?->email ?? '',
    ]]);
@endphp

@section('title', 'Zamów kurs online – '.$product->name.' | PNEDU')
@section('meta_description', 'Formularz zamówienia kursu online '.$product->name.'. Kup dostęp dla jednej osoby lub wielu uczestników.')
@section('robots', 'noindex,nofollow')

@push('styles')
<style>
    .product-checkout { max-width: 980px; }
    .product-checkout__panel { border: 1px solid #dee2e6; border-radius: 1rem; background: #fff; box-shadow: 0 .4rem 1.2rem rgba(15, 23, 42, .06); }
    .product-checkout__choice { border: 2px solid #dee2e6; border-radius: .75rem; cursor: pointer; height: 100%; }
    .product-checkout__choice:has(input:checked) { border-color: #198754; background: #f0fdf4; }
    .product-checkout__required::after { content: " *"; color: #b91c1c; }
    .product-checkout .gus-nip-button {
        background-color: #43a047;
        border-color: #43a047;
        color: #fff;
        font-weight: 600;
        white-space: normal;
        line-height: 1.25;
    }
    .product-checkout .gus-nip-button:hover,
    .product-checkout .gus-nip-button:focus {
        background-color: #2e7d32;
        border-color: #2e7d32;
        color: #fff;
    }
    .product-checkout .gus-nip-button:disabled {
        background-color: #81c784;
        border-color: #81c784;
        color: #fff;
        opacity: 1;
    }
    .product-checkout #earlyPerformanceWrap .form-check-input {
        margin-top: .45em;
    }
    .product-checkout #earlyPerformanceWrap .form-check-label {
        line-height: 1.5;
    }
</style>
@endpush

@section('content')
<main class="container py-4 py-lg-5 product-checkout">
    <nav aria-label="Okruszki">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.index') }}">Kursy</a></li>
            <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.show', $product->slug) }}">{{ $product->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Zamówienie</li>
        </ol>
    </nav>

    <header class="mb-4">
        <h1 class="h2">{{ $isEditMode ? 'Edytuj zamówienie kursu online' : 'Zamów kurs online' }}</h1>
        <p class="lead mb-1">{{ $product->name }}</p>
        <p class="text-muted">Cena jest liczona za każdego uczestnika. Każda osoba otrzyma dostęp na własny adres e-mail.</p>
    </header>

    @if(!app()->environment('production'))
        <div class="alert alert-warning">
            Tryb developerski: checkout kursów cyfrowych korzysta obecnie z obowiązującego Regulaminu. Zapisy prawne dla treści cyfrowych będą dopracowane przed wdrożeniem produkcyjnym.
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Sprawdź formularz:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('online-courses.checkout.store', $product->slug) }}"
          id="product-checkout-form"
          data-allows-multiple="{{ $offer->allow_multiple_recipients ? '1' : '0' }}"
          @if($loggedInUser) data-logged-in-email="{{ $loggedInUser->email }}" @endif>
        @csrf
        @if($isEditMode && $order)
            <input type="hidden" name="order_ident" value="{{ $order->ident }}">
            <input type="hidden" name="payment_type" value="{{ old('payment_type', $prefill['payment_type'] ?? 'deferred') }}">
        @endif

        <section class="product-checkout__panel p-3 p-md-4 mb-4">
            <h2 class="h4 mb-3">1. Wariant dostępu</h2>
            <div class="row g-3">
                @foreach($prices as $price)
                    <div class="col-md-6">
                        <label class="product-checkout__choice d-block p-3" for="productPrice{{ $price->id }}">
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
                            @if(filled($price->access_note))
                                <span class="d-block small mt-1">{{ $price->access_note }}</span>
                            @endif
                            @if($price->isPromotionActive())
                                <span class="d-block small text-decoration-line-through text-muted mt-2">{{ number_format((float) $price->price, 2, ',', ' ') }} PLN</span>
                                <span class="d-block fs-5 fw-bold text-danger">{{ number_format((float) $price->currentPrice(), 2, ',', ' ') }} PLN / osoba</span>
                                <div class="mt-2">
                                    @include('online-course-storefront.partials.promotion-notice', ['price' => $price])
                                </div>
                            @else
                                <span class="d-block fs-5 fw-bold mt-2">{{ number_format((float) $price->currentPrice(), 2, ',', ' ') }} zł / osoba</span>
                            @endif
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="product-checkout__panel p-3 p-md-4 mb-4">
            <h2 class="h4 mb-3">2. Kto zamawia?</h2>
            <div class="row g-3">
                @foreach([
                    'school' => 'Szkoła publiczna / JST',
                    'organisation' => 'Placówka niepubliczna / firma',
                    'person' => 'Osoba prywatna',
                    'jdg' => 'JDG – zakup niezawodowy',
                ] as $value => $label)
                    <div class="col-sm-6">
                        <label class="product-checkout__choice d-block p-3" for="profile{{ ucfirst($value) }}">
                            <input type="radio" class="form-check-input me-2" name="customer_profile"
                                   id="profile{{ ucfirst($value) }}" value="{{ $value }}" @checked($profile === $value)>
                            <strong>{{ $label }}</strong>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="product-checkout__panel p-3 p-md-4 mb-4">
            <h2 class="h4 mb-3">3. Kontakt i uczestnicy</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label product-checkout__required" for="contactName">Nazwa / imię i nazwisko zamawiającego</label>
                    <input id="contactName" name="contact_name" class="form-control" required value="{{ old('contact_name', $prefill['contact_name'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label product-checkout__required" for="contactEmail">E-mail kontaktowy</label>
                    <input id="contactEmail" type="email" name="contact_email" class="form-control" required value="{{ old('contact_email', $prefill['contact_email'] ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="contactPhone">Telefon — opcjonalnie, do kontaktu w sprawie zamówienia</label>
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
                            <label class="form-label small product-checkout__required">Imię</label>
                            <input name="participants[{{ $index }}][first_name]" class="form-control" required value="{{ $participant['first_name'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small product-checkout__required">Nazwisko</label>
                            <input name="participants[{{ $index }}][last_name]" class="form-control" required value="{{ $participant['last_name'] ?? '' }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small product-checkout__required">E-mail do konta</label>
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

        <section class="product-checkout__panel p-3 p-md-4 mb-4">
            <h2 class="h4 mb-3">4. Dane do faktury</h2>
            <div id="organisationBuyerFields">
                <div class="row g-3 mb-3">
                    <div class="col-lg-7">
                        <label class="form-label product-checkout__required" for="buyerNip">NIP nabywcy</label>
                        <div class="input-group">
                            <input id="buyerNip" name="buyer_nip" class="form-control" inputmode="numeric" autocomplete="off" value="{{ old('buyer_nip', $prefill['buyer_nip'] ?? '') }}">
                            <button class="btn gus-nip-button" type="button" data-gus-target="buyer">Wpisz NIP i pobierz dane z GUS</button>
                        </div>
                        <div class="form-text" id="buyer-gus-status" aria-live="polite"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label product-checkout__required" for="buyerName">Nazwa nabywcy</label>
                        <input id="buyerName" name="buyer_name" class="form-control" value="{{ old('buyer_name', $prefill['buyer_name'] ?? '') }}">
                    </div>
                </div>
            </div>
            <div id="personBuyerFields" hidden>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label product-checkout__required" for="buyerPersonFirstName">Imię nabywcy</label>
                        <input id="buyerPersonFirstName" name="buyer_person_first_name" class="form-control" value="{{ old('buyer_person_first_name', $prefill['buyer_person_first_name'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label product-checkout__required" for="buyerPersonLastName">Nazwisko nabywcy</label>
                        <input id="buyerPersonLastName" name="buyer_person_last_name" class="form-control" value="{{ old('buyer_person_last_name', $prefill['buyer_person_last_name'] ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label product-checkout__required" for="buyerAddress">Adres</label>
                    <input id="buyerAddress" name="buyer_address" class="form-control" required value="{{ old('buyer_address', $prefill['buyer_address'] ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label product-checkout__required" for="buyerPostcode">Kod pocztowy</label>
                    <input id="buyerPostcode" name="buyer_postcode" class="form-control" required value="{{ old('buyer_postcode', $prefill['buyer_postcode'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label product-checkout__required" for="buyerCity">Miejscowość</label>
                    <input id="buyerCity" name="buyer_city" class="form-control" required value="{{ old('buyer_city', $prefill['buyer_city'] ?? '') }}">
                </div>
            </div>

            <div class="form-check form-switch my-4" id="recipientToggleWrap">
                <input class="form-check-input" type="checkbox" id="hasRecipient" @checked(filled(old('recipient_name', $prefill['recipient_name'] ?? '')))>
                <label class="form-check-label" for="hasRecipient">Faktura ma mieć innego odbiorcę</label>
            </div>
            <div id="recipientFields" hidden>
                <hr>
                <h3 class="h6">Odbiorca</h3>
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

        <section class="product-checkout__panel p-3 p-md-4 mb-4">
            <h2 class="h4 mb-3">5. Płatność i podsumowanie</h2>
            <div class="row g-3">
                @if($offer->allow_deferred_invoice)
                    <div class="col-md-6">
                        <label class="product-checkout__choice d-block p-3" for="paymentDeferred">
                            <input type="radio" class="form-check-input me-2" name="payment_type" id="paymentDeferred" value="deferred"
                                   @checked(old('payment_type', $prefill['payment_type'] ?? 'deferred') === 'deferred')
                                   @if($isEditMode) disabled @endif>
                            <strong>Faktura z odroczonym terminem</strong>
                        </label>
                    </div>
                @endif
                @if($offer->allow_payu || $offer->allow_paynow)
                    <div class="col-md-6">
                        <label class="product-checkout__choice d-block p-3" for="paymentOnline">
                            <input type="radio" class="form-check-input me-2" name="payment_type" id="paymentOnline" value="online"
                                   @checked(old('payment_type', $prefill['payment_type'] ?? null) === 'online' || (!$offer->allow_deferred_invoice && !($prefill['payment_type'] ?? null)))
                                   @if($isEditMode) disabled @endif>
                            <strong>Płatność online</strong>
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

            <div class="alert alert-light border mt-4 mb-3" id="checkoutSummaryBox">
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

        <div class="d-flex justify-content-between align-items-center gap-3">
            <a href="{{ route('online-courses.catalog.show', $product->slug) }}" class="btn btn-outline-secondary">Wróć do oferty</a>
            <button type="submit" class="btn btn-success btn-lg">{{ $isEditMode ? 'Zapisz poprawki' : 'Potwierdzam zakup' }}</button>
        </div>
    </form>
</main>

<template id="productParticipantTemplate">
    <div class="row g-2 align-items-end border rounded p-2 product-participant-row">
        <div class="col-md-3"><label class="form-label small product-checkout__required">Imię</label><input data-field="first_name" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label small product-checkout__required">Nazwisko</label><input data-field="last_name" class="form-control" required></div>
        <div class="col-md-5"><label class="form-label small product-checkout__required">E-mail do konta</label><input type="email" data-field="email" class="form-control product-participant-email" required></div>
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
        return form.querySelector('[name="customer_profile"]:checked')?.value || 'school';
    }
    function enableFields(container, enabled) {
        if (!container) return;
        container.hidden = !enabled;
        container.querySelectorAll('input, select, textarea').forEach(function (field) { field.disabled = !enabled; });
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
        var selected = form.querySelector('[name="product_price_id"]:checked');
        var unit = parseFloat(selected?.dataset.unitPrice || '0');
        var count = Math.max(1, rows.querySelectorAll('.product-participant-row').length);
        var online = form.querySelector('[name="payment_type"]:checked')?.value === 'online';
        document.getElementById('checkoutParticipantCount').textContent = String(count);
        document.getElementById('checkoutUnitPrice').textContent = unit.toFixed(2).replace('.', ',');
        document.getElementById('checkoutTotal').textContent = (unit * count).toFixed(2).replace('.', ',');
        if (selected) {
            document.getElementById('checkoutVariantName').textContent = selected.dataset.variantName || '';
            document.getElementById('checkoutAccessLabel').textContent = selected.dataset.accessLabel || '';
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
        var selectedPrice = form.querySelector('[name="product_price_id"]:checked');
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
