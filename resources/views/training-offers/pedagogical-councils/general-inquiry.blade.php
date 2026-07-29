@extends('layouts.app')

@section('title', 'Zapytaj o szkolenie dla rady pedagogicznej - Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Masz pytanie o szkolenie dla rady pedagogicznej lub chcesz zaproponować temat? Napisz do nas — ustalimy formę i termin dopasowane do Twojej placówki.')
@section('canonical', route('training-offers.pedagogical-councils.inquiry.general'))

@section('content')
<section class="bg-primary bg-gradient text-white py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a class="text-white" href="{{ route('home') }}">Start</a></li>
                <li class="breadcrumb-item"><a class="text-white" href="{{ route('training-offers.pedagogical-councils.index') }}">Szkolenia rad pedagogicznych</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">Zapytaj o szkolenie</li>
            </ol>
        </nav>
        <div class="col-lg-8">
            <div class="badge bg-warning text-dark mb-3">Dla szkół, przedszkoli i placówek oświatowych</div>
            <h1 class="display-5 fw-bold mb-3">Zapytaj o szkolenie dla swojej placówki</h1>
            <p class="lead mb-0">
                Nie widzisz tematu, który Cię interesuje? Zaproponuj go albo opisz potrzeby swojej rady pedagogicznej. Odpiszemy i zaproponujemy możliwe rozwiązanie.
            </p>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h4 fw-bold mb-1">Formularz zapytania</h2>
                        <p class="text-muted mb-4">Wypełnij poniższy formularz — odpiszemy lub zadzwonimy, żeby omówić szczegóły.</p>

                        <form method="POST" action="{{ route('training-offers.pedagogical-councils.inquiry.general.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="inquiry_name" class="form-label">Imię i nazwisko <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="inquiry_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="inquiry_email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="inquiry_email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="inquiry_phone" class="form-label">Telefon</label>
                                    <input type="text" name="phone" id="inquiry_phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="inquiry_institution" class="form-label">Szkoła / placówka</label>
                                    <input type="text" name="institution" id="inquiry_institution" class="form-control @error('institution') is-invalid @enderror" value="{{ old('institution') }}">
                                    @error('institution')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="inquiry_offer_topic" class="form-label">Temat szkolenia, który Cię interesuje</label>
                                <input type="text" name="offer_topic" id="inquiry_offer_topic" class="form-control @error('offer_topic') is-invalid @enderror" value="{{ old('offer_topic') }}" placeholder="np. Radzenie sobie ze stresem w pracy nauczyciela">
                                <div class="form-text">Wpisz temat lub zostaw puste, jeśli wolisz opisać potrzeby w wiadomości.</div>
                                @error('offer_topic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="inquiry_preferred_format" class="form-label">Preferowana forma</label>
                                <select name="preferred_format" id="inquiry_preferred_format" class="form-select @error('preferred_format') is-invalid @enderror">
                                    <option value="">Wybierz, jeśli wiesz</option>
                                    <option value="online" {{ old('preferred_format') === 'online' ? 'selected' : '' }}>Online</option>
                                    <option value="onsite" {{ old('preferred_format') === 'onsite' ? 'selected' : '' }}>Stacjonarnie</option>
                                    <option value="to_discuss" {{ old('preferred_format') === 'to_discuss' ? 'selected' : '' }}>Do ustalenia</option>
                                </select>
                                @error('preferred_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="inquiry_message" class="form-label">Wiadomość <span class="text-danger">*</span></label>
                                <textarea name="message" id="inquiry_message" class="form-control @error('message') is-invalid @enderror" rows="5" required>{{ old('message') }}</textarea>
                                <div class="form-text">Opisz krótko potrzeby rady pedagogicznej lub zadaj pytanie.</div>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-check mb-4">
                                <input type="checkbox" name="consent" value="1" id="inquiry_consent" class="form-check-input @error('consent') is-invalid @enderror" {{ old('consent') ? 'checked' : '' }} required>
                                <label for="inquiry_consent" class="form-check-label small">
                                    Wyrażam zgodę na przetwarzanie danych w celu obsługi zapytania zgodnie z <a href="{{ route('polityka-prywatnosci') }}" target="_blank">Polityką prywatności</a>. <span class="text-danger">*</span>
                                </label>
                                @error('consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-warning fw-semibold px-4">
                                Wyślij zapytanie
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <aside class="col-lg-5">
                <div class="card border-0 bg-light mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Co możemy dla Ciebie zorganizować?</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex gap-2">
                                <i class="bi bi-check-circle-fill text-primary flex-shrink-0 mt-1"></i>
                                <span>Szkolenie z katalogu — dopasowane do terminu i formy Twojej placówki.</span>
                            </li>
                            <li class="mb-3 d-flex gap-2">
                                <i class="bi bi-check-circle-fill text-primary flex-shrink-0 mt-1"></i>
                                <span>Szkolenie na temat, którego nie widzisz w katalogu — wystarczy, że go zaproponujesz.</span>
                            </li>
                            <li class="d-flex gap-2">
                                <i class="bi bi-check-circle-fill text-primary flex-shrink-0 mt-1"></i>
                                <span>Pomoc w doborze tematu — jeśli wiesz, czego potrzebuje rada, ale nie wiesz jak to nazwać.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 bg-light">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Możesz też napisać bezpośrednio</h2>
                        <p class="text-muted mb-2">
                            <i class="bi bi-envelope me-2 text-primary"></i>kontakt@pnedu.pl
                        </p>
                        <p class="text-muted mb-0">
                            <i class="bi bi-telephone me-2 text-primary"></i>+48 501 654 274
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('training-offers.pedagogical-councils.index') }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-arrow-left me-1"></i> Wróć do katalogu szkoleń
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
