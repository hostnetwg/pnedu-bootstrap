@auth
    @php
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
    @endphp
    @if ($authUser instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $authUser->hasVerifiedEmail())
        @php
            $graceDays = (int) config('auth.unverified_account_grace_days', 14);
            $deletionDeadline = $authUser->unverifiedAccountDeletionDeadline();
        @endphp
        <div class="border-bottom border-warning border-3 bg-warning-subtle" role="alert" aria-live="polite">
            <div class="container py-3">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                    <div class="flex-grow-1">
                        <p class="mb-1 fw-semibold text-dark">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-1" aria-hidden="true"></i>
                            Zweryfikuj swój adres e-mail
                        </p>
                        <p class="mb-1 small text-dark">
                            Kliknij link w wiadomości wysłanej na adres
                            <strong>{{ $authUser->email }}</strong>, aby aktywować konto i korzystać z panelu użytkownika.
                        </p>
                        <p class="mb-0 small text-danger fw-semibold">
                            Uwaga: konta z niezweryfikowanym adresem e-mail zostaną usunięte
                            @if ($deletionDeadline)
                                najpóźniej <strong>{{ $deletionDeadline->timezone(config('app.timezone'))->format('d.m.Y') }}</strong>
                                ({{ $graceDays }} dni od rejestracji).
                            @else
                                w ciągu {{ $graceDays }} dni od rejestracji.
                            @endif
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <p class="mb-0 mt-2 small text-success fw-semibold">
                                Wysłaliśmy ponownie link weryfikacyjny na Twój adres e-mail.
                            </p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                                <i class="bi bi-envelope-arrow-up me-1" aria-hidden="true"></i>
                                Wyślij link ponownie
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
