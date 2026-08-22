@extends('layouts.guest')

@section('robots', 'noindex, nofollow')
@section('title', $isInitialPasswordSetup
    ? 'Ustaw hasło — Platforma Nowoczesnej Edukacji'
    : 'Resetowanie hasła — Platforma Nowoczesnej Edukacji')
@section('meta_description', $isInitialPasswordSetup
    ? 'Ustaw hasło do konta na pnedu.pl utworzonego po zapisie na szkolenie.'
    : 'Ustaw nowe hasło do konta na pnedu.pl.')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h5 mb-0">{{ $isInitialPasswordSetup ? __('Set Password') : __('Reset Password') }}</h1>
                </div>

                <div class="card-body">
                    @if($isInitialPasswordSetup)
                        <p class="text-muted mb-4">{{ __('Set your password to sign in after we created your account for the training.') }}</p>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}" novalidate>
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        @if($isInitialPasswordSetup)
                            <input type="hidden" name="intent" value="set">
                        @endif
                        @if(! empty($redirectAfterSetup))
                            <input type="hidden" name="redirect" value="{{ $redirectAfterSetup }}">
                        @endif

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email" autofocus
                                    @error('email') aria-invalid="true" aria-describedby="reset-password-email-error" @enderror>

                                @error('email')
                                    <div id="reset-password-email-error" class="invalid-feedback d-block" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <div class="input-group @error('password') has-validation @enderror">
                                    <input id="password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        required
                                        autocomplete="new-password"
                                        @error('password') aria-invalid="true" aria-describedby="reset-password-password-error" @enderror>

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="reset-toggle-password"
                                        aria-label="Pokaż hasło"
                                        title="Pokaż hasło"
                                        aria-pressed="false">
                                        <i class="bi bi-eye" id="reset-toggle-password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div id="reset-password-password-error" class="invalid-feedback d-block" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <div class="input-group @if($errors->has('password')) has-validation @endif">
                                    <input id="password-confirm"
                                        type="password"
                                        class="form-control @if($errors->has('password')) is-invalid @endif"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        @if($errors->has('password')) aria-invalid="true" aria-describedby="reset-password-password-error" @endif>

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="reset-toggle-password-confirm"
                                        aria-label="Pokaż potwierdzenie hasła"
                                        title="Pokaż potwierdzenie hasła"
                                        aria-pressed="false">
                                        <i class="bi bi-eye" id="reset-toggle-password-confirm-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ $isInitialPasswordSetup ? __('Set Password') : __('Reset Password') }}
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
        'reset-toggle-password',
        'reset-toggle-password-icon',
        'Pokaż hasło',
        'Ukryj hasło'
    );
    wirePasswordToggle(
        'password-confirm',
        'reset-toggle-password-confirm',
        'reset-toggle-password-confirm-icon',
        'Pokaż potwierdzenie hasła',
        'Ukryj potwierdzenie hasła'
    );
});
</script>
@endpush
