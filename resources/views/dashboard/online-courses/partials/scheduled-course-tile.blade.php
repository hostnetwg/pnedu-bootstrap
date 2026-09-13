@php
    $imgUrl = $enrollment->onlineCourse->publicImageUrl();
@endphp
<div class="col d-flex">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 w-100 d-flex flex-column pending-course-tile">
        <div class="online-course-tile-media bg-body-secondary border-bottom position-relative">
            @if($imgUrl)
                <img src="{{ $imgUrl }}"
                     class="online-course-tile-img pending-course-tile-img"
                     alt="Okładka kursu: {{ $enrollment->onlineCourse->title }}"
                     loading="lazy"
                     decoding="async">
            @else
                <div class="online-course-tile-placeholder text-muted small">
                    <i class="bi bi-collection-play d-block fs-2 mb-2 opacity-50" aria-hidden="true"></i>
                    Brak okładki
                </div>
            @endif
            <div class="pending-course-tile-lock" aria-hidden="true">
                <i class="bi bi-lock-fill"></i>
            </div>
        </div>
        <div class="card-body d-flex flex-column flex-grow-1 pt-3">
            <h3 class="h6 mb-2">{{ $enrollment->onlineCourse->title }}</h3>
            @if($enrollment->onlineCourse->instructor)
                <p class="small text-muted mb-2">{{ $enrollment->onlineCourse->instructor->full_name_with_title }}</p>
            @endif
            <div class="alert alert-info small mb-0">
                <strong>Dostęp od {{ $enrollment->accessStartLabel() }}.</strong>
                Materiały otworzą się automatycznie w tym dniu.
                @if(filled($enrollment->access_note))
                    <span class="d-block mt-2">{{ $enrollment->access_note }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
