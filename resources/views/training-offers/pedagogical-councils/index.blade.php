@extends('layouts.app')

@section('title', 'Szkolenia rad pedagogicznych - Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Szkolenia dla rad pedagogicznych, szkół i przedszkoli. Wybierz temat dla swojej placówki i skontaktuj się z Platformą Nowoczesnej Edukacji, aby ustalić termin i formę szkolenia.')

@section('content')
<section class="bg-primary bg-gradient text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="badge bg-warning text-dark mb-3">Dla szkół, przedszkoli i placówek oświatowych</div>
                <h1 class="display-5 fw-bold mb-3">Szkolenia rad pedagogicznych</h1>
                <p class="lead mb-0">
                    Praktyczne szkolenia dla całej rady pedagogicznej, przygotowywane z myślą o realnych potrzebach szkoły lub przedszkola. Wybierz temat, a my wspólnie ustalimy termin, formę realizacji i szczegółowy zakres szkolenia.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <h2 class="h3 fw-bold mb-3">Wybierz temat szkolenia dla swojej placówki</h2>
                <p class="text-muted mb-0">
                    Poniższe propozycje możemy zrealizować jako szkolenie zamknięte dla konkretnej szkoły, przedszkola lub placówki. Program, czas trwania i forma realizacji mogą zostać dopasowane do potrzeb rady pedagogicznej.
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('training-offers.pedagogical-councils.index') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-9">
                <label for="q" class="form-label">Szukaj w ofertach</label>
                <input type="text" name="q" id="q" class="form-control" value="{{ $search }}" placeholder="Wpisz temat, odbiorców lub zagadnienie">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Szukaj</button>
                @if($search !== '')
                    <a href="{{ route('training-offers.pedagogical-councils.index') }}" class="btn btn-outline-secondary">Wyczyść</a>
                @endif
            </div>
        </form>

        @if($offers->count() > 0)
            <div class="row g-4 mb-5">
                <div class="col-lg-4">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body p-4">
                            <h3 class="h5 fw-bold mb-3">Jak zamówić szkolenie?</h3>
                            <ol class="mb-0 ps-3">
                                <li class="mb-2">Wybierz temat odpowiadający potrzebom rady pedagogicznej.</li>
                                <li class="mb-2">Napisz do nas lub zadzwoń, żeby omówić oczekiwania placówki.</li>
                                <li>Wspólnie ustalimy termin, formę i zakres szkolenia.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border-primary h-100">
                        <div class="card-body p-4">
                            <h3 class="h5 fw-bold mb-3">Nie wiesz, który temat wybrać lub nie widzisz tematu, który Cię interesuje?</h3>
                            <p class="text-muted mb-3">
                                Opisz potrzeby swojej rady pedagogicznej albo zaproponuj temat, którego nie ma w katalogu. Możemy zorganizować szkolenie na miarę — wystarczy, że napiszesz co Cię interesuje.
                            </p>
                            <a href="{{ route('training-offers.pedagogical-councils.inquiry.general') }}" class="btn btn-primary">
                                Zapytaj o szkolenie dla placówki
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-4">
                @foreach($offers as $offer)
                    <div class="col">
                        <article class="card h-100 border-0 shadow-sm">
                            @if($offer->publicImageUrl())
                                <a href="{{ route('training-offers.pedagogical-councils.show', $offer->slug) }}" class="text-decoration-none">
                                    <img src="{{ $offer->publicImageUrl() }}"
                                         class="card-img-top"
                                         alt="{{ $offer->title }}"
                                         loading="lazy"
                                         style="aspect-ratio: 16 / 9; object-fit: cover;">
                                </a>
                            @endif

                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-primary-subtle text-primary-emphasis">Oferta dla placówek</span>
                                    <span class="badge bg-light text-dark border">Termin do ustalenia</span>
                                </div>
                                <h3 class="h5 fw-bold">
                                    <a href="{{ route('training-offers.pedagogical-councils.show', $offer->slug) }}" class="text-decoration-none text-dark">
                                        {{ $offer->title }}
                                    </a>
                                </h3>

                                @if($offer->summary)
                                    <p class="text-muted">{{ $offer->summary }}</p>
                                @else
                                    <p class="text-muted">
                                        Szkolenie może zostać przygotowane dla rady pedagogicznej jako spotkanie dopasowane do potrzeb Twojej placówki.
                                    </p>
                                @endif

                                <ul class="list-unstyled small mb-3">
                                    @if($offer->audience)
                                        <li class="mb-2"><i class="bi bi-people me-2 text-primary"></i><strong>Dla:</strong> {{ $offer->audience }}</li>
                                    @endif
                                    <li><i class="bi bi-cash-coin me-2 text-primary"></i>{{ $offer->formattedPrice() }}</li>
                                </ul>

                                <div class="mt-auto">
                                    @if($offer->instructor)
                                        <div class="d-flex align-items-center gap-3 border-top pt-3 mb-3">
                                            @if($offer->instructor->publicPhotoUrl())
                                                <img src="{{ $offer->instructor->publicPhotoUrl() }}"
                                                     alt="{{ $offer->instructor->full_name }}"
                                                     class="rounded-circle flex-shrink-0"
                                                     width="56"
                                                     height="56"
                                                     style="object-fit: cover;"
                                                     loading="lazy">
                                            @else
                                                <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0"
                                                     style="width: 56px; height: 56px;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="small text-muted">Prowadzi</div>
                                                <div class="fw-semibold">{{ $offer->instructor->full_name_with_title }}</div>
                                                @if($offer->instructor->bio)
                                                    <div class="small text-muted">
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($offer->instructor->bio), 110) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <a href="{{ route('training-offers.pedagogical-councils.show', $offer->slug) }}" class="btn btn-outline-primary w-100">
                                        Zobacz szczegóły
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            @if($offers->hasPages())
                <div class="mt-4">
                    {{ $offers->links() }}
                </div>
            @endif
        @else
            <div class="alert alert-info">
                @if($search !== '')
                    Nie znaleziono ofert pasujących do wyszukiwania.
                @else
                    Przygotowujemy katalog tematów szkoleń dla rad pedagogicznych. Jeśli chcesz ustalić szkolenie dla swojej szkoły lub placówki, skontaktuj się z nami - pomożemy dobrać temat i termin.
                @endif
            </div>
        @endif
    </div>
</section>
@endsection
