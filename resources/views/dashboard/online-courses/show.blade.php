@extends('layouts.app')

@section('title', ($course->title ?? 'Kurs').' – Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>@include('dashboard.partials.sidebar-nav')</nav>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                @php($imgUrl = $course->publicImageUrl())
                @if($imgUrl)
                    <div class="bg-body-secondary text-center p-4 border-bottom">
                        <img src="{{ $imgUrl }}" alt="Okładka: {{ $course->title }}" class="img-fluid rounded-3" style="max-height: 280px; object-fit: contain;">
                    </div>
                @endif
                <div class="card-body py-4">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.online-courses.index') }}">Kursy online</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $course->title }}</li>
                        </ol>
                    </nav>

                    <h1 class="h3 mb-2">{{ $course->title }}</h1>
                    @if($course->instructor)
                        <p class="text-muted mb-3">{{ $course->instructor->full_name_with_title }}</p>
                    @endif

                    @if(($lessonProgress['total'] ?? 0) > 0)
                        @php($pct = min(100, (int) round(100 * (int) ($lessonProgress['completed'] ?? 0) / (int) $lessonProgress['total'])))
                        <p class="small text-muted mb-1">Ukończone lekcje: {{ (int) $lessonProgress['completed'] }} z {{ (int) $lessonProgress['total'] }}</p>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                        </div>
                    @endif

                    @if($course->description)
                        <div class="mb-4">{!! nl2br(e($course->description)) !!}</div>
                    @endif

                    @if(!empty($certificateContext['show']))
                        <div class="mb-4">
                            @include('dashboard.online-courses.partials.certificate-cta', ['context' => $certificateContext])
                        </div>
                    @endif

                    @if($firstLesson)
                        <a href="{{ route('dashboard.online-courses.lesson', [$enrollment, $firstLesson]) }}" class="btn btn-primary">
                            {{ ($lessonProgress['completed'] ?? 0) > 0 ? 'Kontynuuj naukę' : 'Rozpocznij naukę' }}
                        </a>
                    @else
                        <p class="text-muted mb-0">Ten kurs nie ma jeszcze opublikowanych lekcji.</p>
                    @endif
                </div>
            </div>

            @if($course->modulesWithPublishedLessons->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h2 class="h5 mb-0">Moduły i lekcje</h2>
                    </div>
                    <div class="card-body">
                        @foreach($course->modulesWithPublishedLessons as $module)
                            <div class="mb-4">
                                <h3 class="h6 text-uppercase text-muted mb-2">{{ $module->title }}</h3>
                                <ul class="list-group list-group-flush">
                                    @foreach($module->lessons as $lesson)
                                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                            <a href="{{ route('dashboard.online-courses.lesson', [$enrollment, $lesson]) }}" class="text-decoration-none">
                                                {{ $lesson->title }}
                                            </a>
                                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
@endpush
