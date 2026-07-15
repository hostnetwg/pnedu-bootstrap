@extends('layouts.app')

@section('title', 'Szkolenia indywidualne - Platforma Nowoczesnej Edukacji')

@section('content')

<!-- ===== HERO BANNER ======================================= -->
<div class="bg-primary bg-gradient text-white py-3 text-center">
    <div class="container">
        <p class="lead fw-semibold mb-0">
            Szkolenia indywidualne<br>
            <span style="color: #c6a300; font-style: normal; font-weight: 600;">
                Rozwijaj się z nami
            </span>
        </p>
    </div>
</div>

<!-- ===== UPCOMING COURSES ======================================= -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-6">
                <div class="badge bg-warning text-dark mb-2">Nadchodzące wydarzenia</div>
                <h2 class="display-5 fw-bold mb-3">Szkolenia, które <span class="text-primary">rozwijają</span></h2>
                <p class="lead">Zapoznaj się z naszymi najbliższymi szkoleniami i wybierz te, które najlepiej odpowiadają Twoim potrzebom zawodowym.</p>
            </div>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 g-4" data-aos="fade-up">
            @forelse($upcomingCourses as $course)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="position-relative">
                            <a href="{{ route('courses.show', $course->id) }}" class="d-block text-decoration-none" style="color: inherit;">
                                @if(!empty($course->image))
                                    <img src="{{ $course->publicImageUrl() }}" class="card-img-top" alt="{{ $course->title }}">
                                @else
                                    <div class="card-img-top d-flex align-items-center justify-content-center mb-2" style="width:100%; aspect-ratio:1/1; background:#e9ecef; border: 2px solid #dee2e6; border-radius: .5rem; border-style:dashed;">
                                        <i class="bi bi-mortarboard" style="font-size: 4rem; color: #f8f9fa;"></i>
                                    </div>
                                @endif
                            </a>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h5 class="card-title fw-bold mb-3">{!! $course->title !!}</h5>
                            @php
                                $start = \Carbon\Carbon::parse($course->start_date)->locale('pl');
                                $end = $course->end_date ? \Carbon\Carbon::parse($course->end_date) : null;
                            @endphp
                            <ul class="list-unstyled mb-3">
                                <li><strong>Data:</strong> {{ $start->format('d.m.Y') }}</li>
                                <li><strong>Godzina:</strong> {{ $start->format('H:i') }}@if($end) ({{ $start->diffInMinutes($end) }} min)@endif</li>
                                <li><strong>Dzień tygodnia:</strong> {{ $start->translatedFormat('l') }}</li>
                            </ul>
                            <p class="card-text">
                                <strong>{{ $course->trainer_title }}:</strong> {{ $course->trainer }}
                            </p>
                            <div class="mt-auto pt-3">
                                @php
                                    $priceInfo = $course->getCurrentPrice();
                                    // Fallback: jeśli getCurrentPrice() zwraca null, spróbuj pobrać cenę z pierwszego aktywnego wariantu
                                    if (!$priceInfo) {
                                        // Sprawdź czy priceVariants są załadowane
                                        if ($course->relationLoaded('priceVariants') && $course->priceVariants && $course->priceVariants->count() > 0) {
                                            $firstVariant = $course->priceVariants
                                                ->where('is_active', true)
                                                ->first(fn ($variant) => $variant->isAvailableForCourseEndState($course->hasEnded()));
                                            if ($firstVariant) {
                                                $isPromotionActive = $firstVariant->isPromotionActive();
                                                $currentPrice = $firstVariant->getCurrentPrice();
                                                $priceInfo = [
                                                    'price' => round((float) $currentPrice, 2),
                                                    'original_price' => $isPromotionActive ? round((float) $firstVariant->price, 2) : null,
                                                    'is_promotion' => $isPromotionActive,
                                                    'promotion_end' => $isPromotionActive && $firstVariant->promotion_type === 'time_limited' ? $firstVariant->promotion_end : null,
                                                    'promotion_type' => $firstVariant->promotion_type,
                                                ];
                                            }
                                        }
                                    }
                                @endphp
                                @if($priceInfo)
                                    <div class="text-center mb-3">
                                        @if($priceInfo['is_promotion'] && $priceInfo['original_price'])
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <span class="text-muted text-decoration-line-through" style="font-size: 0.9rem;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</span>
                                                    <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span class="text-danger" style="font-size: 1.2rem;">(brutto)</span>
                                                </div>
                                                @if($priceInfo['promotion_end'] && $priceInfo['promotion_type'] === 'time_limited')
                                                    <small style="font-size: 0.85rem; color: #000;">
                                                        Promocja trwa do: {{ \Carbon\Carbon::parse($priceInfo['promotion_end'])->format('d.m.Y H:i') }}
                                                    </small>
                                                @endif
                                                <small style="font-size: 0.75rem; color: #aaa;">
                                                    Najniższa cena z ostatnich 30 dni przed obniżką wynosiła: <strong style="color: #aaa;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</strong>
                                                </small>
                                            </div>
                                        @else
                                            <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span style="font-size: 1.2rem; color: #1976d2;">(brutto)</span>
                                        @endif
                                    </div>
                                @endif
                                <div class="text-center mb-2">
                                    <a href="{{ route('courses.show', $course->id) }}" 
                                       class="read-more-link">
                                        Czytaj więcej ...
                                    </a>
                                </div>
                                <a href="{{ $course->publicOrderFormUrl() }}"
                                   class="btn btn-warning w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm cta-btn"
                                   style="font-size:1.15rem; letter-spacing:0.5px;">
                                    <span>Zapisz się</span>
                                    <i class="bi bi-arrow-right-circle-fill"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h5>Brak dostępnych szkoleń</h5>
                        <p class="mb-0">W chwili obecnej nie ma dostępnych szkoleń indywidualnych. Sprawdź ponownie później.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($showArchivedSection)
            <div id="szkolenia-zakonczone" class="mt-5">
                @if($upcomingCourses->count() > 0)
                    <hr class="my-4" style="border-top: 2px solid #dee2e6;">
                @endif
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-muted">Szkolenia zakończone</h3>
                </div>
                @if($upcomingCourses->count() > 0)
                    <hr class="my-4 mb-5" style="border-top: 2px solid #dee2e6;">
                @endif

                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <form method="GET"
                              action="{{ route('courses.individual') }}#szkolenia-zakonczone"
                              class="row g-2 align-items-end">
                            <div class="col-md-9">
                                <label for="archived-q" class="form-label form-label-sm mb-1">Szukaj w zakończonych szkoleniach</label>
                                <input type="search"
                                       name="q"
                                       id="archived-q"
                                       class="form-control"
                                       value="{{ $archivedSearch }}"
                                       placeholder="Fragment tytułu szkolenia..."
                                       autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-search me-1"></i>Szukaj
                                </button>
                            </div>
                        </form>
                        @if($archivedSearch !== '')
                            <div class="text-center mt-2">
                                <a href="{{ route('courses.individual') }}#szkolenia-zakonczone" class="small text-muted">
                                    Wyczyść wyszukiwanie
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                @if($archivedSearch !== '')
                    <p class="text-center text-muted small mb-4">
                        @if($archivedCourses->total() > 0)
                            Znaleziono {{ $archivedCourses->total() }}
                            {{ $archivedCourses->total() === 1 ? 'pasujące szkolenie' : 'pasujących szkoleń' }}
                            dla „{{ $archivedSearch }}”
                        @else
                            Brak szkoleń pasujących do „{{ $archivedSearch }}”
                        @endif
                    </p>
                @endif

                @if($archivedCourses->count() > 0)
            <div class="row row-cols-1 row-cols-md-3 g-4 js-archived-courses-grid" data-aos="fade-up">
                @include('courses.partials.archived-courses-items', ['archivedCourses' => $archivedCourses])
            </div>

            @if($archivedCourses->hasMorePages())
                <div class="d-flex justify-content-center mt-4 mb-2">
                    <button type="button"
                            class="btn btn-outline-primary px-4 js-archived-load-more"
                            data-next-url="{{ $archivedCourses->nextPageUrl() }}">
                        <span class="js-archived-load-more-label">Pokaż więcej</span>
                        <span class="js-archived-load-more-spinner d-none spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            @endif
            @if($archivedCourses->hasPages())
                <div class="d-flex justify-content-center mt-2 js-archived-courses-pagination">
                    {{ $archivedCourses->links('pagination::bootstrap-4') }}
                </div>
            @endif
                @endif
            </div>
        @endif
    </div>
</section>

@endsection

@push('styles')
<style>
    .course-card-archived {
        opacity: 0.85;
        transition: opacity 0.25s ease, filter 0.25s ease;
    }
    .course-card-archived .course-card-archived-media img.card-img-top {
        transition: filter 0.25s ease;
        filter: grayscale(35%);
    }
    .course-card-archived:hover {
        opacity: 1;
    }
    .course-card-archived:hover .course-card-archived-media img.card-img-top {
        filter: grayscale(0%);
    }

    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
    }

    .cta-btn {
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .cta-btn:hover, .cta-btn:focus {
        background: #e0a800 !important;
        color: #212529 !important;
        box-shadow: 0 2px 12px rgba(224,168,0,0.18);
    }
    .cta-btn:hover i, .cta-btn:focus i {
        transform: translateX(6px) scale(1.1);
        transition: transform 0.18s;
    }
    .cta-btn i {
        transition: transform 0.18s;
    }
    
    .read-more-link {
        color: #1976d2;
        font-size: 0.95rem;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.2s ease, text-decoration 0.2s ease;
    }
    .read-more-link:hover {
        color: #0d47a1;
        text-decoration: underline;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });

    (function () {
        var button = document.querySelector('.js-archived-load-more');
        var grid = document.querySelector('.js-archived-courses-grid');
        if (!button || !grid) {
            return;
        }

        var label = button.querySelector('.js-archived-load-more-label');
        var spinner = button.querySelector('.js-archived-load-more-spinner');
        var loading = false;

        function setLoading(isLoading) {
            loading = isLoading;
            button.disabled = isLoading;
            button.setAttribute('aria-busy', isLoading ? 'true' : 'false');
            if (label) {
                label.textContent = isLoading ? 'Ładowanie…' : 'Pokaż więcej';
            }
            if (spinner) {
                spinner.classList.toggle('d-none', !isLoading);
            }
        }

        button.addEventListener('click', function () {
            if (loading) {
                return;
            }

            var nextUrl = button.getAttribute('data-next-url');
            if (!nextUrl) {
                return;
            }

            var requestUrl = new URL(nextUrl, window.location.origin);
            requestUrl.searchParams.set('load_more', '1');

            setLoading(true);

            fetch(requestUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Archived courses load-more failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (!data.html) {
                        return;
                    }

                    grid.insertAdjacentHTML('beforeend', data.html);

                    if (typeof AOS !== 'undefined') {
                        AOS.refresh();
                    }

                    if (data.has_more && data.next_page_url) {
                        button.setAttribute('data-next-url', data.next_page_url);
                        setLoading(false);
                        return;
                    }

                    button.closest('.d-flex')?.remove();
                })
                .catch(function () {
                    setLoading(false);
                });
        });
    })();
</script>
@endpush

