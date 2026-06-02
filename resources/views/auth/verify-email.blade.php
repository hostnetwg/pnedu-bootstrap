@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold">Weryfikacja adresu e-mail</div>

                <div class="card-body">
                    <p class="mb-3">
                        Dziękujemy za rejestrację! Zanim zaczniesz korzystać z panelu użytkownika, potwierdź swój adres e-mail,
                        klikając link w wiadomości, którą właśnie wysłaliśmy na <strong>{{ auth()->user()->email }}</strong>.
                    </p>

                    @php
                        $graceDays = (int) config('auth.unverified_account_grace_days', 14);
                        $deletionDeadline = auth()->user()->unverifiedAccountDeletionDeadline();
                    @endphp
                    <div class="alert alert-warning small mb-4" role="alert">
                        <strong>Uwaga:</strong> konta z niezweryfikowanym adresem e-mail zostaną usunięte
                        @if ($deletionDeadline)
                            najpóźniej <strong>{{ $deletionDeadline->timezone(config('app.timezone'))->format('d.m.Y') }}</strong>
                            ({{ $graceDays }} dni od rejestracji).
                        @else
                            w ciągu {{ $graceDays }} dni od rejestracji.
                        @endif
                    </div>

                    <p class="mb-3 text-muted small">
                        Nie dotarła wiadomość? Sprawdź folder spam lub wyślij link ponownie — możesz też użyć przycisku
                        w żółtym pasku u góry strony.
                    </p>

                    <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            Wyślij link weryfikacyjny ponownie
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
