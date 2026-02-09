@extends('layouts.app')

@section('title', 'Zapłać online – ' . $course->title)

@push('styles')
<style>
    .order-form-section {
        background: linear-gradient(135deg, #f4f7fa 60%, #e3e9f3 100%);
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(25, 118, 210, 0.07), 0 1.5px 8px 0 rgba(0,0,0,0.04);
        padding: 2.2rem 1.5rem 1.5rem 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid #b0bec5;
    }
    .order-form-section legend {
        font-size: 1.18rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 1.2rem;
        padding: 0 0.7rem;
        width: auto;
        border-bottom: none;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(25, 118, 210, 0.04);
    }
    .order-form-section .form-label {
        font-weight: 500;
    }
    .order-form-section input:not([type="checkbox"]),
    .order-form-section textarea {
        border-radius: 7px;
        border: 1.5px solid #b0bec5;
        background: #fafdff;
        font-size: 1.07rem;
    }
    .order-form-section input:focus,
    .order-form-section textarea:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 2px #bbdefb;
        background: #fff;
    }
    .order-form-section .form-check-input {
        width: 1.25em;
        height: 1.25em;
        margin-top: 0.25em;
        border: 2px solid #b0bec5;
        cursor: pointer;
    }
    .order-form-section .form-check-input:checked {
        background-color: #1976d2;
        border-color: #1976d2;
    }
    .order-form-section .form-check-input:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
    }
    .course-title-section {
        background: linear-gradient(135deg, #e3f2fd 60%, #f3e5f5 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid #bbdefb;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.1);
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="mb-4">Zapłać online za szkolenie</h1>

            <div class="course-title-section">
                <strong>Szkolenie:</strong> {!! $course->title !!}<br>
                <strong>Data:</strong> {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}
                @php $priceInfo = $course->getCurrentPrice(); @endphp
                @if($priceInfo)
                    <br><strong>Cena:</strong>
                    @if($priceInfo['is_promotion'] && $priceInfo['original_price'])
                        <span class="text-muted text-decoration-line-through">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</span>
                        <span class="fw-bold text-danger">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> (brutto)
                    @else
                        <span class="fw-bold text-primary">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> (brutto)
                    @endif
                @endif
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('payment.online.store', $course->id) }}" id="payOnlineForm">
                @csrf
                <fieldset class="order-form-section">
                    <legend>Zamawiający</legend>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adres e-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email_confirmation" class="form-label">Powtórz adres e-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email_confirmation') is-invalid @enderror" id="email_confirmation" name="email_confirmation" value="{{ old('email_confirmation') }}" required autocomplete="email">
                        @error('email_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Numer telefonu <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="order_comment" class="form-label">Komentarz do zamówienia</label>
                        <textarea class="form-control @error('order_comment') is-invalid @enderror" id="order_comment" name="order_comment" rows="4" placeholder="Opcjonalne uwagi do zamówienia...">{{ old('order_comment') }}</textarea>
                        @error('order_comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>

                <fieldset class="order-form-section">
                    <legend>Dane do faktury</legend>
                    <div class="mb-0">
                        <label class="form-label d-block">Typ zamawiającego</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input @error('buyer_type') is-invalid @enderror" type="radio" name="buyer_type" id="buyer_type_person" value="person" {{ old('buyer_type', 'person') === 'person' ? 'checked' : '' }}>
                                <label class="form-check-label" for="buyer_type_person">Osoba fizyczna <small class="text-muted">(opcjonalne)</small></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('buyer_type') is-invalid @enderror" type="radio" name="buyer_type" id="buyer_type_organisation" value="organisation" {{ old('buyer_type') === 'organisation' ? 'checked' : '' }}>
                                <label class="form-check-label" for="buyer_type_organisation">Instytucja</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('buyer_type') is-invalid @enderror" type="radio" name="buyer_type" id="buyer_type_company" value="company" {{ old('buyer_type') === 'company' ? 'checked' : '' }}>
                                <label class="form-check-label" for="buyer_type_company">Firma</label>
                            </div>
                        </div>
                        @error('buyer_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </fieldset>

                {{-- Formularze adresowe – widoczne w zależności od typu zamawiającego --}}
                <div id="form-person" class="order-form-section buyer-type-form" data-type="person" style="display: none;">
                    <legend>Dane adresowe – Osoba fizyczna</legend>
                    <div class="mb-3">
                        <label for="person_full_name" class="form-label">Imię i nazwisko</label>
                        <input type="text" class="form-control" id="person_full_name" name="person_full_name" value="{{ old('person_full_name') }}">
                        @error('person_full_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <label for="person_street" class="form-label">Ulica</label>
                            <input type="text" class="form-control" id="person_street" name="person_street" value="{{ old('person_street') }}">
                            @error('person_street')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="person_building_no" class="form-label">Numer budynku</label>
                            <input type="text" class="form-control" id="person_building_no" name="person_building_no" value="{{ old('person_building_no') }}">
                            @error('person_building_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="person_flat_no" class="form-label">Numer lokalu</label>
                            <input type="text" class="form-control" id="person_flat_no" name="person_flat_no" value="{{ old('person_flat_no') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="person_postcode" class="form-label">Kod pocztowy</label>
                            <input type="text" class="form-control" id="person_postcode" name="person_postcode" placeholder="00-000" value="{{ old('person_postcode') }}">
                            @error('person_postcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="person_city" class="form-label">Miejscowość</label>
                            <input type="text" class="form-control" id="person_city" name="person_city" value="{{ old('person_city') }}">
                            @error('person_city')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="person_country" class="form-label">Kraj</label>
                            <input type="text" class="form-control" id="person_country" name="person_country" value="{{ old('person_country', 'Polska') }}">
                            @error('person_country')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div id="form-company" class="order-form-section buyer-type-form" data-type="company" style="display: none;">
                    <legend>Dane adresowe – Firma</legend>
                    <div class="mb-3">
                        <label for="company_nip" class="form-label">NIP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="company_nip" name="company_nip" placeholder="0000000000" value="{{ old('company_nip') }}">
                        @error('company_nip')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="company_country" class="form-label">Kraj <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="company_country" name="company_country" value="{{ old('company_country', 'Polska') }}">
                        @error('company_country')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Nazwa firmy <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name') }}">
                        @error('company_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <label for="company_street" class="form-label">Ulica <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_street" name="company_street" value="{{ old('company_street') }}">
                            @error('company_street')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="company_building_no" class="form-label">Numer budynku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_building_no" name="company_building_no" value="{{ old('company_building_no') }}">
                            @error('company_building_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="company_flat_no" class="form-label">Numer lokalu</label>
                            <input type="text" class="form-control" id="company_flat_no" name="company_flat_no" value="{{ old('company_flat_no') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_postcode" class="form-label">Kod pocztowy <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_postcode" name="company_postcode" placeholder="00-000" value="{{ old('company_postcode') }}">
                            @error('company_postcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="company_city" class="form-label">Miejscowość <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_city" name="company_city" value="{{ old('company_city') }}">
                            @error('company_city')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div id="form-organisation" class="buyer-type-form" data-type="organisation" style="display: none;">
                    <fieldset class="order-form-section">
                        <legend>NABYWCA</legend>
                        <div class="mb-3">
                            <label for="buyer_nip" class="form-label">NIP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="buyer_nip" name="buyer_nip" placeholder="0000000000" value="{{ old('buyer_nip') }}">
                            @error('buyer_nip')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="buyer_country" class="form-label">Kraj <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="buyer_country" name="buyer_country" value="{{ old('buyer_country', 'Polska') }}">
                            @error('buyer_country')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="buyer_name" class="form-label">Nazwa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="buyer_name" name="buyer_name" value="{{ old('buyer_name') }}">
                            @error('buyer_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label for="buyer_street" class="form-label">Ulica <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="buyer_street" name="buyer_street" value="{{ old('buyer_street') }}">
                                @error('buyer_street')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="buyer_building_no" class="form-label">Numer budynku <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="buyer_building_no" name="buyer_building_no" value="{{ old('buyer_building_no') }}">
                                @error('buyer_building_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="buyer_flat_no" class="form-label">Numer lokalu</label>
                                <input type="text" class="form-control" id="buyer_flat_no" name="buyer_flat_no" value="{{ old('buyer_flat_no') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="buyer_postcode" class="form-label">Kod pocztowy <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="buyer_postcode" name="buyer_postcode" placeholder="00-000" value="{{ old('buyer_postcode') }}">
                                @error('buyer_postcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="buyer_city" class="form-label">Miejscowość <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="buyer_city" name="buyer_city" value="{{ old('buyer_city') }}">
                                @error('buyer_city')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="order-form-section">
                        <legend>ODBIORCA <small class="text-muted">(opcjonalne)</small></legend>
                        <div class="mb-3">
                            <label for="recipient_nip" class="form-label">NIP</label>
                            <input type="text" class="form-control" id="recipient_nip" name="recipient_nip" placeholder="0000000000" value="{{ old('recipient_nip') }}">
                            @error('recipient_nip')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="recipient_country" class="form-label">Kraj</label>
                            <input type="text" class="form-control" id="recipient_country" name="recipient_country" value="{{ old('recipient_country', 'Polska') }}">
                            @error('recipient_country')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Nazwa</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" value="{{ old('recipient_name') }}">
                            @error('recipient_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label for="recipient_street" class="form-label">Ulica</label>
                                <input type="text" class="form-control" id="recipient_street" name="recipient_street" value="{{ old('recipient_street') }}">
                                @error('recipient_street')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="recipient_building_no" class="form-label">Numer budynku</label>
                                <input type="text" class="form-control" id="recipient_building_no" name="recipient_building_no" value="{{ old('recipient_building_no') }}">
                                @error('recipient_building_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="recipient_flat_no" class="form-label">Numer lokalu</label>
                                <input type="text" class="form-control" id="recipient_flat_no" name="recipient_flat_no" value="{{ old('recipient_flat_no') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="recipient_postcode" class="form-label">Kod pocztowy</label>
                                <input type="text" class="form-control" id="recipient_postcode" name="recipient_postcode" placeholder="00-000" value="{{ old('recipient_postcode') }}">
                                @error('recipient_postcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="recipient_city" class="form-label">Miejscowość</label>
                                <input type="text" class="form-control" id="recipient_city" name="recipient_city" value="{{ old('recipient_city') }}">
                                @error('recipient_city')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>
                </div>

                <fieldset class="order-form-section">
                    <legend>Bramka płatności</legend>
                    <label class="form-label d-block">Wybierz bramkę płatności <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                                <input class="form-check-input @error('payment_gateway') is-invalid @enderror" type="radio" name="payment_gateway" id="payment_gateway_payu" value="payu" {{ old('payment_gateway', 'paynow') === 'payu' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="payment_gateway_payu">PayU</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('payment_gateway') is-invalid @enderror" type="radio" name="payment_gateway" id="payment_gateway_paynow" value="paynow" {{ old('payment_gateway', 'paynow') === 'paynow' ? 'checked' : '' }}>
                                <label class="form-check-label" for="payment_gateway_paynow">Paynow</label>
                            </div>
                    </div>
                    @error('payment_gateway')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </fieldset>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">Przejdź do płatności</button>
                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">Powrót do szczegółów szkolenia</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var form = document.getElementById('payOnlineForm');
    var buyerTypeInputs = form.querySelectorAll('input[name="buyer_type"]');
    var typeForms = document.querySelectorAll('.buyer-type-form');
    var requiredFieldsByType = {
        person: [], // Osoba fizyczna - wszystkie pola opcjonalne
        company: ['company_nip', 'company_country', 'company_name', 'company_street', 'company_building_no', 'company_postcode', 'company_city'], // Firma - wszystkie pola required (oprócz flat_no)
        organisation: {
            buyer: ['buyer_nip', 'buyer_country', 'buyer_name', 'buyer_street', 'buyer_building_no', 'buyer_postcode', 'buyer_city'], // NABYWCA - wszystkie required
            recipient: [] // ODBIORCA - wszystkie opcjonalne (ale jeśli podane dane, to recipient_nip required - sprawdzane w walidacji backend)
        }
    };

    function getCurrentType() {
        var checked = form.querySelector('input[name="buyer_type"]:checked');
        return checked ? checked.value : 'person';
    }

    function toggleForms() {
        var type = getCurrentType();
        typeForms.forEach(function(el) {
            var visible = el.getAttribute('data-type') === type;
            el.style.display = visible ? 'block' : 'none';
            
            var inputs = el.querySelectorAll('input, select');
            inputs.forEach(function(inp) {
                if (!inp.name) return;
                
                if (type === 'person') {
                    // Osoba fizyczna - wszystkie pola opcjonalne
                    inp.required = false;
                } else if (type === 'company') {
                    // Firma - pola z listy required
                    inp.required = requiredFieldsByType.company.indexOf(inp.name) !== -1;
                } else if (type === 'organisation') {
                    // Instytucja - NABYWCA required, ODBIORCA opcjonalny
                    var isBuyerField = requiredFieldsByType.organisation.buyer.indexOf(inp.name) !== -1;
                    var isRecipientField = inp.name && inp.name.startsWith('recipient_');
                    
                    if (isBuyerField) {
                        inp.required = true;
                    } else if (isRecipientField) {
                        // ODBIORCA - wszystkie opcjonalne (walidacja recipient_nip jeśli podane dane - w backend)
                        inp.required = false;
                    } else {
                        inp.required = false;
                    }
                } else {
                    inp.required = false;
                }
            });
        });
    }
    
    // Dla instytucji - dynamiczna walidacja recipient_nip jeśli podane dane odbiorcy
    function checkRecipientNipRequirement() {
        var type = getCurrentType();
        if (type !== 'organisation') return;
        
        var recipientNip = document.getElementById('recipient_nip');
        var recipientFields = ['recipient_name', 'recipient_street', 'recipient_city', 'recipient_postcode', 'recipient_country'];
        var hasRecipientData = recipientFields.some(function(fieldName) {
            var field = document.getElementById(fieldName);
            return field && field.value.trim() !== '';
        });
        
        if (recipientNip) {
            recipientNip.required = hasRecipientData;
        }
    }
    
    // Dodaj event listenery dla pól odbiorcy w instytucji
    function setupRecipientValidation() {
        var recipientFields = ['recipient_name', 'recipient_street', 'recipient_city', 'recipient_postcode', 'recipient_country', 'recipient_nip'];
        recipientFields.forEach(function(fieldName) {
            var field = document.getElementById(fieldName);
            if (field) {
                field.addEventListener('input', checkRecipientNipRequirement);
                field.addEventListener('blur', checkRecipientNipRequirement);
            }
        });
    }

    buyerTypeInputs.forEach(function(inp) {
        inp.addEventListener('change', function() {
            toggleForms();
            setupRecipientValidation();
            checkRecipientNipRequirement();
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            toggleForms();
            setupRecipientValidation();
            checkRecipientNipRequirement();
        });
    } else {
        toggleForms();
        setupRecipientValidation();
        checkRecipientNipRequirement();
    }

    form.addEventListener('submit', function(e) {
        var email = document.getElementById('email').value;
        var emailConf = document.getElementById('email_confirmation').value;
        if (email !== emailConf) {
            e.preventDefault();
            alert('Adresy e-mail muszą być identyczne.');
            document.getElementById('email_confirmation').focus();
            return false;
        }
        toggleForms();
    });
})();
</script>
@endpush
@endsection
