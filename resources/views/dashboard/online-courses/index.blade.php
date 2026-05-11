@extends('layouts.app')

@section('title', 'Kursy online – Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>@include('dashboard.partials.sidebar-nav')</nav>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h2 class="h4 mb-3">Kursy online</h2>
                    <p class="text-muted mb-4">Materiały z kursów zakupionych wcześniej (np. na nowoczesna-edukacja.pl). Dostęp po zalogowaniu na ten sam adres e-mail.</p>
                    @if($enrollments->isEmpty())
                        <p class="text-muted mb-0">Nie masz jeszcze przypisanych kursów online. Jeśli kupiłeś kurs wcześniej na starej platformie, po migracji dostępu pojawi się on tutaj — na ten sam adres e-mail co konto PNEDU.</p>
                    @else
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4">
                            @foreach($enrollments as $enrollment)
                                @php($p = $lessonProgressByEnrollment[$enrollment->id] ?? ['completed' => 0, 'total' => 0])
                                @php($pctRow = (($p['total'] ?? 0) > 0) ? min(100, (int) round(100 * (int) ($p['completed'] ?? 0) / (int) $p['total'])) : 0)
                                @php($imgUrl = $enrollment->onlineCourse->publicImageUrl())
                                <div class="col d-flex">
                                    <article class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 w-100 d-flex flex-column online-course-tile">
                                        <div class="online-course-tile-media bg-body-secondary border-bottom">
                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}"
                                                     class="online-course-tile-img"
                                                     alt="Okładka kursu: {{ $enrollment->onlineCourse->title }}"
                                                     loading="lazy"
                                                     decoding="async">
                                            @else
                                                <div class="online-course-tile-placeholder text-muted small">
                                                    <i class="bi bi-collection-play d-block fs-2 mb-2 opacity-50" aria-hidden="true"></i>
                                                    Brak okładki
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-body d-flex flex-column flex-grow-1 pt-3">
                                            <h3 class="h6 mb-2">{{ $enrollment->onlineCourse->title }}</h3>
                                            @if($enrollment->onlineCourse->instructor)
                                                <p class="small text-muted mb-2 mb-md-3">{{ $enrollment->onlineCourse->instructor->full_name_with_title }}</p>
                                            @endif
                                            @if(($p['total'] ?? 0) > 0)
                                                <p class="small text-muted mb-1">Ukończone lekcje: {{ (int) ($p['completed'] ?? 0) }} z {{ (int) $p['total'] }}</p>
                                                <div class="progress mb-0" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $pctRow }}%;"></div>
                                                </div>
                                            @endif
                                            <div class="mt-auto pt-3">
                                                <a href="{{ route('dashboard.online-courses.show', $enrollment) }}" class="btn btn-primary w-100">Przejdź do kursu</a>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
<style>
    .online-course-tile-media {
        min-height: 10rem;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1rem;
    }
    .online-course-tile-img {
        max-width: 100%;
        max-height: 11rem;
        width: auto;
        height: auto;
        object-fit: contain;
        vertical-align: middle;
    }
    .online-course-tile-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-height: 8rem;
        padding: 1rem;
    }
</style>
@endpush
