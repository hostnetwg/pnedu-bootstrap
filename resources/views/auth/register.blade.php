@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Register') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <p class="fw-semibold mb-2">Popraw następujące pola:</p>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <label for="first_name" class="col-md-4 col-form-label text-md-end">{{ __('Imię') }} <span class="text-danger">*</span></label>

                            <div class="col-md-6">
                                <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus>

                                @error('first_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="last_name" class="col-md-4 col-form-label text-md-end">{{ __('Nazwisko') }}</label>

                            <div class="col-md-6">
                                <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name">

                                @error('last_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email') }} <span class="text-danger">*</span></label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', request('email')) }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }} <span class="text-danger">*</span></label>

                            <div class="col-md-6">
                                <div class="input-group">
                                    <input id="password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        required
                                        autocomplete="new-password"
                                        @error('password') aria-invalid="true" aria-describedby="register-password-error" @enderror>

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="register-toggle-password"
                                        aria-label="Pokaż hasło"
                                        title="Pokaż hasło"
                                        aria-pressed="false">
                                        <i class="bi bi-eye" id="register-toggle-password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div id="register-password-error" class="invalid-feedback d-block" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>

                            <div class="col-md-6">
                                <div class="input-group">
                                    <input id="password-confirm"
                                        type="password"
                                        class="form-control @if($errors->has('password')) is-invalid @endif"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        @if($errors->has('password'))
                                            aria-invalid="true" aria-describedby="register-password-error"
                                        @endif>

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="register-toggle-password-confirm"
                                        aria-label="Pokaż potwierdzenie hasła"
                                        title="Pokaż potwierdzenie hasła"
                                        aria-pressed="false">
                                        <i class="bi bi-eye" id="register-toggle-password-confirm-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('rodo_consent') is-invalid @enderror" type="checkbox" name="rodo_consent" id="rodo_consent" value="1" {{ old('rodo_consent') ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="rodo_consent">
                                        Wyrażam zgodę na przetwarzanie moich danych osobowych w celu rejestracji konta i udziału w szkoleniach zgodnie z <a href="{{ route('rodo') }}" target="_blank">klauzulą informacyjną RODO</a> oraz <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
                                    </label>
                                    @error('rodo_consent')
                                        <div class="invalid-feedback d-block">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="newsletter_consent" id="newsletter_consent" value="1" {{ old('newsletter_consent') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="newsletter_consent">
                                        Wyrażam zgodę na otrzymywanie newslettera z materiałami edukacyjnymi i informacjami o nowych usługach (zgoda dobrowolna, można ją wycofać w każdej chwili).
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <a class="btn btn-link" href="{{ route('login') }}">
                                    {{ __('Already registered?') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function wirePasswordToggle(inputId, buttonId, iconId, labelShow, labelHide) {
        const pwd = document.getElementById(inputId);
        const btn = document.getElementById(buttonId);
        const icon = document.getElementById(iconId);
        if (!pwd || !btn || !icon) {
            return;
        }
        btn.addEventListener('click', function () {
            const hidden = pwd.getAttribute('type') === 'password';
            pwd.setAttribute('type', hidden ? 'text' : 'password');
            icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            const label = hidden ? labelHide : labelShow;
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        });
    }

    wirePasswordToggle(
        'password',
        'register-toggle-password',
        'register-toggle-password-icon',
        'Pokaż hasło',
        'Ukryj hasło'
    );
    wirePasswordToggle(
        'password-confirm',
        'register-toggle-password-confirm',
        'register-toggle-password-confirm-icon',
        'Pokaż potwierdzenie hasła',
        'Ukryj potwierdzenie hasła'
    );
});
</script>
@endpush
