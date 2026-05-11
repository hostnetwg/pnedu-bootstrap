@if($previousLesson || $nextLesson)
    <nav class="lesson-adjacent-nav d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $lessonNavExtraClass ?? 'mb-4' }}" aria-label="Nawigacja między lekcjami">
        <div class="flex-grow-1 flex-md-grow-0">
            @if($previousLesson)
                <a href="{{ route('dashboard.online-courses.lesson', [$enrollment, $previousLesson]) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left-short" aria-hidden="true"></i>
                    Poprzednia lekcja
                </a>
            @else
                <span class="btn btn-outline-secondary btn-sm disabled opacity-50">Poprzednia lekcja</span>
            @endif
        </div>
        <div class="flex-grow-1 flex-md-grow-0 text-md-end">
            @if($nextLesson)
                <a href="{{ route('dashboard.online-courses.lesson', [$enrollment, $nextLesson]) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                    Następna lekcja
                    <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                </a>
            @else
                <span class="btn btn-outline-secondary btn-sm disabled opacity-50">Następna lekcja</span>
            @endif
        </div>
    </nav>
@endif
