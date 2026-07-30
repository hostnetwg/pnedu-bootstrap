@foreach($offers as $offer)
    @php
        $offerSummary = $offer->summary
            ?: 'Szkolenie zamknięte dopasowane do potrzeb szkoły, przedszkola lub placówki oświatowej.';
    @endphp
    <div class="featured-offers-carousel__slide">
        <article class="card h-100 border-0 shadow-sm hover-lift featured-training-offer-card">
            @if($offer->publicImageUrl())
                <a href="{{ route('training-offers.pedagogical-councils.show', $offer->slug) }}" class="text-decoration-none">
                    <img src="{{ $offer->publicImageUrl() }}"
                         class="card-img-top"
                         alt="{{ $offer->title }}"
                         loading="lazy"
                         decoding="async"
                         style="aspect-ratio: 16 / 9; object-fit: cover;">
                </a>
            @endif

            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary-subtle text-primary-emphasis">Rada pedagogiczna</span>
                    <span class="badge bg-light text-dark border">Termin do ustalenia</span>
                </div>

                <h3 class="h5 fw-bold mb-3">
                    <a href="{{ route('training-offers.pedagogical-councils.show', $offer->slug) }}" class="text-decoration-none text-dark">
                        {{ $offer->title }}
                    </a>
                </h3>

                <div class="featured-offer-summary-block mb-3">
                    <p class="text-muted featured-offer-summary mb-0 is-clamped" data-featured-offer-summary>
                        {{ $offerSummary }}
                    </p>
                    <button type="button"
                            class="btn btn-link btn-sm p-0 mt-1 featured-offer-summary-toggle d-none"
                            data-featured-offer-summary-toggle
                            aria-expanded="false"
                            aria-label="Pokaż pełny opis szkolenia">
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        <span class="visually-hidden">Rozwiń opis</span>
                    </button>
                </div>

                <div class="mt-auto pt-2">
                    @if($offer->instructor)
                        <div class="small text-muted mb-3 text-end">
                            <i class="bi bi-person me-2 text-primary"></i>{{ $offer->instructor->full_name_with_title }}
                        </div>
                    @endif

                    <a href="{{ route('training-offers.pedagogical-councils.show', $offer->slug) }}" class="btn btn-primary w-100">
                        Zobacz ofertę
                    </a>
                </div>
            </div>
        </article>
    </div>
@endforeach
