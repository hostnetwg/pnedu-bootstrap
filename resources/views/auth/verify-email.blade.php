@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @php
                /** @var \App\Models\User $user */
                $user = auth()->user();
                $undeliverable = $user->hasUndeliverableEmail();
                $graceDays = (int) config('auth.unverified_account_grace_days', 90);
                $deletionDeadline = $user->unverifiedAccountDeletionDeadline();
                $protectedFromPurge = $user->isProtectedFromUnverifiedPurge();
            @endphp

            @if ($undeliverable)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-danger text-white fw-semibold">
                        <i class="bi bi-envelope-x me-1" aria-hidden="true"></i>
                        Adres e-mail niedostarczalny
                    </div>

                    <div class="card-body">
                        <p class="mb-3">
                            Wiadomość weryfikacyjna <strong>nie mogła zostać dostarczona</strong> na adres
                            <strong>{{ $user->email }}</strong>.
                            Najczęściej oznacza to literówkę, nieistniejącą skrzynkę lub odrzucenie wiadomości przez serwer pocztowy.
                        </p>

                        <p class="mb-4">
                            Aby aktywować konto, wpisz <strong>poprawny, działający adres e-mail</strong> w profilu.
                            Po zapisaniu automatycznie wyślemy nowy link weryfikacyjny.
                        </p>

                        @if ($protectedFromPurge)
                            <div class="alert alert-info small mb-4" role="alert">
                                Masz zapis na płatne szkolenie powiązany z tym adresem e-mail — konto
                                <strong>nie zostanie usunięte</strong>. Panel użytkownika wymaga jednak potwierdzenia poprawnego adresu.
                            </div>
                        @else
                            <div class="alert alert-warning small mb-4" role="alert">
                                <strong>Uwaga:</strong> jeśli nie poprawisz adresu i nie zweryfikujesz konta, zostanie ono usunięte
                                @if ($deletionDeadline)
                                    najpóźniej <strong>{{ $deletionDeadline->timezone(config('app.timezone'))->format('d.m.Y') }}</strong>
                                    ({{ $graceDays }} dni od rejestracji).
                                @else
                                    w ciągu {{ $graceDays }} dni od rejestracji.
                                @endif
                            </div>
                        @endif

                        <a href="{{ route('profile.edit') }}" class="btn btn-danger">
                            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                            Popraw adres e-mail w profilu
                        </a>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white fw-semibold">Weryfikacja adresu e-mail</div>

                    <div class="card-body">
                        <p class="mb-3">
                            Dziękujemy za rejestrację! Zanim zaczniesz korzystać z panelu użytkownika, potwierdź swój adres e-mail,
                            klikając link w wiadomości, którą właśnie wysłaliśmy na <strong>{{ $user->email }}</strong>.
                        </p>

                        @if ($protectedFromPurge)
                            <div class="alert alert-info small mb-4" role="alert">
                                Masz zapis na płatne szkolenie powiązany z tym adresem e-mail — konto <strong>nie zostanie usunięte</strong>.
                                Panel użytkownika wymaga jednak potwierdzenia adresu e-mail.
                            </div>
                        @else
                            <div class="alert alert-warning small mb-4" role="alert">
                                <strong>Uwaga:</strong> niezweryfikowane konto zostanie usunięte
                                @if ($deletionDeadline)
                                    najpóźniej <strong>{{ $deletionDeadline->timezone(config('app.timezone'))->format('d.m.Y') }}</strong>
                                    ({{ $graceDays }} dni od rejestracji).
                                @else
                                    w ciągu {{ $graceDays }} dni od rejestracji.
                                @endif
                            </div>
                        @endif

                        <p class="mb-3 text-muted small">
                            Nie dotarła wiadomość? Sprawdź folder spam, wyślij link ponownie lub
                            <a href="{{ route('profile.edit') }}">popraw adres e-mail w profilu</a>, jeśli zauważyłeś/aś literówkę.
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success small mb-3" role="alert">
                                Wysłaliśmy ponownie link weryfikacyjny na adres <strong>{{ $user->email }}</strong>.
                            </div>
                        @endif

                        <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-envelope-arrow-up me-1" aria-hidden="true"></i>
                                Wyślij link weryfikacyjny ponownie
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
