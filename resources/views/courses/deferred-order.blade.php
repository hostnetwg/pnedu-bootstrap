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
    .order-form-section label .text-danger {
        margin-left: 2px;
        font-size: 1.1em;
        vertical-align: middle;
    }
    .order-form-section .form-label {
        font-weight: 500;
    }
    .order-form-section input,
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
        margin-bottom: 2.7rem;
    }
    .course-title-section {
        background: linear-gradient(135deg, #e3f2fd 60%, #f3e5f5 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 2px solid #bbdefb;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.1);
    }
    .course-title-section .course-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 0.5rem;
        line-height: 1.3;
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
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="mb-4 text-center">Formularz zamówienia z&nbsp;odroczonym terminem płatności</h1>
            <div class="course-title-section text-center">
                <div class="course-title">{{ $course->title }}</div>
                <div class="course-date">Data: {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}</div>
                @if(!empty($course->trainer))
                    <div class="course-trainer">{{ $course->trainer_title }}: {{ $course->trainer }}</div>
                @endif
            </div>
            <form method="POST" action="#">
                @csrf
                <fieldset class="order-form-section">
                    <legend>NABYWCA (dane do faktury)</legend>
                    <div class="mb-3">
                        <label for="buyer_name" class="form-label">Nazwa nabywcy <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="buyer_name" name="buyer_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="buyer_address" class="form-label">Adres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="buyer_address" name="buyer_address" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="buyer_postcode" class="form-label">Kod pocztowy <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="buyer_postcode" name="buyer_postcode" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="buyer_city" class="form-label">Poczta / Miasto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="buyer_city" name="buyer_city" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="buyer_nip" class="form-label">NIP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="buyer_nip" name="buyer_nip" required>
                    </div>
                </fieldset>
                <fieldset class="order-form-section">
                    <legend>ODBIORCA (opcjonalnie, jeśli inny niż nabywca)</legend>
                    <div class="mb-3">
                        <label for="recipient_name" class="form-label">Nazwa odbiorcy</label>
                        <input type="text" class="form-control" id="recipient_name" name="recipient_name">
                    </div>
                    <div class="mb-3">
                        <label for="recipient_address" class="form-label">Adres</label>
                        <input type="text" class="form-control" id="recipient_address" name="recipient_address">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient_postcode" class="form-label">Kod pocztowy</label>
                            <input type="text" class="form-control" id="recipient_postcode" name="recipient_postcode">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="recipient_city" class="form-label">Poczta / Miasto</label>
                            <input type="text" class="form-control" id="recipient_city" name="recipient_city">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="recipient_nip" class="form-label">NIP</label>
                        <input type="text" class="form-control" id="recipient_nip" name="recipient_nip" placeholder="Wypełnij jeżeli wymagane">
                    </div>
                </fieldset>
                <fieldset class="order-form-section">
                    <legend>DANE KONTAKTOWE ZAMAWIAJĄCEGO</legend>
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Telefon kontaktowy <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">E-mail do przesłania faktury <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                    </div>
                </fieldset>
                <fieldset class="order-form-section">
                    <legend>DANE UCZESTNIKA SZKOLENIA</legend>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="participant_first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="participant_first_name" name="participant_first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="participant_last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="participant_last_name" name="participant_last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="participant_email" class="form-label">E-mail uczestnika <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="participant_email" name="participant_email" required placeholder="na ten adres zostaną przesłane dane dostępowe do szkolenia">
                    </div>
                </fieldset>
                <div class="order-form-section">
                    <div class="mb-3">
                        <label for="invoice_notes" class="form-label">Uwagi do faktury (opcjonalnie)</label>
                        <textarea class="form-control" id="invoice_notes" name="invoice_notes" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="payment_terms" class="form-label">Termin płatności (dni) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="payment_terms" name="payment_terms" value="14" min="1" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                        <label class="form-check-label" for="consent">Wyrażam zgodę na przetwarzanie danych osobowych zgodnie z polityką prywatności. <span class="text-danger">*</span></label>
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-3 mt-4">
                        <button type="submit" class="btn btn-primary flex-fill">Wyślij zamówienie</button>
                        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-link flex-fill">Powrót do szczegółów szkolenia</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 