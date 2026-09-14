@extends('layouts.app')

@section('title', 'Kursy online dla nauczycieli i dyrektorów | PNEDU')
@section('meta_description', 'Nagrywane kursy online dla nauczycieli, dyrektorów i placówek oświatowych. Ucz się we własnym tempie i wybierz dogodny okres dostępu.')
@section('canonical', route('online-courses.catalog.index'))

@section('content')
<main>
    <section class="bg-primary text-white py-5">
        <div class="container">
            <nav aria-label="Okruszki">
                <ol class="breadcrumb breadcrumb-dark mb-3">
                    <li class="breadcrumb-item"><a class="text-white" href="{{ route('home') }}">Start</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Kursy</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold">Kursy online</h1>
            <p class="lead mb-0 col-lg-8">Nagrania, materiały i nauka we własnym tempie. Oglądasz wtedy, kiedy chcesz, wracasz do wybranych tematów i nie musisz trzymać się sztywnego kalendarza. Kursy są zwykle ułożone w moduły i lekcje, żeby łatwiej iść krok po kroku.</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            @if($products->isEmpty())
                <div class="alert alert-info">Obecnie przygotowujemy ofertę kursów. Zajrzyj ponownie wkrótce.</div>
            @else
                <div class="row g-4">
                    @foreach($products as $product)
                        @php
                            $course = $product->onlineCourse;
                            $prices = $product->defaultOffer?->activePrices ?? collect();
                            $paidPrices = $prices->reject(fn ($price) => $price->isComplimentary());
                            $featuredPrice = $paidPrices
                                ->sortBy(fn ($price) => (float) $price->currentPrice())
                                ->first();
                            $lowestPrice = $featuredPrice ? (float) $featuredPrice->currentPrice() : null;
                            $hasComplimentary = $prices->contains(fn ($price) => $price->isComplimentary());
                            $salesOpen = $product->isSalesOpen() && $prices->isNotEmpty();
                            $ownerAccess = ($accessByCourseId ?? [])[(int) $product->resource_id] ?? null;
                            $detailsUrl = route('online-courses.catalog.show', $product->slug);
                            $complimentaryPrice = $prices->first(fn ($price) => $price->isComplimentary());
                            $orderUrl = $featuredPrice
                                ? route('online-courses.checkout.create', ['product' => $product->slug, 'price' => $featuredPrice->id])
                                : ($complimentaryPrice
                                    ? route('online-courses.free-signup.create', ['product' => $product->slug, 'price' => $complimentaryPrice->id])
                                    : null);
                            $orderLabel = $featuredPrice ? 'Zamawiam dostęp' : 'Zapisz się bezpłatnie';
                        @endphp
                        <article class="col-md-6 col-xl-4">
                            <div class="card h-100 shadow-sm border-0">
                                @if($course?->publicImageUrl())
                                    <a href="{{ route('online-courses.catalog.show', $product->slug) }}">
                                        <img src="{{ $course->publicImageUrl() }}"
                                             class="card-img-top"
                                             style="width: 100%; height: auto; object-fit: contain; display: block;"
                                             alt="Okładka kursu {{ $product->name }}">
                                    </a>
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <h2 class="h5">
                                        <a class="text-decoration-none" href="{{ route('online-courses.catalog.show', $product->slug) }}">
                                            {{ $product->name }}
                                        </a>
                                    </h2>
                                    @if($course?->instructor)
                                        <p class="small text-muted mb-2">
                                            Autor: {{ trim($course->instructor->first_name.' '.$course->instructor->last_name) }}
                                        </p>
                                    @endif
                                    <p class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($course?->description ?? ''), 180) }}</p>
                                    <div class="mt-auto">
                                        @if($ownerAccess?->isUnlimited())
                                            <p class="mb-3">Masz dostęp bezterminowy.</p>
                                            <a href="{{ $ownerAccess->dashboardUrl() }}" class="btn btn-primary">Przejdź do kursu</a>
                                        @elseif($ownerAccess?->canEnter())
                                            <p class="mb-2">Dostęp do <strong>{{ $ownerAccess->endsLabel() }}</strong></p>
                                            <div class="d-grid gap-2">
                                                <a href="{{ $ownerAccess->dashboardUrl() }}" class="btn btn-primary">Przejdź do kursu</a>
                                                <a href="{{ $detailsUrl }}" class="btn btn-outline-primary">Zobacz szczegóły</a>
                                            </div>
                                        @elseif(! $salesOpen)
                                            <div class="d-grid gap-2">
                                                <button type="button"
                                                        class="btn btn-primary"
                                                        disabled
                                                        aria-disabled="true">
                                                    Zamawiam dostęp
                                                </button>
                                                <a href="{{ $detailsUrl }}" class="btn btn-outline-primary">Zobacz szczegóły</a>
                                            </div>
                                        @else
                                            @if($ownerAccess?->state === \App\Support\StorefrontCourseAccess::STATE_SCHEDULED)
                                                <p class="mb-3">Dostęp od <strong>{{ $ownerAccess->startsLabel() }}</strong></p>
                                            @elseif($ownerAccess?->state === \App\Support\StorefrontCourseAccess::STATE_EXPIRED)
                                                <p class="small text-muted mb-2">Dostęp skończył się {{ $ownerAccess->endsLabel() }}.</p>
                                            @endif
                                            @if($featuredPrice?->isPromotionActive() && $ownerAccess?->state !== \App\Support\StorefrontCourseAccess::STATE_SCHEDULED)
                                                <div class="d-flex flex-column gap-1 mb-3">
                                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                                        <span class="text-muted text-decoration-line-through" style="font-size: 0.85rem;">{{ number_format((float) $featuredPrice->price, 2, ',', ' ') }} PLN</span>
                                                        <strong class="text-danger">{{ number_format((float) $lowestPrice, 2, ',', ' ') }} PLN</strong>
                                                    </div>
                                                    @include('online-course-storefront.partials.promotion-notice', ['price' => $featuredPrice])
                                                </div>
                                            @elseif($ownerAccess?->state !== \App\Support\StorefrontCourseAccess::STATE_SCHEDULED && $lowestPrice !== null)
                                                <p class="mb-3"><strong>{{ number_format((float) $lowestPrice, 2, ',', ' ') }} zł</strong></p>
                                            @elseif($ownerAccess?->state !== \App\Support\StorefrontCourseAccess::STATE_SCHEDULED && $hasComplimentary)
                                                <p class="mb-3"><strong>Bezpłatny dostęp</strong></p>
                                            @endif
                                            <div class="d-grid gap-2">
                                                @if($orderUrl)
                                                    <a href="{{ $orderUrl }}" class="btn {{ $featuredPrice ? 'btn-primary' : 'btn-success' }}">{{ $orderLabel }}</a>
                                                @endif
                                                <a href="{{ $detailsUrl }}" class="btn btn-outline-primary">Zobacz szczegóły</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $products->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @endif
        </div>
    </section>
</main>
@endsection

@push('scripts')
    @include('online-course-storefront.partials.promotion-countdown-script')
@endpush
