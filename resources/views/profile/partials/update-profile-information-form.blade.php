<div class="card">
    <div class="card-header">{{ __('Informacje o profilu') }}</div>

    <div class="card-body">
        <form
            id="send-verification"
            class="d-none"
            method="post"
            action="{{ route('verification.send') }}"
        >
            @csrf
        </form>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <div class="row mb-3">
                <label for="first_name" class="col-md-4 col-form-label text-md-end">
                    {{ __('Imię') }}
                </label>

                <div class="col-md-6">
                    <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $user->first_name) }}" required autofocus autocomplete="given-name">

                    @error('first_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="last_name" class="col-md-4 col-form-label text-md-end">
                    {{ __('Nazwisko') }}
                </label>

                <div class="col-md-6">
                    <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name">

                    @error('last_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="birth_date" class="col-md-4 col-form-label text-md-end">
                    {{ __('Data urodzenia') }}
                </label>

                <div class="col-md-6">
                    <input id="birth_date" type="date" class="form-control @error('birth_date') is-invalid @enderror" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}" autocomplete="bday">

                    @error('birth_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="birth_place" class="col-md-4 col-form-label text-md-end">
                    {{ __('Miejsce urodzenia') }}
                </label>

                <div class="col-md-6">
                    <input id="birth_place" type="text" class="form-control @error('birth_place') is-invalid @enderror" name="birth_place" value="{{ old('birth_place', $user->birth_place) }}" autocomplete="bday-location">

                    @error('birth_place')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <label for="email" class="col-md-4 col-form-label text-md-end">
                    {{ __('E-mail') }}
                </label>

                <div class="col-md-6">
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">

                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2">
                            <p class="mb-0">
                                {{ __('Adres e-mail nie został zweryfikowany.') }}

                            <button form="send-verification" class="btn btn-link p-0">
                                {{ __('Kliknij tutaj, aby ponownie wysłać e-mail weryfikacyjny.') }}
                            </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <div class="alert alert-success mt-3 mb-0" role="alert">
                                    {{ __('Nowy link weryfikacyjny został wysłany na Twój adres e-mail.') }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Zapisz') }}
                    </button>
                    @if (session('status') === 'profile-updated')
                        <span class="m-1 fade-out">{{ __('Zapisano.') }}</span>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
