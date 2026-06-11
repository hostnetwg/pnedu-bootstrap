@extends('layouts.app')

@section('title', ($selectedVideo ? 'Nagranie: ' : 'Materiały: ') . $course->title . ' - Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-3 order-1 order-lg-1 mb-4 mb-lg-0">
            <nav>
                @include('dashboard.partials.sidebar-nav-menu')
            </nav>
            <div class="d-none d-lg-block">
                @include('dashboard.partials.sidebar-nav-offer-mount', ['offerMountClass' => ''])
            </div>
        </div>
        <div class="col-12 col-lg-9 order-2 order-lg-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <a href="{{ route('dashboard.szkolenia') }}" class="btn btn-link text-decoration-none p-0">
                            <i class="bi bi-arrow-left me-1"></i> Powrót do listy szkoleń
                        </a>
                    </div>
                    <h2 class="h4 mb-2">{{ $course->title }}</h2>
                    @if($course->instructor)
                        <p class="text-muted mb-4">
                            <small>{{ $course->trainer_title }}: {{ $course->instructor->full_name_with_title }}</small>
                        </p>
                    @endif

                    @if($selectedVideo)
                    {{-- Osadzony odtwarzacz wideo --}}
                    <div class="video-wrapper mb-4">
                        <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                            <iframe
                                src="{{ $selectedVideo->getEmbedUrl() }}"
                                title="{{ $selectedVideo->title ?: 'Nagranie szkolenia' }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>
                        </div>
                    </div>
                    @php
                        $accessExpiresAt = $participant->access_expires_at;
                        $accessExpiresAtFormatted = $accessExpiresAt
                            ? $accessExpiresAt->copy()->timezone(config('app.timezone'))->format('d.m.Y H:i')
                            : null;
                    @endphp
                    @if($accessExpiresAt && $participant->hasActiveAccess())
                        <p class="small text-success mb-4 mt-n2">
                            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>
                            Dostęp wygaśnie {{ $accessExpiresAtFormatted }}
                        </p>
                    @endif

                    @if($selectedVideo->platform === 'youtube')
                        <div class="youtube-external-hint border-top pt-3 mt-2 mb-4">
                            <p class="small text-muted mb-2">
                                Na YouTube nagranie otwiera się w widoku z <strong>zapisem czatu</strong> obok wideo — wygodniej śledzisz pytania i odpowiedzi z transmisji.
                                Jeśli materiał jest dla Ciebie wartościowy, możesz <strong>zasubskrybować kanał</strong> i zostawić <strong>polubienie</strong>; to realnie wspiera powstawanie kolejnych treści.
                            </p>
                            <a href="{{ $selectedVideo->getWatchUrl() }}"
                               class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-2"
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="bi bi-youtube"></i>
                                Otwórz na YouTube
                            </a>
                        </div>
                    @endif
                        @if($course->fileLinks->isNotEmpty() && ! $courseEnded)
                            <div class="alert alert-light border mb-0" role="status">
                                <p class="small mb-0 text-body-secondary">
                                    <i class="bi bi-info-circle me-1 text-primary" aria-hidden="true"></i>
                                    Materiały do pobrania będą dostępne po zakończeniu szkolenia (wg daty zakończenia w kursie).
                                </p>
                            </div>
                        @endif
                    @elseif($fileLinks->isNotEmpty())
                        <p class="text-muted mb-4">To szkolenie udostępnia materiały do pobrania (linki poniżej).</p>
                    @endif

                    @if($accessibleSurveyLinks->isNotEmpty())
                        <div class="alert alert-primary border-0 shadow-sm mb-4" role="region" aria-labelledby="surveyHeading{{ $participant->id }}">
                            <h6 class="alert-heading h6 mb-2" id="surveyHeading{{ $participant->id }}">
                                <i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Ankiety po szkoleniu
                            </h6>
                            <p class="small mb-3 text-body">
                                Pomóż nam doskonalić nasze szkolenia. Wypełnij krótką ankietę poszkoleniową —
                                Twoja opinia pozwoli nam jeszcze lepiej odpowiadać na potrzeby dyrektorów i nauczycieli.
                            </p>
                            <div class="d-grid gap-2">
                                @foreach($accessibleSurveyLinks as $survey)
                                    <a href="{{ $survey['url'] }}"
                                       class="btn btn-light btn-sm text-start d-flex justify-content-between align-items-center text-wrap"
                                       target="_blank"
                                       rel="noopener noreferrer">
                                        <span class="me-2">{{ $survey['title'] }}</span>
                                        <i class="bi bi-box-arrow-up-right flex-shrink-0" aria-hidden="true"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @php
                        $certStatusKey = $course->certificate_download_status ?? 'in_preparation';
                        $certCanDownload = $certStatusKey === 'download_enabled';
                        $zaswiadczenieUrl = route('dashboard.zaswiadczenia.course', $course->id).'?from=szkolenia-wideo';
                    @endphp

                    {{-- Zaświadczenie; materiały do plików opcjonalnie pod spodem --}}
                    <div class="border-top pt-4 mt-4 mb-4">
                            <h6 class="h6 mb-3"><i class="bi bi-award me-2 text-primary"></i>Zaświadczenie</h6>
                            @if(! $courseEnded)
                                <p class="small text-muted mb-0">Zaświadczenie zostanie udostępnione po zakończeniu szkolenia.</p>
                            @elseif($certCanDownload)
                                <a href="{{ $zaswiadczenieUrl }}"
                                   class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    Przejdź do zaświadczenia
                                </a>
                            @elseif($certStatusKey === 'no_certificate')
                                <p class="small text-muted mb-0">Brak zaświadczenia dla tego szkolenia.</p>
                            @else
                                <p class="small text-muted mb-0">Zaświadczenie w przygotowaniu — gdy będzie gotowe, pojawi się tutaj i w zakładce Zaświadczenia.</p>
                            @endif

                            @if($fileLinks->isNotEmpty())
                                <h5 class="h5 mb-3 mt-4 pt-3 border-top"><i class="bi bi-folder2-open me-2"></i>Materiały do pobrania (pliki)</h5>
                                <div class="d-grid gap-2">
                                    @foreach($fileLinks as $link)
                                        <a href="{{ $link->url }}"
                                           class="btn btn-outline-primary btn-sm d-flex justify-content-between align-items-center text-start text-wrap"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            <span class="me-2">
                                                @if($link->isGoogleHostedUrl())
                                                    <i class="bi bi-google me-2" aria-hidden="true"></i>
                                                @else
                                                    <i class="bi bi-link-45deg me-2" aria-hidden="true"></i>
                                                @endif
                                                {{ $link->title ?: $link->url }}
                                            </span>
                                            <i class="bi bi-box-arrow-up-right flex-shrink-0" aria-hidden="true"></i>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                    </div>

                    {{-- Lista nagrań (gdy jest więcej niż jedno) --}}
                    @if($videos->count() > 1)
                        <div class="border-top pt-4">
                            <h5 class="mb-3"><i class="bi bi-camera-video me-2"></i>Inne nagrania z tego szkolenia</h5>
                            <div class="list-group">
                                @foreach($videos as $video)
                                    <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}?video={{ $video->id }}"
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $selectedVideo && $video->id === $selectedVideo->id ? 'active' : '' }}">
                                        <span>
                                            {{ $video->title ?: ('Nagranie nr ' . $loop->iteration) }}
                                            @if($selectedVideo && $video->id === $selectedVideo->id)
                                                <span class="badge bg-light text-dark ms-2">Odtwarzane</span>
                                            @endif
                                        </span>
                                        <i class="bi bi-play-circle"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 d-lg-none order-3">
            @include('dashboard.partials.sidebar-nav-offer-mount', ['offerMountClass' => ''])
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
<style>
.video-wrapper iframe { border: none; }
</style>
@endpush

@push('scripts')
@include('dashboard.partials.sidebar-nav-offer-loader-script')
@endpush
