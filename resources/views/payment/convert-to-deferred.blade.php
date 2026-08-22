@extends('layouts.app')

@section('title', 'Potwierdzenie zamówienia z fakturą odroczoną')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="order-summary-card">
                <div class="convert-header">
                    <h1><i class="bi bi-file-earmark-text me-2"></i>Potwierdzenie zamówienia</h1>
                    <p>Sprawdź dane i potwierdź zamówienie z fakturą z odroczonym terminem płatności.</p>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="order-info-box">
                    <h3>Zamówienie</h3>
                    <div class="info-row">
                        <span class="info-label">Numer:</span>
                        <span class="info-value">{{ $formOrder->ident }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kwota:</span>
                        <span class="info-value">{{ number_format((float) $formOrder->product_price, 2, ',', ' ') }} PLN</span>
                    </div>
                </div>

                @if($course)
                    <div class="order-info-box">
                        <h3>Szkolenie</h3>
                        <div class="info-row">
                            <span class="info-label">Temat:</span>
                            <span class="info-value">{!! $course->title !!}</span>
                        </div>
                        @if($course->start_date)
                            <div class="info-row">
                                <span class="info-label">Data:</span>
                                <span class="info-value">{{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="order-info-box">
                    <h3>Nabywca</h3>
                    <div class="info-row">
                        <span class="info-label">Nazwa:</span>
                        <span class="info-value">{{ $formOrder->buyer_name ?: '—' }}</span>
                    </div>
                    @if($formOrder->buyer_nip)
                        <div class="info-row">
                            <span class="info-label">NIP:</span>
                            <span class="info-value">{{ $formOrder->buyer_nip }}</span>
                        </div>
                    @endif
                    @if($formOrder->buyer_address || $formOrder->buyer_city)
                        <div class="info-row">
                            <span class="info-label">Adres:</span>
                            <span class="info-value">
                                {{ trim(implode(', ', array_filter([
                                    $formOrder->buyer_address,
                                    trim(($formOrder->buyer_postal_code ?? '').' '.($formOrder->buyer_city ?? '')),
                                ]))) }}
                            </span>
                        </div>
                    @endif
                </div>

                @if($formOrder->recipient_name || $formOrder->recipient_nip)
                    <div class="order-info-box">
                        <h3>Odbiorca</h3>
                        <div class="info-row">
                            <span class="info-label">Nazwa:</span>
                            <span class="info-value">{{ $formOrder->recipient_name ?: '—' }}</span>
                        </div>
                        @if($formOrder->recipient_nip)
                            <div class="info-row">
                                <span class="info-label">NIP:</span>
                                <span class="info-value">{{ $formOrder->recipient_nip }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if($formOrder->participants->isNotEmpty())
                    <div class="order-info-box">
                        <h3>Uczestnik{{ $formOrder->participants->count() > 1 ? 'cy' : '' }}</h3>
                        @foreach($formOrder->participants as $participant)
                            <div class="info-row mb-2">
                                <span class="info-value">
                                    {{ trim(($participant->participant_firstname ?? '').' '.($participant->participant_lastname ?? '')) }}
                                    @if($participant->participant_email)
                                        <span class="text-muted">({{ $participant->participant_email }})</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="order-info-box">
                    <h3>Kontakt</h3>
                    @if($formOrder->orderer_name)
                        <div class="info-row">
                            <span class="info-label">Imię i nazwisko:</span>
                            <span class="info-value">{{ $formOrder->orderer_name }}</span>
                        </div>
                    @endif
                    @if($formOrder->orderer_email)
                        <div class="info-row">
                            <span class="info-label">E-mail:</span>
                            <span class="info-value">{{ $formOrder->orderer_email }}</span>
                        </div>
                    @endif
                    @if($formOrder->orderer_phone)
                        <div class="info-row">
                            <span class="info-label">Telefon:</span>
                            <span class="info-value">{{ $formOrder->orderer_phone }}</span>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ $confirmUrl }}" class="convert-form">
                    @csrf
                    <div class="mb-4">
                        <label for="payment_terms" class="form-label fw-semibold">Termin płatności (dni)</label>
                        <input
                            type="number"
                            class="form-control form-control-lg @error('payment_terms') is-invalid @enderror"
                            id="payment_terms"
                            name="payment_terms"
                            min="0"
                            max="{{ $maxPaymentTerms }}"
                            value="{{ old('payment_terms', $defaultPaymentTerms) }}"
                            required
                        >
                        <div class="form-text">Maksymalnie {{ $maxPaymentTerms }} dni. Domyślnie {{ $defaultPaymentTerms }}.</div>
                        @error('payment_terms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        Potwierdzam zamówienie z fakturą z odroczonym terminem
                    </button>
                </form>

                <a href="{{ $editDataUrl }}" class="btn btn-outline-secondary w-100 mb-4">
                    Chcę poprawić dane
                </a>

                <p class="text-muted mb-0">W razie pytań skontaktuj się z nami: kontakt@pnedu.pl</p>
                <div class="mt-4">
                    @if($formOrder->product_id)
                        <a href="{{ route('courses.show', $formOrder->product_id) }}" class="btn btn-secondary">Powrót do szczegółów szkolenia</a>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary ms-2">Strona główna</a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
.order-summary-card { background: white; border-radius: 12px; box-shadow: 0 4px 18px rgba(25,118,210,0.1); padding: 2.5rem; }
.convert-header { background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%); color: white; padding: 2rem; border-radius: 12px; text-align: center; margin: -2.5rem -2.5rem 2rem -2.5rem; }
.convert-header h1 { font-size: 1.75rem; font-weight: 700; }
.order-info-box { background: #f8f9fa; border-left: 4px solid #1976d2; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 4px; }
.order-info-box h3 { font-size: 1.1rem; margin-bottom: 1rem; }
.info-row { margin-bottom: 0.75rem; }
.info-label { font-weight: 600; color: #495057; display: inline-block; min-width: 7rem; }
.info-value { color: #212529; }
.convert-form { padding-top: 0.25rem; }
</style>
@endpush
@endsection
