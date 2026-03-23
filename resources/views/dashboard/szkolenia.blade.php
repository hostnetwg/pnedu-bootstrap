@extends('layouts.app')

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
                        <a href="{{ route('dashboard.szkolenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.szkolenia')) active @endif">
                            <i class="bi bi-journal-text"></i> Moje szkolenia
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.zaswiadczenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.zaswiadczenia*')) active @endif">
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
                    <h2 class="h4 mb-2">Moje szkolenia</h2>
                    <p class="text-muted mb-4">
                        <small>Liczba szkoleń: <strong>{{ $participants->total() }}</strong></small>
                    </p>
                    
                    @if($participants->isEmpty())
                        <p class="text-muted">Nie jesteś jeszcze zarejestrowany na żadne szkolenie.</p>
                    @else
                        <div class="training-list">
                            @foreach($participants as $participant)
                                @php
                                    $course = $participant->course;
                                    $firstVideo = $course?->videos->first();
                                    $accessActive = $participant->hasActiveAccess();
                                    $accessExpiresFormatted = $participant->access_expires_at
                                        ? $participant->access_expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
                                        : null;
                                @endphp
                                <div class="training-item">
                                    <div class="training-content">
                                        <h3 class="training-title mb-2">
                                            @if($firstVideo && $accessActive)
                                                <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}" class="training-title-link" title="Otwórz nagranie">
                                                    {{ $course?->title ?? 'Szkolenie niedostępne w katalogu' }}
                                                    <i class="bi bi-camera-video ms-1" style="font-size: 0.9em; opacity: 0.7;"></i>
                                                </a>
                                            @elseif($firstVideo && !$accessActive)
                                                <span class="training-title-link training-title-link--disabled" title="Dostęp wygasł">
                                                    {{ $course?->title ?? 'Szkolenie niedostępne w katalogu' }}
                                                    <i class="bi bi-camera-video-off ms-1 text-muted" style="font-size: 0.9em;"></i>
                                                </span>
                                            @else
                                                <span class="training-title-text">{{ $course?->title ?? 'Szkolenie niedostępne w katalogu' }}</span>
                                            @endif
                                        </h3>
                                        @if($course)
                                            <div class="training-meta mb-2">
                                                <span class="training-date">
                                                    Data:
                                                    @if($course->start_date)
                                                        {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y') }}
                                                    @else
                                                        <span class="text-muted">Brak daty</span>
                                                    @endif
                                                </span>
                                                <span class="training-separator">|</span>
                                                <span class="training-instructor">
                                                    {{ $course->trainer_title }}:
                                                    @if($course->instructor)
                                                        {{ $course->instructor->full_name }}
                                                        @if($course->instructor->title)
                                                            <span class="text-muted">({{ $course->instructor->title }})</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Brak prowadzącego</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                        <p class="training-access-term text-muted small mb-0">
                                            @if($participant->access_expires_at)
                                                @if($accessActive)
                                                    Dostęp wygaśnie {{ $accessExpiresFormatted }}
                                                @else
                                                    Dostęp wygasł {{ $accessExpiresFormatted }}
                                                @endif
                                            @else
                                                Dostęp bezterminowy
                                            @endif
                                        </p>
                                    </div>
                                    @if($course)
                                        @php
                                            $courseForCert = $course;
                                            $certStatusKey = $courseForCert->certificate_download_status ?? 'in_preparation';
                                            $certCanDownload = $certStatusKey === 'download_enabled';
                                            $certCourseEnded = $courseForCert->end_date && \Carbon\Carbon::parse($courseForCert->end_date)->isPast();
                                            $zaswiadczenieUrl = route('dashboard.zaswiadczenia.course', $courseForCert->id).'?from=szkolenia';
                                        @endphp
                                        <div class="training-certificate">
                                            @if(!$certCourseEnded)
                                                <span class="certificate-download-link certificate-download-link--disabled"
                                                      title="Zaświadczenie zostanie udostępnione po zakończeniu szkolenia">
                                                    <img src="{{ asset('images/certificate.png') }}"
                                                         alt=""
                                                         class="certificate-icon certificate-icon--muted"
                                                         aria-hidden="true">
                                                    <span class="visually-hidden">Po zakończeniu szkolenia</span>
                                                </span>
                                            @elseif($certCanDownload)
                                                <a href="{{ $zaswiadczenieUrl }}"
                                                   class="certificate-download-link"
                                                   title="Zaświadczenie — szczegóły i pobranie (jak w zakładce Zaświadczenia)">
                                                    <img src="{{ asset('images/certificate.png') }}"
                                                         alt="Zaświadczenie"
                                                         class="certificate-icon">
                                                </a>
                                            @elseif($certStatusKey === 'no_certificate')
                                                <span class="certificate-download-link certificate-download-link--disabled"
                                                      title="Brak zaświadczenia dla tego szkolenia">
                                                    <img src="{{ asset('images/certificate.png') }}"
                                                         alt=""
                                                         class="certificate-icon certificate-icon--muted"
                                                         aria-hidden="true">
                                                    <span class="visually-hidden">Brak zaświadczenia</span>
                                                </span>
                                            @else
                                                <span class="certificate-download-link certificate-download-link--disabled"
                                                      title="Zaświadczenie w przygotowaniu — gdy będzie gotowe, pojawi się tutaj i w zakładce Zaświadczenia">
                                                    <img src="{{ asset('images/certificate.png') }}"
                                                         alt=""
                                                         class="certificate-icon certificate-icon--muted"
                                                         aria-hidden="true">
                                                    <span class="visually-hidden">W przygotowaniu</span>
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Paginacja --}}
                        @if($participants->hasPages())
                            <div class="mt-4 d-flex justify-content-center">
                                {{ $participants->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dashboard-minimal-menu {
    background: #fff;
    padding: 0;
    margin: 0;
}
.dashboard-minimal-menu li {
    margin-bottom: 0.5rem;
}
.dashboard-minimal-menu a {
    color: #6c757d;
    font-size: 1.08rem;
    padding: 0.75rem 1rem 0.75rem 0.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
    font-weight: 400;
    position: relative;
    display: block;
}
.dashboard-minimal-menu a.active, .dashboard-minimal-menu a:hover {
    color: #0d6efd;
    background: #f5f7fa;
}
.dashboard-minimal-menu i {
    font-size: 1.2rem;
    color: #adb5bd;
    transition: color 0.15s;
}
.dashboard-minimal-menu a.active i, .dashboard-minimal-menu a:hover i {
    color: #0d6efd;
}
@media (max-width: 991.98px) {
    .dashboard-minimal-menu {
        display: flex;
        flex-direction: row;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .dashboard-minimal-menu li {
        margin-bottom: 0;
    }
    .dashboard-minimal-menu a {
        padding: 0.5rem 0.9rem;
        font-size: 1rem;
    }
}
.training-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.training-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: #f8f9fa;
    transition: box-shadow 0.2s ease;
}
.training-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.training-content {
    flex: 1;
}
.training-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #212529;
}
.training-title-link {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s ease;
}
.training-title-link:hover {
    color: #0d6efd;
}
.training-title-link--disabled {
    cursor: not-allowed;
    color: #6c757d !important;
}
.training-title-link--disabled:hover {
    color: #6c757d !important;
}
.training-access-term {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}
.training-meta {
    font-size: 0.95rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.training-separator {
    color: #adb5bd;
}
.training-certificate {
    margin-left: 1.5rem;
    flex-shrink: 0;
}
.certificate-download-link {
    display: inline-block;
    text-decoration: none;
    transition: all 0.3s ease;
}
.certificate-download-link--disabled {
    cursor: help;
}
.certificate-download-link--disabled:hover .certificate-icon {
    transform: none;
}
.certificate-icon {
    width: 250px;
    height: auto;
    display: block;
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
}
.certificate-download-link:hover .certificate-icon {
    transform: scale(1.1);
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
}
.certificate-icon--muted {
    opacity: 0.5;
    filter: grayscale(0.4) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.12));
}
.certificate-download-link--disabled:hover .certificate-icon--muted {
    filter: grayscale(0.4) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.12));
}
.pagination {
    margin-bottom: 0;
}
.pagination .page-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #0d6efd;
    border-color: #dee2e6;
}
.pagination .page-link:hover {
    color: #0a58ca;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
    opacity: 0.5;
}
@media (max-width: 767.98px) {
    .training-item {
        flex-direction: column;
        align-items: stretch;
    }
    .training-certificate {
        margin-left: 0;
        margin-top: 1rem;
        text-align: center;
    }
    .certificate-icon {
        width: 200px;
    }
}
</style>
@endpush