@extends('layouts.app')

@php
    $course = $product->onlineCourse;
    $offer = $product->defaultOffer;
    $prices = $offer?->activePrices ?? collect();
    $salesOpen = $product->isSalesOpen() && $prices->isNotEmpty();
    $metaTitle = $product->meta_title ?: $product->name.' – kurs online | PNEDU';
    $metaDescription = $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($course?->description ?? ''), 155);
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDescription)
@section('canonical', route('online-courses.catalog.show', $product->slug))
@section('robots', $salesOpen
    ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'
    : 'noindex, nofollow')

@section('content')
<main>
    <section class="bg-light border-bottom py-5">
        <div class="container">
            <nav aria-label="Okruszki">
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Start</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('online-courses.catalog.index') }}">Kursy</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="row g-5 align-items-center">
                <div class="col-lg-7">
                    <span class="badge text-bg-primary mb-3">Kurs online · nagrania</span>
                    <h1 class="display-5 fw-bold">{{ $product->name }}</h1>
                    @if($course?->instructor)
                        <p class="lead text-muted">
                            Autor: {{ trim($course->instructor->first_name.' '.$course->instructor->last_name) }}
                        </p>
                    @endif
                    <p class="lead">{{ $course?->description }}</p>
                </div>
                @if($course?->publicImageUrl())
                    <div class="col-lg-5">
                        <img src="{{ $course->publicImageUrl() }}"
                             class="img-fluid rounded shadow"
                             alt="Okładka kursu {{ $product->name }}">
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="row g-5">
                <div class="col-lg-7">
                    <h2 class="h3 mb-3">O kursie</h2>
                    @if(filled($course?->offer_description_html))
                        <div class="content-body">{!! $course->offer_description_html !!}</div>
                    @elseif(filled($course?->description))
                        <p>{{ $course->description }}</p>
                    @endif

                    @php
                        $publishedModules = $course?->modules
                            ?->map(fn ($module) => [
                                'module' => $module,
                                'lessons' => $module->lessons->where('is_published', true),
                            ])
                            ->filter(fn ($row) => $row['lessons']->isNotEmpty())
                            ?? collect();
                    @endphp
                    @if($publishedModules->isNotEmpty())
                        <h2 class="h3 mt-5 mb-3">Program kursu</h2>
                        <div class="accordion" id="courseProgram">
                            @foreach($publishedModules as $row)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#courseModule{{ $row['module']->id }}"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $row['module']->title }}
                                        </button>
                                    </h3>
                                    <div id="courseModule{{ $row['module']->id }}"
                                         class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                         data-bs-parent="#courseProgram">
                                        <div class="accordion-body">
                                            <ul class="mb-0">
                                                @foreach($row['lessons'] as $lesson)
                                                    <li>{{ $lesson->title }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="col-lg-5">
                    <div class="card shadow-sm sticky-lg-top" style="top: 90px;">
                        <div class="card-body p-4">
                            @include('online-course-storefront.partials.offer-sidebar', [
                                'product' => $product,
                                'offer' => $offer,
                                'prices' => $prices,
                                'salesOpen' => $salesOpen,
                                'viewerAccess' => $viewerAccess,
                            ])
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    @include('online-course-storefront.partials.promotion-countdown-script')
@endpush
