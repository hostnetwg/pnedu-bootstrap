@extends('layouts.app')

@section('title', 'Brak dostępu – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <h1 class="h4 mb-3">Ten link nie pasuje do zalogowanego konta</h1>
                    <p class="text-muted mb-4">
                        Link do zaświadczeń został wysłany na inny adres e-mail niż konto, na które jesteś obecnie zalogowany.
                        Wyloguj się lub otwórz link w oknie prywatnym, jeśli chcesz skorzystać z tej wiadomości.
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary me-2">Panel konta</a>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Strona główna</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
