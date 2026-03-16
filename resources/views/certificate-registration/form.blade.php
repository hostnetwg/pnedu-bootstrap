@extends('layouts.app')

@section('title', 'Rejestracja zaświadczenia – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3" style="background-color:#f3f4f6;">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2 text-center text-uppercase text-primary">Rejestracja zaświadczenia</h1>
                    <p class="fs-5 fw-semibold text-dark text-center {{ !empty($instructorName) ? 'mb-2' : 'mb-4' }}">
                        „{{ $courseTitle }}”
                    </p>
                    @if(!empty($instructorName))
                        <p class="text-muted mb-4 d-flex align-items-center">
                            @if(!empty($instructorPhoto))
                                <img src="{{ 'https://adm.pnedu.pl/storage/' . $instructorPhoto }}" alt="{{ $instructorName }}" class="rounded me-2" style="max-width: 48px; height: auto;">
                            @endif
                            <span><strong>Prowadzący:</strong> {{ $instructorName }}</span>
                        </p>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('certificate-registration.submit', $token) }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required maxlength="255">
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required maxlength="255">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adres e-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-danger">
                                Podaj <strong>swój indywidualny adres e-mail</strong>, na który otrzymasz zaświadczenie – nie rejestruj kilku osób na ten sam adres ani na wspólne skrzynki typu sekretariat@, szkola@ itp.
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('rodo_consent') is-invalid @enderror" type="checkbox" name="rodo_consent" id="rodo_consent" value="1" {{ old('rodo_consent') ? 'checked' : '' }} required>
                                <label class="form-check-label small" for="rodo_consent">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu rejestracji i wydania zaświadczenia zgodnie z przepisami MEN (rejestr zaświadczeń) oraz <a href="{{ route('rodo') }}" target="_blank">klauzulą RODO</a> i <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
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
                        <button type="submit" class="btn btn-primary w-100">Zarejestruj się</button>
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
