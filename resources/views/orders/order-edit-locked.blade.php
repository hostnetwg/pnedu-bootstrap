@extends('layouts.app')

@section('title', 'Zamówienie ' . $order->id . ' — podgląd')

@push('styles')
<style>
    .locked-order-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(25, 118, 210, 0.08);
        padding: 2rem 2.25rem;
        margin-bottom: 1.5rem;
    }
    .locked-order-card h2 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1565c0;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e3f2fd;
    }
    .locked-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 1rem;
        padding: 0.45rem 0;
        border-bottom: 1px solid #f0f4f8;
    }
    .locked-row:last-child {
        border-bottom: none;
    }
    .locked-label {
        font-weight: 600;
        color: #495057;
        min-width: 200px;
        max-width: 100%;
    }
    .locked-value {
        color: #212529;
        flex: 1;
        min-width: 0;
        word-break: break-word;
    }
    .locked-hero {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        border-left: 5px solid #1976d2;
        border-radius: 8px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.75rem;
    }
    .locked-hero p:last-child {
        margin-bottom: 0;
    }
    .locked-actions .btn {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')
@php
    $hasInvoice = trim((string) ($order->invoice_number ?? '')) !== '';
    $yn = fn ($b) => $b ? 'Tak' : 'Nie';
@endphp
<div class="container py-4 py-md-5">
    <h1 class="h3 mb-3"><i class="bi bi-lock-fill text-primary me-2"></i>Podgląd zamówienia</h1>

    <div class="locked-hero">
        @if($hasInvoice)
            <p class="mb-2 fw-semibold">Do tego zamówienia wystawiono fakturę nr <strong>{{ $order->invoice_number }}</strong>. Edycja danych z poziomu formularza nie jest już możliwa.</p>
        @else
            <p class="mb-2 fw-semibold">To zamówienie zostało oznaczone jako zakończone. Edycja danych z poziomu formularza nie jest już możliwa.</p>
        @endif
        <p class="text-muted mb-0 small">Poniżej znajdują się wszystkie zapisane dane zamówienia. W razie potrzeby korekty skontaktuj się z nami — podaj numer zamówienia: <strong>{{ $order->id }}</strong>.</p>
    </div>

    <div class="locked-actions mb-4">
        <a href="{{ route('home') }}#kontakt" class="btn btn-primary"><i class="bi bi-envelope me-1"></i> Formularz kontaktowy</a>
        <a href="mailto:kontakt@nowoczesna-edukacja.pl?subject={{ rawurlencode('Zamówienie '.$order->id) }}" class="btn btn-outline-primary"><i class="bi bi-at me-1"></i> kontakt@nowoczesna-edukacja.pl</a>
        @if($course)
            <a href="{{ route('courses.show', $course->id) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Strona szkolenia</a>
        @endif
        <a href="{{ route('orders.pdf', ['ident' => $order->ident]) }}" class="btn btn-outline-dark" target="_blank" rel="noopener"><i class="bi bi-file-pdf me-1"></i> Pobierz PDF</a>
        <a href="{{ route('orders.summary', ['ident' => $order->ident]) }}" class="btn btn-outline-secondary"><i class="bi bi-card-checklist me-1"></i> Podsumowanie</a>
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-hash me-2"></i>Identyfikacja i status</h2>
        <div class="locked-row"><span class="locked-label">Numer zamówienia</span><span class="locked-value"><strong>{{ $order->id }}</strong></span></div>
        @if($order->order_date)
        <div class="locked-row"><span class="locked-label">Data złożenia</span><span class="locked-value">{{ $order->order_date->format('d.m.Y H:i') }}</span></div>
        @endif
        <div class="locked-row"><span class="locked-label">Status zakończenia</span><span class="locked-value">{{ $yn($order->status_completed || $hasInvoice) }}</span></div>
        @if($order->trashed())
        <div class="locked-row"><span class="locked-label">Uwaga</span><span class="locked-value text-warning">Rekord technicznie oznaczony jako usunięty (podgląd z linku edycji).</span></div>
        @endif
        @if($order->invoice_number)
        <div class="locked-row"><span class="locked-label">Numer faktury</span><span class="locked-value">{{ $order->invoice_number }}</span></div>
        @endif
        @if(isset($order->ksef_number) && $order->ksef_number)
        <div class="locked-row"><span class="locked-label">Numer KSeF</span><span class="locked-value">{{ $order->ksef_number }}</span></div>
        @endif
        @if(isset($order->ksef_sent_at) && $order->ksef_sent_at)
        <div class="locked-row"><span class="locked-label">Data przesłania do KSeF</span><span class="locked-value">{{ \Carbon\Carbon::parse($order->ksef_sent_at)->format('d.m.Y H:i') }}</span></div>
        @endif
        @if(isset($order->ksef_status) && $order->ksef_status)
        <div class="locked-row"><span class="locked-label">Status KSeF</span><span class="locked-value">{{ $order->ksef_status }}</span></div>
        @endif
        @if(isset($order->ksef_error) && $order->ksef_error)
        <div class="locked-row"><span class="locked-label">Komunikat KSeF</span><span class="locked-value">{{ $order->ksef_error }}</span></div>
        @endif
        <div class="locked-row"><span class="locked-label">Odroczenie płatności (faktura, dni)</span><span class="locked-value">{{ $order->invoice_payment_delay !== null ? $order->invoice_payment_delay : '—' }}</span></div>
        @if($order->updated_manually_at)
        <div class="locked-row"><span class="locked-label">Ostatnia ręczna aktualizacja</span><span class="locked-value">{{ $order->updated_manually_at->format('d.m.Y H:i') }}</span></div>
        @endif
        <div class="locked-row"><span class="locked-label">Utworzono (rekord)</span><span class="locked-value">{{ $order->created_at?->format('d.m.Y H:i') ?? '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Ostatnia zmiana (rekord)</span><span class="locked-value">{{ $order->updated_at?->format('d.m.Y H:i') ?? '—' }}</span></div>
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-book me-2"></i>Szkolenie / produkt</h2>
        <div class="locked-row"><span class="locked-label">Nazwa</span><span class="locked-value">{{ str_replace('&nbsp;', ' ', strip_tags($order->product_name ?? '')) ?: '—' }}</span></div>
        @if($order->product_price !== null)
        <div class="locked-row"><span class="locked-label">Cena (brutto)</span><span class="locked-value">{{ number_format((float) $order->product_price, 2, ',', ' ') }} PLN</span></div>
        @endif
        @if($course && $course->start_date)
        <div class="locked-row"><span class="locked-label">Termin szkolenia (z kursu)</span><span class="locked-value">{{ $course->formatted_date ?? $course->start_date }}</span></div>
        @endif
        @if($order->product_description)
        <div class="locked-row align-items-start"><span class="locked-label">Opis produktu (zapisany)</span><span class="locked-value" style="white-space: pre-wrap;">{{ str_replace('&nbsp;', ' ', strip_tags($order->product_description)) }}</span></div>
        @endif
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-person-badge me-2"></i>Zamawiający (kontakt)</h2>
        <div class="locked-row"><span class="locked-label">Nazwa / imię i nazwisko</span><span class="locked-value">{{ $order->orderer_name ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Adres</span><span class="locked-value">{{ $order->orderer_address ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Kod pocztowy</span><span class="locked-value">{{ $order->orderer_postal_code ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Miejscowość</span><span class="locked-value">{{ $order->orderer_city ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Telefon</span><span class="locked-value">{{ $order->orderer_phone ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">E-mail</span><span class="locked-value">{{ $order->orderer_email ?: '—' }}</span></div>
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-building me-2"></i>Nabywca (faktura)</h2>
        <div class="locked-row"><span class="locked-label">Nazwa</span><span class="locked-value">{{ $order->buyer_name ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Adres</span><span class="locked-value">{{ $order->buyer_address ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Kod pocztowy</span><span class="locked-value">{{ $order->buyer_postal_code ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Miejscowość</span><span class="locked-value">{{ $order->buyer_city ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">NIP</span><span class="locked-value">{{ $order->buyer_nip ?: '—' }}</span></div>
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-geo-alt me-2"></i>Odbiorca</h2>
        <div class="locked-row"><span class="locked-label">Nazwa</span><span class="locked-value">{{ $order->recipient_name ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Adres</span><span class="locked-value">{{ $order->recipient_address ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Kod pocztowy</span><span class="locked-value">{{ $order->recipient_postal_code ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">Miejscowość</span><span class="locked-value">{{ $order->recipient_city ?: '—' }}</span></div>
        <div class="locked-row"><span class="locked-label">NIP</span><span class="locked-value">{{ $order->recipient_nip ?: '—' }}</span></div>
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-receipt me-2"></i>Faktura — uwagi</h2>
        <div class="locked-row align-items-start"><span class="locked-label">Uwagi do faktury</span><span class="locked-value" style="white-space: pre-wrap;">{{ $order->invoice_notes ?: '—' }}</span></div>
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-people me-2"></i>Uczestnicy</h2>
        @forelse($order->participants as $idx => $p)
            <p class="fw-semibold text-secondary mb-2 mt-3 @if($loop->first) mt-0 @endif">Uczestnik {{ $idx + 1 }} @if($p->is_primary)<span class="badge bg-primary">główny</span>@endif</p>
            <div class="locked-row"><span class="locked-label">Imię</span><span class="locked-value">{{ $p->participant_firstname ?: '—' }}</span></div>
            <div class="locked-row"><span class="locked-label">Nazwisko</span><span class="locked-value">{{ $p->participant_lastname ?: '—' }}</span></div>
            <div class="locked-row"><span class="locked-label">E-mail</span><span class="locked-value">{{ $p->participant_email ?: '—' }}</span></div>
        @empty
            <p class="text-muted mb-0">Brak zapisanych uczestników w tabeli powiązanej.</p>
        @endforelse
    </div>

    <div class="locked-order-card">
        <h2><i class="bi bi-info-circle me-2"></i>Inne</h2>
        <div class="locked-row"><span class="locked-label">Adres IP</span><span class="locked-value">{{ $order->ip_address ?: '—' }}</span></div>
        @if($order->fb_source)
        <div class="locked-row"><span class="locked-label">Źródło (fb / marketing)</span><span class="locked-value">{{ $order->fb_source }}</span></div>
        @endif
    </div>
</div>
@endsection
