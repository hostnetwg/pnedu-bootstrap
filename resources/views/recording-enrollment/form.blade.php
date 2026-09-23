@extends('layouts.certificate-registration')

@section('title', 'Dostęp do nagrania – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3" style="background-color:#f3f4f6;">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2 text-center text-uppercase text-primary">
                        Dostęp do nagrania i zaświadczenia
                    </h1>
                    <p class="fs-5 fw-semibold text-dark text-center {{ !empty($instructorName) ? 'mb-2' : 'mb-3' }}">
                        „{{ $courseTitle }}”
                    </p>
                    @if(!empty($courseStartDisplay))
                        <p class="text-center text-muted small fw-bold mb-2">Data szkolenia: <span class="text-body">{{ $courseStartDisplay }}</span></p>
                    @endif
                    @if(!empty($enrollmentEndsDisplay))
                        <p class="text-center text-danger mb-2" style="font-size:0.72rem;">
                            Formularz dostępny do: {{ $enrollmentEndsDisplay }}
                        </p>
                    @endif
                    @if(!empty($instructorName))
                        <p class="text-muted mb-3 d-flex align-items-center">
                            @if(!empty($instructorPhoto))
                                <img src="{{ \App\Support\PneadmMedia::url($instructorPhoto) }}" alt="{{ $instructorName }}" class="rounded me-2" style="max-width: 48px; height: auto;">
                            @endif
                            <span><strong>Prowadzący:</strong> {{ $instructorName }}</span>
                        </p>
                    @endif
                    <p class="small text-muted mb-4">
                        Ustaw hasło dostępu do nagrania.
                        Po wysłaniu formularza dopiszemy Cię do szkolenia i utworzymy konto na pnedu.pl.
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('recording-enrollment.submit', $token) }}" method="post" autocomplete="on">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required maxlength="255" autocomplete="given-name">
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required maxlength="255" autocomplete="family-name">
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adres e-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-danger">
                                Podaj <strong>swój indywidualny adres e-mail</strong>. Nie używaj wspólnej skrzynki typu sekretariat@ albo szkola@.
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Hasło do nagrania <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Potwierdź hasło <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password">
                            </div>
                        </div>
                        <p class="form-text mb-3">
                            Jeśli konto na ten adres już jest, hasła nie zmieniamy. Zalogujesz się dotychczasowym.
                        </p>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('rodo_consent') is-invalid @enderror" type="checkbox" name="rodo_consent" id="rodo_consent" value="1" {{ old('rodo_consent') ? 'checked' : '' }} required>
                                <label class="form-check-label small" for="rodo_consent">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu utworzenia konta na pnedu.pl, udostępnienia nagrania i materiałów oraz wydania zaświadczenia, zgodnie z <a href="{{ route('rodo') }}" target="_blank">klauzulą RODO</a> i <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
                                </label>
                                @error('rodo_consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="newsletter_consent" id="newsletter_consent" value="1" {{ old('newsletter_consent') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="newsletter_consent">
                                    Wyrażam zgodę na otrzymywanie newslettera z materiałami edukacyjnymi i informacjami o nowych usługach (zgoda dobrowolna, można ją wycofać w każdej chwili).
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Ustaw hasło i zapisz dostęp</button>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted mt-3 small">
                <a href="{{ route('home') }}" class="text-decoration-none">← Powrót na stronę główną</a>
            </p>
        </div>
    </div>
</div>
@endsection
