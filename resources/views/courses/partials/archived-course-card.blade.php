<div class="col">
    <div class="card h-100 border-0 shadow-sm hover-lift course-card-archived">
        <div class="position-relative">
            <a href="{{ route('courses.show', $course->id) }}" class="d-block text-decoration-none course-card-archived-media" style="color: inherit;">
                @if(!empty($course->image))
                    <img src="{{ $course->publicImageUrl() }}" class="card-img-top" alt="{{ $course->title }}">
                @else
                    <div class="card-img-top d-flex align-items-center justify-content-center mb-2" style="width:100%; aspect-ratio:1/1; background:#e9ecef; border: 2px solid #dee2e6; border-radius: .5rem; border-style:dashed;">
                        <i class="bi bi-mortarboard" style="font-size: 4rem; color: #f8f9fa;"></i>
                    </div>
                @endif
            </a>
        </div>
        <div class="card-body d-flex flex-column p-4">
            <h5 class="card-title fw-bold mb-3">{!! $course->title !!}</h5>
            @php
                $start = \Carbon\Carbon::parse($course->start_date)->locale('pl');
                $end = $course->end_date ? \Carbon\Carbon::parse($course->end_date) : null;
            @endphp
            <ul class="list-unstyled mb-3">
                <li><strong>Data:</strong> {{ $start->format('d.m.Y') }}</li>
                <li><strong>Godzina:</strong> {{ $start->format('H:i') }}@if($end) ({{ $start->diffInMinutes($end) }} min)@endif</li>
                <li><strong>Dzień tygodnia:</strong> {{ $start->translatedFormat('l') }}</li>
            </ul>
            <p class="card-text">
                <strong>{{ $course->trainer_title }}:</strong> {{ $course->trainer }}
            </p>
            <div class="mt-auto pt-3">
                <a href="{{ route('courses.show', $course->id) }}"
                   class="btn btn-outline-secondary w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm"
                   style="font-size:1.15rem; letter-spacing:0.5px;">
                    <span>Zobacz szczegóły</span>
                    <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    </div>
</div>
