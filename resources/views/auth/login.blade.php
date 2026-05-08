@extends('layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        @error('email')
                            <div id="login-auth-error" class="alert alert-danger" role="alert">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    autofocus
                                    @error('email') aria-invalid="true" aria-describedby="login-auth-error" @enderror>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <div class="input-group">
                                    <input id="password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>

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
                                    <div id="password-error" class="invalid-feedback d-block" role="alert">
                                        {{ $message }}
                                    </div>
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
