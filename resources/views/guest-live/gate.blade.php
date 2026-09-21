@extends('layouts.certificate-registration')

@section('title', 'Wejście na szkolenie – ' . ($courseTitle ?? 'Platforma Nowoczesnej Edukacji'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2 text-center">Wejście na szkolenie</h1>
                    <p class="fs-5 fw-semibold text-dark text-center mb-1">„{{ $courseTitle }}”</p>
                    <p class="text-center text-muted small mb-4">
                        Podaj imię, nazwisko i e-mail, aby wejść na szkolenie.
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('guest-live.register', ['token' => $token]) }}" method="post" autocomplete="on">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name') }}" required maxlength="255" autocomplete="given-name">
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name') }}" required maxlength="255" autocomplete="family-name">
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adres e-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-danger">
                                Podaj <strong>swój indywidualny adres e-mail</strong> — nie wspólnej skrzynki typu sekretariat@, szkola@.
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('rodo_consent') is-invalid @enderror"
                                       type="checkbox" name="rodo_consent" id="rodo_consent" value="1"
                                       {{ old('rodo_consent') ? 'checked' : '' }} required>
                                <label class="form-check-label small" for="rodo_consent">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu listy obecności i udziału w szkoleniu,
                                    zgodnie z <a href="{{ route('rodo') }}" target="_blank" rel="noopener">klauzulą RODO</a>
                                    i <a href="{{ route('polityka-prywatnosci') }}" target="_blank" rel="noopener">Polityką prywatności</a>.
                                    <span class="text-danger">*</span>
                                </label>
                                @error('rodo_consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Wejdź na szkolenie</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
