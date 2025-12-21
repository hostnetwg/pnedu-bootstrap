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
                            @if(!empty($course->image))
                                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" class="card-img-top" alt="{{ $course->title }}">
                            @else
                                <div class="card-img-top d-flex align-items-center justify-content-center mb-2" style="width:100%; aspect-ratio:1/1; background:#e9ecef; border: 2px solid #dee2e6; border-radius: .5rem; border-style:dashed;">
                                    <i class="bi bi-mortarboard" style="font-size: 4rem; color: #f8f9fa;"></i>
                                </div>
                            @endif
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
                                            $firstVariant = $course->priceVariants->where('is_active', true)->first();
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
                                <a href="{{ route('courses.show', $course->id) }}"
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

        @if($upcomingCourses->count() > 0 && $archivedCourses->count() > 0)
            <!-- Pozioma linia z napisem -->
            <div class="row my-5">
                <div class="col-12">
                    <hr class="my-4" style="border-top: 2px solid #dee2e6;">
                    <div class="text-center">
                        <h3 class="fw-bold text-muted">Szkolenia zakończone</h3>
                    </div>
                    <hr class="my-4" style="border-top: 2px solid #dee2e6;">
                </div>
            </div>
        @endif

        <!-- Archiwalne szkolenia -->
        @if($archivedCourses->count() > 0)
            <div class="row row-cols-1 row-cols-md-3 g-4" data-aos="fade-up">
                @foreach($archivedCourses as $course)
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm hover-lift" style="opacity: 0.85;">
                            <div class="position-relative">
                                @if(!empty($course->image))
                                    <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" class="card-img-top" alt="{{ $course->title }}" style="filter: grayscale(30%);">
                                @else
                                    <div class="card-img-top d-flex align-items-center justify-content-center mb-2" style="width:100%; aspect-ratio:1/1; background:#e9ecef; border: 2px solid #dee2e6; border-radius: .5rem; border-style:dashed;">
                                        <i class="bi bi-mortarboard" style="font-size: 4rem; color: #f8f9fa;"></i>
                                    </div>
                                @endif
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
                                    <a href="{{ route('courses.show', $course->id) }}"
                                       class="btn btn-outline-secondary w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                       style="font-size:1.15rem; letter-spacing:0.5px;">
                                        <span>Zobacz szczegóły</span>
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection

@push('styles')
<style>
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
    // Initialize AOS
    AOS.init({ 
        duration: 800, 
        once: true,
        offset: 100
    });
</script>
@endpush

