@extends('layouts.app')

@section('title', ($selectedVideo ? 'Nagranie: ' : 'Materiały: ') . $course->title . ' - Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>
                <ul class="list-unstyled dashboard-minimal-menu">
                    <li>
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active @endif">
                            <i class="bi bi-house-door"></i> Panel
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.szkolenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.szkolenia*')) active @endif">
                            <i class="bi bi-journal-text"></i> Moje szkolenia
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.zaswiadczenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.zaswiadczenia*')) active @endif">
                            <i class="bi bi-award"></i> Zaświadczenia
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="col-lg-9">
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
                            <small>{{ $course->trainer_title }}: {{ $course->instructor->full_name }}@if($course->instructor->title) <span class="text-muted">({{ $course->instructor->title }})</span>@endif</small>
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
                    @elseif($fileLinks->isNotEmpty())
                        <p class="text-muted mb-4">To szkolenie udostępnia materiały do pobrania (linki poniżej).</p>
                    @endif

                    @php
                        $certStatusKey = $course->certificate_download_status ?? 'in_preparation';
                        $certCanDownload = $certStatusKey === 'download_enabled';
                        $certCourseEnded = $course->end_date && \Carbon\Carbon::parse($course->end_date)->isPast();
                        $zaswiadczenieUrl = route('dashboard.zaswiadczenia.course', $course->id).'?from=szkolenia-wideo';
                    @endphp

                    {{-- Zaświadczenie; materiały do plików opcjonalnie pod spodem --}}
                    <div class="border-top pt-4 mt-4 mb-4">
                            <h6 class="h6 mb-3"><i class="bi bi-award me-2 text-primary"></i>Zaświadczenie</h6>
                            @if(!$certCourseEnded)
                                <p class="small text-muted mb-0">Zaświadczenie zostanie udostępnione po zakończeniu szkolenia.</p>
                            @elseif($certCanDownload)
                                <a href="{{ $zaswiadczenieUrl }}"
                                   class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    Przejdź do zaświadczenia
                                </a>
                                <p class="small text-muted mt-2 mb-0">Szczegóły i pobranie — tak jak w zakładce Zaświadczenia.</p>
                            @elseif($certStatusKey === 'no_certificate')
                                <p class="small text-muted mb-0">Brak zaświadczenia dla tego szkolenia.</p>
                            @else
                                <p class="small text-muted mb-0">Zaświadczenie w przygotowaniu — gdy będzie gotowe, pojawi się tutaj i w zakładce Zaświadczenia.</p>
                            @endif

                            @if($fileLinks->isNotEmpty())
                                <h5 class="h5 mb-2 mt-4 pt-3 border-top"><i class="bi bi-folder2-open me-2"></i>Materiały do pobrania (pliki)</h5>
                                <p class="small text-muted mb-3 mb-md-4">Otwórz link w nowej karcie (np. folder lub plik w chmurze albo u innego dostawcy).</p>
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
    </div>
</div>
@endsection

@push('styles')
<style>
.dashboard-minimal-menu { background: #fff; padding: 0; margin: 0; }
.dashboard-minimal-menu li { margin-bottom: 0.5rem; }
.dashboard-minimal-menu a {
    color: #6c757d;
    font-size: 1.08rem;
    padding: 0.75rem 1rem 0.75rem 0.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
    font-weight: 400;
    display: block;
}
.dashboard-minimal-menu a.active, .dashboard-minimal-menu a:hover {
    color: #0d6efd;
    background: #f5f7fa;
}
.dashboard-minimal-menu i { font-size: 1.2rem; color: #adb5bd; }
.dashboard-minimal-menu a.active i, .dashboard-minimal-menu a:hover i { color: #0d6efd; }
.video-wrapper iframe { border: none; }
</style>
@endpush
