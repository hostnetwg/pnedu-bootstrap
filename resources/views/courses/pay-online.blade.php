@extends('layouts.app')

@section('title', 'Zapłać online – ' . $course->title)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="mb-4">Zapłać online za szkolenie</h1>
            <div class="mb-3">
                <strong>Szkolenie:</strong> {!! $course->title !!}<br>
                <strong>Data:</strong> {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}
            </div>
            <div class="alert alert-info mb-4">
                Tu powinien znaleźć się formularz lub integracja z systemem płatności online.<br>
                (np. Przelewy24, PayU, Stripe, itp.)
            </div>
            <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">Powrót do szczegółów szkolenia</a>
        </div>
    </div>
</div>
@endsection 