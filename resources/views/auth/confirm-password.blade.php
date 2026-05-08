@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Confirm Password') }}</div>

                <div class="card-body">
                    <div class="mb-3">
                        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                    </div>

                    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
                        @csrf

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <div class="input-group @error('password') has-validation @enderror">
                                    <input id="password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        @error('password') aria-invalid="true" aria-describedby="confirm-password-field-error" @enderror>

                                    <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="confirm-toggle-password"
                                        aria-label="Pokaż hasło"
                                        title="Pokaż hasło"
                                        aria-pressed="false">
                                        <i class="bi bi-eye" id="confirm-toggle-password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div id="confirm-password-field-error" class="invalid-feedback d-block" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-5 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Confirm') }}
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
    const pwd = document.getElementById('password');
    const btn = document.getElementById('confirm-toggle-password');
    const icon = document.getElementById('confirm-toggle-password-icon');
    if (!pwd || !btn || !icon) {
        return;
    }
    btn.addEventListener('click', function () {
        const hidden = pwd.getAttribute('type') === 'password';
        pwd.setAttribute('type', hidden ? 'text' : 'password');
        icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        const label = hidden ? 'Ukryj hasło' : 'Pokaż hasło';
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
    });
});
</script>
@endpush
