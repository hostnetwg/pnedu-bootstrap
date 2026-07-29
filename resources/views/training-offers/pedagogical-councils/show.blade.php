@extends('layouts.app')

@section('title', $offer->seoTitle().' - Platforma Nowoczesnej Edukacji')
@section('meta_description', $offer->seoDescription())
@section('canonical', route('training-offers.pedagogical-councils.show', $offer->slug))

@section('content')
<style>
    .training-offer-sticky-cta {
        position: static;
    }

    @media (min-width: 992px) {
        .training-offer-sticky-cta {
            position: sticky;
            top: 5.5rem;
            z-index: 2;
        }
    }

    .training-offer-mobile-cta {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1030;
        background: rgba(255, 255, 255, 0.97);
        border-top: 1px solid #dee2e6;
        box-shadow: 0 -4px 18px rgba(0, 0, 0, 0.08);
        padding: 0.75rem 1rem;
    }

    @media (min-width: 992px) {
        .training-offer-mobile-cta {
            display: none !important;
        }
    }

    body.training-offer-detail-page {
        padding-bottom: 0;
    }

    @media (max-width: 991.98px) {
        body.training-offer-detail-page {
            padding-bottom: 5.5rem;
        }
    }
</style>

<section class="bg-primary bg-gradient text-white py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a class="text-white" href="{{ route('home') }}">Start</a></li>
                <li class="breadcrumb-item"><a class="text-white" href="{{ route('training-offers.pedagogical-councils.index') }}">Szkolenia rad pedagogicznych</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $offer->title }}</li>
            </ol>
        </nav>

        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="badge bg-warning text-dark mb-3">Szkolenie dla rady pedagogicznej</div>
                <h1 class="display-5 fw-bold mb-3">{{ $offer->title }}</h1>
                @if($offer->summary)
                    <p class="lead mb-0">{{ $offer->summary }}</p>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-dark">
                    <div class="card-body">
                        <h2 class="h5 fw-bold">Chcesz zorganizować to szkolenie?</h2>
                        <p class="text-muted">
                            Napisz do nas, a ustalimy termin, formę realizacji i zakres dopasowany do potrzeb Twojej rady pedagogicznej.
                        </p>
                        <a href="#zapytanie-o-szkolenie" class="btn btn-warning w-100 fw-semibold">
                            Zapytaj o termin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if($offer->publicImageUrl())
                    <img src="{{ $offer->publicImageUrl() }}"
                         alt="{{ $offer->title }}"
                         class="img-fluid rounded shadow-sm mb-4"
                         loading="lazy">
                @endif

                @if($offer->summary)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h4 fw-bold mb-3">O szkoleniu</h2>
                            <p class="lead mb-0">{{ $offer->summary }}</p>
                        </div>
                    </div>
                @else
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h4 fw-bold mb-3">O szkoleniu</h2>
                            <p class="lead mb-0">
                                To propozycja szkolenia dla rady pedagogicznej, którą możemy dopasować do potrzeb Twojej szkoły, przedszkola lub placówki oświatowej.
                            </p>
                        </div>
                    </div>
                @endif

                @if($offer->description_html)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h4 fw-bold mb-3">Pełny opis szkolenia</h2>
                            <div class="training-offer-description">
                                {!! $offer->description_html !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if($offer->scope)
                    <div class="card border-0 bg-light mb-4">
                        <div class="card-body p-4">
                            <h2 class="h4 fw-bold mb-3">Proponowany zakres szkolenia</h2>
                            <div style="white-space: pre-line;">{{ $offer->scope }}</div>
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 fw-bold mb-3">Jak możemy zrealizować szkolenie?</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="h-100 p-3 bg-light rounded">
                                    <div class="fw-semibold mb-1"><i class="bi bi-calendar-check me-2 text-primary"></i>Termin</div>
                                    <p class="small text-muted mb-0">Ustalany indywidualnie z placówką.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="h-100 p-3 bg-light rounded">
                                    <div class="fw-semibold mb-1"><i class="bi bi-camera-video me-2 text-primary"></i>Forma</div>
                                    <p class="small text-muted mb-0">Online albo stacjonarnie, zależnie od potrzeb.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="h-100 p-3 bg-light rounded">
                                    <div class="fw-semibold mb-1"><i class="bi bi-people me-2 text-primary"></i>Zakres</div>
                                    <p class="small text-muted mb-0">Możliwy do dopasowania do rady pedagogicznej.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" id="zapytanie-o-szkolenie">
                    <div class="card-body p-4">
                        <h2 class="h4 fw-bold mb-3">Zapytaj o termin i szczegóły</h2>
                        <p class="text-muted mb-4">
                            Napisz kilka zdań o potrzebach placówki. Oddzwonimy lub odpiszemy, żeby ustalić termin i warunki realizacji.
                        </p>
                        <form method="POST" action="{{ route('training-offers.pedagogical-councils.inquiry.store', $offer->slug) }}">
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
                                <textarea name="message" id="inquiry_message" class="form-control @error('message') is-invalid @enderror" rows="5" required>{{ old('message', 'Dzień dobry, interesuje mnie szkolenie „'.$offer->title.'”. Proszę o kontakt w sprawie możliwego terminu i warunków realizacji.') }}</textarea>
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

            <aside class="col-lg-4">
                <div class="training-offer-sticky-cta">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Informacje o ofercie</h2>
                            <ul class="list-unstyled mb-0">
                                @if($offer->audience)
                                    <li class="mb-3">
                                        <div class="small text-muted">Dla kogo</div>
                                        <div class="fw-semibold">{{ $offer->audience }}</div>
                                    </li>
                                @endif
                                <li class="mb-3">
                                    <div class="small text-muted">Cena</div>
                                    <div class="fw-semibold">{{ $offer->formattedPrice() }}</div>
                                </li>
                                @if($offer->instructor)
                                    <li>
                                        <div class="small text-muted">Prowadzący</div>
                                        <div class="d-flex align-items-center gap-3 mt-2">
                                            @if($offer->instructor->publicPhotoUrl())
                                                <img src="{{ $offer->instructor->publicPhotoUrl() }}"
                                                     alt="{{ $offer->instructor->full_name }}"
                                                     class="rounded-circle flex-shrink-0"
                                                     width="64"
                                                     height="64"
                                                     style="object-fit: cover;"
                                                     loading="lazy">
                                            @else
                                                <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0"
                                                     style="width: 64px; height: 64px;">
                                                    <i class="bi bi-person fs-4"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $offer->instructor->full_name_with_title }}</div>
                                                @if($offer->instructor->bio)
                                                    <div class="small text-muted">
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($offer->instructor->bio), 130) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <div class="card border-primary shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold">Szkolenie dla Twojej placówki</h2>
                            <p class="text-muted mb-3">
                                Możemy dopasować termin, formę i zakres do potrzeb Twojej rady pedagogicznej.
                            </p>
                            <a href="#zapytanie-o-szkolenie" class="btn btn-warning w-100 fw-semibold">
                                Zapytaj o termin
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<div class="training-offer-mobile-cta d-lg-none">
    <div class="container">
        <a href="#zapytanie-o-szkolenie" class="btn btn-warning w-100 fw-semibold">
            Zapytaj o termin
        </a>
    </div>
</div>

<script>
    document.body.classList.add('training-offer-detail-page');
</script>
@endsection
