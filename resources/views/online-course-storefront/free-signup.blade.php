@extends('layouts.app')

@php
    $loggedInUser = $loggedInUser ?? auth()->user();
@endphp

@section('title', 'Zapisz się bezpłatnie – '.$product->name.' | PNEDU')
@section('meta_description', 'Bezpłatny zapis na kurs online '.$product->name.'.')
@section('robots', 'noindex,nofollow')

@section('content')
<main class="container py-4 py-lg-5" style="max-width: 720px;">
    <nav aria-label="Okruszki">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.index') }}">Kursy</a></li>
            <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.show', $product->slug) }}">{{ $product->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Bezpłatny zapis</li>
        </ol>
    </nav>

    <header class="mb-4">
        <h1 class="h2">Zapisz się bezpłatnie</h1>
        <p class="lead mb-1">{{ $product->name }}</p>
        <p class="text-muted mb-0">{{ $price->name }} · {{ $price->accessLabel() }}</p>
        @if(filled($price->access_note))
            <p class="small text-muted mt-2 mb-0">{{ $price->access_note }}</p>
        @endif
    </header>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Sprawdź formularz:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($viewerAccess?->canEnter())
        <div class="alert alert-success">
            Masz już dostęp do tego kursu.
            <a href="{{ $viewerAccess->dashboardUrl() }}" class="alert-link">Przejdź do kursu</a>
        </div>
    @endif

    <form method="POST" action="{{ route('online-courses.free-signup.store', $product->slug) }}" class="border rounded-3 p-3 p-md-4 bg-white">
        @csrf
        <input type="hidden" name="product_price_id" value="{{ $price->id }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="freeFirstName">Imię</label>
                <input id="freeFirstName" name="first_name" class="form-control" required maxlength="255"
                       value="{{ old('first_name', $loggedInUser?->first_name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="freeLastName">Nazwisko</label>
                <input id="freeLastName" name="last_name" class="form-control" required maxlength="255"
                       value="{{ old('last_name', $loggedInUser?->last_name) }}">
            </div>
            <div class="col-md-7">
                <label class="form-label" for="freeEmail">E-mail do konta</label>
                <input id="freeEmail" type="email" name="email" class="form-control" required maxlength="255"
                       value="{{ old('email', $loggedInUser?->email) }}">
            </div>
            <div class="col-md-5">
                <label class="form-label" for="freePhone">Telefon <span class="text-muted fw-normal">(opcjonalnie)</span></label>
                <input id="freePhone" type="tel" name="phone" class="form-control" maxlength="50"
                       value="{{ old('phone', $loggedInUser?->phone) }}">
            </div>
        </div>

        <p class="small mt-4 mb-3">
            Zapisując się, zawierasz nieodpłatną umowę na warunkach
            <a href="{{ route('regulamin.version', $termsVersion) }}" target="_blank" rel="noopener">Regulaminu w wersji {{ $termsVersion }}</a>.
            Informacje o danych:
            <a href="{{ route('rodo') }}" target="_blank" rel="noopener">RODO</a>
            i
            <a href="{{ route('polityka-prywatnosci') }}" target="_blank" rel="noopener">Polityka prywatności</a>.
        </p>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <a href="{{ route('online-courses.catalog.show', $product->slug) }}" class="btn btn-outline-secondary">Wróć do oferty</a>
            <button type="submit" class="btn btn-success btn-lg">Zapisz się bezpłatnie</button>
        </div>
    </form>
</main>
@endsection
