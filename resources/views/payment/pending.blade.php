@extends('layouts.app')

@section('title', 'Płatność w realizacji')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="order-summary-card">
                <div class="pending-header">
                    <h1><i class="bi bi-clock-history me-2"></i>Płatność w realizacji</h1>
                    <p>Twoje zamówienie jest w trakcie realizacji. Otrzymasz potwierdzenie na adres e-mail po zaksięgowaniu płatności.</p>
                </div>
                @if($order->course)
                    <div class="order-info-box">
                        <h3>Szkolenie</h3>
                        <div class="info-row">
                            <span class="info-label">Temat:</span>
                            <span class="info-value">{!! $order->course->title !!}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Data:</span>
                            <span class="info-value">{{ \Carbon\Carbon::parse($order->course->start_date)->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kwota:</span>
                            <span class="info-value">{{ number_format($order->total_amount, 2, ',', ' ') }} PLN</span>
                        </div>
                    </div>
                @endif
                <p class="text-muted mb-0">W razie pytań skontaktuj się z nami: kontakt@nowoczesna-edukacja.pl</p>
                <div class="mt-4">
                    <a href="{{ route('courses.show', $order->course_id) }}" class="btn btn-primary">Powrót do szczegółów szkolenia</a>
                    <a href="{{ route('home') }}" class="btn btn-secondary ms-2">Strona główna</a>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
.order-summary-card { background: white; border-radius: 12px; box-shadow: 0 4px 18px rgba(25,118,210,0.1); padding: 2.5rem; }
.pending-header { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); color: white; padding: 2rem; border-radius: 12px; text-align: center; margin: -2.5rem -2.5rem 2rem -2.5rem; }
.pending-header h1 { font-size: 2rem; font-weight: 700; }
.order-info-box { background: #f8f9fa; border-left: 4px solid #1976d2; padding: 1.5rem; margin-bottom: 2rem; border-radius: 4px; }
.info-row { margin-bottom: 0.75rem; }
.info-label { font-weight: 600; color: #495057; }
.info-value { color: #212529; }
</style>
@endpush
@endsection
