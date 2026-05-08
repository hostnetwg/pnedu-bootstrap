@php
    $updatePwBag = $errors->getBag('updatePassword');
@endphp

<div class="card">
    <div class="card-header">{{ __('Zaktualizuj hasło') }}</div>

    <div class="card-body">
        @if (session('status') === 'password-updated')
            <div class="alert alert-success" role="alert">
                {{ __('Hasło zostało zmienione.') }}
            </div>
        @endif

        <div class="mb-3">
            {{ __('Upewnij się, że Twoje konto używa długiego, losowego hasła, aby zachować bezpieczeństwo.') }}
        </div>
        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            @method('put')

            <div class="row mb-3">
                <label for="current_password" class="col-md-4 col-form-label text-md-end">
                    {{ __('Aktualne hasło') }}
                </label>

                <div class="col-md-6">
                    <div class="input-group @error('current_password', 'updatePassword') has-validation @enderror">
                        <input id="current_password"
                            type="password"
                            class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                            name="current_password"
                            required
                            autocomplete="current-password"
                            @error('current_password', 'updatePassword') aria-invalid="true" aria-describedby="profile-current-password-error" @enderror>

                        <button type="button"
                            class="btn btn-outline-secondary"
                            id="profile-toggle-current-password"
                            aria-label="Pokaż aktualne hasło"
                            title="Pokaż aktualne hasło"
                            aria-pressed="false">
                            <i class="bi bi-eye" id="profile-toggle-current-password-icon" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('current_password', 'updatePassword')
                        <div id="profile-current-password-error" class="invalid-feedback d-block" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="password" class="col-md-4 col-form-label text-md-end">
                    {{ __('Nowe hasło') }}
                </label>

                <div class="col-md-6">
                    <div class="input-group @error('password', 'updatePassword') has-validation @enderror">
                        <input id="password"
                            type="password"
                            class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                            name="password"
                            required
                            autocomplete="new-password"
                            @error('password', 'updatePassword') aria-invalid="true" aria-describedby="profile-new-password-error" @enderror>

                        <button type="button"
                            class="btn btn-outline-secondary"
                            id="profile-toggle-new-password"
                            aria-label="Pokaż nowe hasło"
                            title="Pokaż nowe hasło"
                            aria-pressed="false">
                            <i class="bi bi-eye" id="profile-toggle-new-password-icon" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('password', 'updatePassword')
                        <div id="profile-new-password-error" class="invalid-feedback d-block" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="password_confirmation" class="col-md-4 col-form-label text-md-end">
                    {{ __('Potwierdź hasło') }}
                </label>

                <div class="col-md-6">
                    <div class="input-group @if($updatePwBag->has('password') || $updatePwBag->has('password_confirmation')) has-validation @endif">
                        <input id="password_confirmation"
                            type="password"
                            class="form-control @if($updatePwBag->has('password') || $updatePwBag->has('password_confirmation')) is-invalid @endif"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            @if($updatePwBag->has('password'))
                                aria-invalid="true" aria-describedby="profile-new-password-error"
                            @elseif($updatePwBag->has('password_confirmation'))
                                aria-invalid="true" aria-describedby="profile-password-confirmation-error"
                            @endif>

                        <button type="button"
                            class="btn btn-outline-secondary"
                            id="profile-toggle-password-confirmation"
                            aria-label="Pokaż potwierdzenie hasła"
                            title="Pokaż potwierdzenie hasła"
                            aria-pressed="false">
                            <i class="bi bi-eye" id="profile-toggle-password-confirmation-icon" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('password_confirmation', 'updatePassword')
                        <div id="profile-password-confirmation-error" class="invalid-feedback d-block" role="alert">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Zapisz') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

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
        'current_password',
        'profile-toggle-current-password',
        'profile-toggle-current-password-icon',
        'Pokaż aktualne hasło',
        'Ukryj aktualne hasło'
    );
    wirePasswordToggle(
        'password',
        'profile-toggle-new-password',
        'profile-toggle-new-password-icon',
        'Pokaż nowe hasło',
        'Ukryj nowe hasło'
    );
    wirePasswordToggle(
        'password_confirmation',
        'profile-toggle-password-confirmation',
        'profile-toggle-password-confirmation-icon',
        'Pokaż potwierdzenie hasła',
        'Ukryj potwierdzenie hasła'
    );
});
</script>
@endpush
