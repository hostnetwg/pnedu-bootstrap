@extends('layouts.app')

@section('title', ($pageTitle ?? 'TIK w pracy NAUCZYCIELA') . ' - Bezpłatne szkolenia - Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">{{ $pageTitle ?? 'TIK w pracy NAUCZYCIELA' }}</h1>
            <p class="lead mb-4">Lista bezpłatnych szkoleń dostępnych na platformie</p>
            
            @if(!isset($databaseError) || !$databaseError)
                <div class="mb-3">
                    <span class="fw-semibold">
                        Wyświetlono
                        @if($courses->total() > 0)
                            {{ ($courses->currentPage() - 1) * $courses->perPage() + 1 }}
                            -
                            {{ ($courses->currentPage() - 1) * $courses->perPage() + $courses->count() }}
                            z
                            {{ $courses->total() }}
                            szkoleń
                        @else
                            0 szkoleń
                        @endif
                    </span>
                </div>
            @endif
            
            <div class="mb-4">
                <form method="GET" action="{{ route('courses.free') }}" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label for="q" class="form-label form-label-sm mb-1">Szukaj</label>
                        <input type="text" name="q" id="q" class="form-control" value="{{ old('q', $searchQuery ?? request('q')) }}" placeholder="Tytuł lub opis...">
                    </div>
                    <div class="col-md-2">
                        <label for="sort" class="form-label form-label-sm mb-1">Sortuj</label>
                        <select name="sort" id="sort" class="form-select" onchange="this.form.submit()">
                            <option value="desc" @if(isset($sort) && $sort === 'desc') selected @endif>Najnowsze</option>
                            <option value="asc" @if(isset($sort) && $sort === 'asc') selected @endif>Najstarsze</option>
                        </select>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Szukaj</button>
                    </div>
                </form>
            </div>
            
            @if(isset($databaseError) && $databaseError)
                <div class="alert alert-danger">
                    Przepraszamy, wystąpił problem z dostępem do bazy danych. Prosimy spróbować później.
                </div>
            @else
                @if($courses->count() > 0)
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            Sortuj: 
                            <a href="{{ request()->fullUrlWithQuery(['sort' => (isset($sort) && $sort === 'asc') ? 'desc' : 'asc', 'page' => 1]) }}" class="text-decoration-none">
                                @if(isset($sort) && $sort === 'asc')
                                    Najstarsze <i class="bi bi-caret-up-fill"></i>
                                @else
                                    Najnowsze <i class="bi bi-caret-down-fill"></i>
                                @endif
                            </a>
                        </span>
                    </div>
                    
                    <div class="course-list">
                        @php
                            $now = now();
                        @endphp
                        @foreach ($courses as $course)
                            @php
                                $imageLink = route('courses.show', $course->id);
                                $isYouTube = false;
                                if ($course->onlineDetail && 
                                    strtolower($course->onlineDetail->platform ?? '') === 'youtube' && 
                                    !empty($course->onlineDetail->meeting_link)) {
                                    $imageLink = $course->onlineDetail->meeting_link;
                                    $isYouTube = true;
                                }
                            @endphp
                            @php
                                $isParticipant = auth()->check() && isset($participantCourseIds) && in_array($course->id, $participantCourseIds);
                                $participantIdsByCourse = $participantIdsByCourse ?? [];
                            @endphp
                            <div class="course-item">
                                <div class="course-image-wrapper row g-3">
                                    <div class="col-auto">
                                        <a href="{{ $imageLink }}" class="text-decoration-none course-image-link @if($isYouTube) course-image-youtube @endif" @if($isYouTube) target="_blank" @endif>
                                            @if(!empty($course->image))
                                                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" 
                                                     alt="{{ strip_tags($course->title) }}" 
                                                     class="course-thumbnail">
                                            @else
                                                <div class="course-thumbnail course-thumbnail-placeholder">
                                                    <i class="bi bi-mortarboard"></i>
                                                </div>
                                            @endif
                                            @if($isYouTube)
                                                <div class="course-play-overlay">
                                                    <i class="bi bi-play-circle-fill"></i>
                                                </div>
                                            @endif
                                        </a>
                                    </div>
                                    @if($isParticipant)
                                        <div class="col d-flex align-items-center justify-content-center">
                                            @php
                                                // Użyj participant_id jeśli dostępne (bardziej precyzyjne), w przeciwnym razie course_id
                                                $participantId = $participantIdsByCourse[$course->id] ?? null;
                                                $certificateRoute = $participantId 
                                                    ? route('certificates.generate.by-participant', $participantId)
                                                    : route('certificates.generate', $course->id);
                                            @endphp
                                            <a href="{{ $certificateRoute }}" class="course-certificate-link" title="Pobierz zaświadczenie">
                                                <img src="{{ asset('images/certificate.png') }}" alt="Zaświadczenie" class="course-certificate-icon">
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                @php
                                    // Dla Akademii Dyrektora, tytuł też prowadzi do YouTube (jeśli dostępne)
                                    $titleLink = route('courses.show', $course->id);
                                    if (($pageTitle ?? '') === 'Akademia Dyrektora' && $isYouTube) {
                                        $titleLink = $imageLink;
                                    }
                                @endphp
                                <a href="{{ $titleLink }}" class="text-decoration-none course-link" @if(($pageTitle ?? '') === 'Akademia Dyrektora' && $isYouTube) target="_blank" @endif>
                                    <div class="course-content">
                                        <h5 class="course-title">
                                            {!! $course->title !!}
                                        </h5>
                                        <div class="course-meta text-muted small">
                                            <div class="mb-1">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <strong>Data:</strong> {{ $course->formatted_date }}
                                            </div>
                                            <div>
                                                <i class="bi bi-person me-1"></i>
                                                <strong>{{ $course->trainer_title }}:</strong> {{ $course->trainer }}
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        {{ $courses->links('pagination::bootstrap-4') }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <p class="mb-0">Brak dostępnych bezpłatnych szkoleń.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .course-list {
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .course-item {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 1.5rem;
        transition: transform 0.2s ease;
    }
    
    .course-item:hover {
        transform: translateY(-2px);
    }
    
    .course-link {
        display: block;
        color: inherit;
    }
    
    .course-link:hover {
        text-decoration: none;
    }
    
    .course-image-wrapper {
        margin-bottom: 1rem;
    }
    
    .course-certificate-link {
        display: inline-block;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .course-certificate-icon {
        width: 250px;
        height: auto;
        display: block;
        transition: all 0.3s ease;
        filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
    }
    
    .course-certificate-link:hover .course-certificate-icon {
        transform: scale(1.1);
        filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
    }
    
    .course-image-link {
        position: relative;
        display: inline-block;
    }
    
    .course-image-youtube .course-thumbnail {
        transition: opacity 0.3s ease;
    }
    
    .course-image-youtube:hover .course-thumbnail {
        opacity: 0.8;
    }
    
    .course-play-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
        pointer-events: none;
    }
    
    .course-image-youtube:hover .course-play-overlay {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.1);
    }
    
    .course-play-overlay i {
        font-size: 5rem;
        color: #ff0000;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }
    
    .course-thumbnail {
        width: 480px;
        height: auto;
        max-width: 100%;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        display: block;
    }
    
    .course-thumbnail-placeholder {
        width: 480px;
        height: 270px;
        max-width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    
    .course-thumbnail-placeholder i {
        font-size: 4rem;
        color: #adb5bd;
    }
    
    .course-content {
        padding: 0;
    }
    
    .course-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #1976d2;
        line-height: 1.4;
    }
    
    .course-link:hover .course-title {
        color: #1565c0;
        text-decoration: underline;
    }
    
    .course-meta {
        font-size: 0.9rem;
        line-height: 1.6;
    }
    
    .course-meta strong {
        color: #495057;
    }
    
    @media (max-width: 768px) {
        .course-list {
            gap: 1.5rem;
        }
        
        .course-thumbnail,
        .course-thumbnail-placeholder {
            width: 100%;
            max-width: 100%;
        }
        
        .course-thumbnail-placeholder {
            height: 200px;
        }
        
        .course-thumbnail-placeholder i {
            font-size: 3rem;
        }
        
        .course-title {
            font-size: 1rem;
        }
        
        .course-meta {
            font-size: 0.85rem;
        }
    }
</style>
@endpush

