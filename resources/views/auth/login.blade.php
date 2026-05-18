@extends('layouts.guest')

@section('content')
{{-- Przy błędzie logowania: e-mail na czerwono jak hasło, ale bez ikonki SVG z .is-invalid (czytelniej dla użytkownika). --}}
<style>
    .form-control.login-email-invalid-border-only.is-invalid {
        background-image: none;
        padding-right: 0.75rem;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    @if (session('training_access_relogin'))
                        <div class="alert alert-warning" role="alert">
                            <strong>Ten link dotyczy innego konta.</strong>
                            Zostałeś/aś wylogowany/a, bo link do szkolenia z e-maila jest przypisany do innego adresu niż obecnie zalogowane konto.
                            @if (session('login_email_hint'))
                                Zaloguj się na adres <strong>{{ session('login_email_hint') }}</strong>
                                (ten sam, na który przyszła wiadomość o dostępie do materiałów).
                            @else
                                Zaloguj się na konto powiązane z adresem, na który wysłaliśmy e-mail o szkoleniu.
                            @endif
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        @error('credentials')
                            <div id="login-credentials-error" class="alert alert-danger" role="alert">
                                {{ $message }}
                            </div>
                        @enderror

                        @error('throttle')
                            <div id="login-throttle-error" class="alert alert-danger" role="alert">
                                {{ $message }}
                            </div>
                        @enderror

                        @php
                            $loginCredentialsFailed = $errors->has('credentials');
                            $loginEmailRuleFailed = $errors->has('email');
                            $loginEmailHighlighted = $loginEmailRuleFailed || $loginCredentialsFailed;
                            $loginEmailInvalidNoIcon = $loginCredentialsFailed && ! $loginEmailRuleFailed;
                        @endphp

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email"
                                    class="form-control @if($loginEmailHighlighted) is-invalid @endif @if($loginEmailInvalidNoIcon) login-email-invalid-border-only @endif"
                                    name="email"
                                    value="{{ old('email', session('login_email_hint')) }}"
                                    required
                                    autocomplete="email"
                                    autofocus
                                    @if($loginEmailRuleFailed)
                                        aria-invalid="true" aria-describedby="login-email-field-error"
                                    @elseif($loginCredentialsFailed)
                                        aria-invalid="true" aria-describedby="login-credentials-error"
                                    @endif>

                                @error('email')
                                    <div id="login-email-field-error" class="invalid-feedback d-block" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <div class="input-group @if($errors->has('password') || $errors->has('credentials')) has-validation @endif">
                                    <input id="password"
                                        type="password"
                                        class="form-control @if($errors->has('password') || $errors->has('credentials')) is-invalid @endif"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        @if($errors->has('credentials'))
                                            aria-invalid="true" aria-describedby="login-credentials-error"
                                        @elseif($errors->has('password'))
                                            aria-invalid="true" aria-describedby="login-password-field-error"
                                        @endif>

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="login-toggle-password"
                                        aria-label="Pokaż hasło"
                                        title="Pokaż hasło"
                                        aria-pressed="false">
                                        <i class="bi bi-eye" id="login-toggle-password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @error('password')
                                    @unless($errors->has('credentials'))
                                        <div id="login-password-field-error" class="invalid-feedback d-block" role="alert">
                                            {{ $message }}
                                        </div>
                                    @endunless
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Log in') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @endif
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
    const pwd = document.getElementById('password');
    const btn = document.getElementById('login-toggle-password');
    const icon = document.getElementById('login-toggle-password-icon');
    if (!pwd || !btn || !icon) {
        return;
    }
    btn.addEventListener('click', function () {
        const hidden = pwd.getAttribute('type') === 'password';
        pwd.setAttribute('type', hidden ? 'text' : 'password');
        icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        let label = hidden ? 'Ukryj hasło' : 'Pokaż hasło';
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
    });
});
</script>
@endpush
