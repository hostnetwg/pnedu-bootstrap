@extends('layouts.app')

@section('title', $course->title . ' – Szczegóły szkolenia')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="mb-4">{{ $course->title }}</h1>
            <div class="mb-3">
                <strong>Data:</strong> {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}<br>
                @if($course->end_date)
                    <strong>Koniec:</strong> {{ \Carbon\Carbon::parse($course->end_date)->format('d.m.Y H:i') }}<br>
                @endif
                <strong>Trener:</strong> {{ $course->trainer }}<br>
            </div>
            @if(!empty($course->image))
                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" class="img-fluid rounded mb-4" alt="{{ $course->title }}">
            @endif
            <div class="mb-4">
                <h4>Opis szkolenia</h4>
                <p>{{ $course->description }}</p>
            </div>
            <div class="mb-5">
                <h3 class="mb-3">Wybierz formę płatności</h3>
                <div class="d-flex flex-column flex-md-row gap-3">
                    <a href="{{ route('payment.online', $course->id) }}" class="btn btn-success btn-lg flex-fill">Zapłać online</a>
                    <a href="{{ route('payment.deferred', $course->id) }}" class="btn btn-outline-primary btn-lg flex-fill">Formularz zamówienia z odroczonym terminem płatności</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 