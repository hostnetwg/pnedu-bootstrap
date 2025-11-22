@extends('layouts.app')

@section('title', 'Podsumowanie zamówienia - ' . $order->ident)

@push('styles')
<style>
    .order-summary-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(25, 118, 210, 0.1);
        padding: 2.5rem;
        margin-bottom: 2rem;
    }
    .success-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px 12px 0 0;
        text-align: center;
        margin: -2.5rem -2.5rem 2rem -2.5rem;
    }
    .success-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .success-header p {
        font-size: 1.1rem;
        margin-bottom: 0;
        opacity: 0.95;
    }
    .order-info-box {
        background: #f8f9fa;
        border-left: 4px solid #1976d2;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border-radius: 4px;
    }
    .order-info-box h3 {
        color: #1976d2;
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }
    .info-row {
        display: flex;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    .info-label {
        font-weight: 600;
        min-width: 150px;
        color: #495057;
    }
    .info-value {
        color: #212529;
    }
    .pdf-container {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        margin: 2rem 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .pdf-container iframe {
        width: 100%;
        height: 800px;
        border: none;
    }
    .action-buttons {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid #dee2e6;
    }
    .btn-download {
        background: #1976d2;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s;
    }
    .btn-download:hover {
        background: #1565c0;
        color: white;
    }
    .alert-info-custom {
        background: #e3f2fd;
        border-left: 4px solid #1976d2;
        padding: 1.5rem;
        border-radius: 4px;
        margin-top: 2rem;
    }
    .course-details-box {
        background: linear-gradient(135deg, #f4f7fa 0%, #e3e9f3 100%);
        padding: 1.5rem;
        border-radius: 8px;
        margin-top: 1rem;
    }
    .course-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="order-summary-card">
        <div class="success-header">
            <h1><i class="bi bi-check-circle-fill me-2"></i>PODSUMOWANIE ZŁOŻONEGO ZAMÓWIENIA</h1>
            <p>Dziękujemy za złożenie zamówienia!</p>
        </div>
                <div class="order-info-box">
                    <h3><i class="bi bi-info-circle me-2"></i>Informacje o zamówieniu</h3>
                    <div class="info-row">
                        <span class="info-label">Numer zamówienia:</span>
                        <span class="info-value"><strong>{{ $order->id }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Data złożenia:</span>
                        <span class="info-value">{{ $order->order_date->format('d.m.Y H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">E-mail kontaktowy:</span>
                        <span class="info-value">{{ $order->orderer_email }}</span>
                    </div>
                </div>

                <div class="course-details-box">
                    <div class="course-title"><i class="bi bi-book me-2"></i>Zamówione szkolenie</div>
                    <p class="mb-2"><strong>{{ str_replace('&nbsp;', ' ', strip_tags($order->product_name)) }}</strong></p>
                    @if($course && $course->start_date)
                        <p class="mb-2"><i class="bi bi-calendar-event me-2"></i>Data szkolenia: {{ $course->formatted_date }}</p>
                    @endif
                    @if($order->product_price)
                        <p class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Cena: <strong>{{ number_format($order->product_price, 2, ',', ' ') }} PLN (brutto)</strong></p>
                    @endif
                </div>

                <div class="alert-info-custom">
                    <h5 class="mb-3"><i class="bi bi-envelope-check me-2"></i>Dalsze kroki</h5>
                    @php
                        $isCoursePassed = false;
                        if ($course && $course->start_date) {
                            $courseDate = \Carbon\Carbon::parse($course->start_date);
                            $isCoursePassed = $courseDate->isPast();
                        }
                    @endphp
                    @if($isCoursePassed)
                        <p class="mb-2">Wkrótce prześlemy na podany adres e-mail (<strong>{{ $order->orderer_email }}</strong>):</p>
                    @else
                        <p class="mb-2">Dzień przed terminem szkolenia prześlemy na podany adres e-mail (<strong>{{ $order->orderer_email }}</strong>):</p>
                    @endif
                    <ul class="mb-2">
                        <li>Dane dostępowe do szkolenia</li>
                        <li>Fakturę z odroczonym terminem płatności</li>
                    </ul>
                    <p class="mb-0"><strong>Prosimy o zapisanie lub wydrukowanie poniższego dokumentu.</strong></p>
                </div>

                <h4 class="mt-4 mb-3"><i class="bi bi-file-pdf me-2"></i>Potwierdzenie zamówienia (PDF)</h4>
                
                @if($course)
                <div class="alert alert-warning border-warning mb-3" style="border-left-width: 4px;">
                    <h5 class="mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Sprawdź dane na zamówieniu w poniżej wygenerowanym dokumencie PDF</h5>
                    <p class="mb-2">Jeżeli zauważysz błąd, kliknij w <strong>"EDYTUJ"</strong> i dokonaj poprawki.</p>
                    <a href="{{ route('payment.deferred.edit', ['id' => $course->id, 'ident' => $order->ident]) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>EDYTUJ
                    </a>
                </div>
                @endif
                <div class="pdf-container">
                    <iframe src="{{ route('orders.pdf', ['ident' => $order->ident]) }}" title="Potwierdzenie zamówienia"></iframe>
                </div>

                <div class="action-buttons text-center">
                    <a href="{{ route('orders.pdf', ['ident' => $order->ident]) }}" class="btn btn-primary btn-lg btn-download" target="_blank">
                        <i class="bi bi-download me-2"></i>Pobierz PDF
                    </a>
                    @if($course)
                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-outline-secondary btn-lg ms-3">
                        <i class="bi bi-arrow-left me-2"></i>Powrót do szkolenia
                    </a>
                    @endif
                </div>
    </div>
</div>
@endsection

