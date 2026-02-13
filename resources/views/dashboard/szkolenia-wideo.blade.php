@extends('layouts.app')

@section('title', 'Nagranie: ' . $course->title . ' - Platforma Nowoczesnej Edukacji')

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
                        <a href="{{ route('dashboard.zaswiadczenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.zaswiadczenia')) active @endif">
                            <i class="bi bi-award"></i> Zaświadczenia
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.moje-dane') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.moje-dane')) active @endif">
                            <i class="bi bi-person-circle"></i> Moje dane
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
                            <small>Prowadzący: {{ $course->instructor->full_name }}@if($course->instructor->title) <span class="text-muted">({{ $course->instructor->title }})</span>@endif</small>
                        </p>
                    @endif

                    {{-- Osadzony odtwarzacz wideo --}}
                    <div class="video-wrapper mb-4">
                        <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                            <iframe
                                src="{{ $selectedVideo->getEmbedUrl() }}"
                                title="{{ $selectedVideo->title ?: 'Nagranie szkolenia' }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                    </div>

                    {{-- Lista nagrań (gdy jest więcej niż jedno) --}}
                    @if($videos->count() > 1)
                        <div class="border-top pt-4">
                            <h5 class="mb-3"><i class="bi bi-camera-video me-2"></i>Inne nagrania z tego szkolenia</h5>
                            <div class="list-group">
                                @foreach($videos as $video)
                                    <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}?video={{ $video->id }}"
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $video->id === $selectedVideo->id ? 'active' : '' }}">
                                        <span>
                                            {{ $video->title ?: ('Nagranie nr ' . $loop->iteration) }}
                                            @if($video->id === $selectedVideo->id)
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
