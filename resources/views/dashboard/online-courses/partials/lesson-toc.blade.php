<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body py-3">
        <div class="small text-muted mb-2">{{ $course->title }}</div>
        @foreach($course->modulesWithPublishedLessons as $mod)
            <div class="fw-semibold mt-3 mb-1">{{ $mod->title }}</div>
            <ul class="list-unstyled mb-0 small">
                @foreach($mod->lessons as $les)
                    @php($lesDone = in_array((int) $les->id, $completedLessonIds, true))
                    <li class="mb-1" data-oc-lesson-id="{{ $les->id }}">
                        <div class="d-flex align-items-start gap-1">
                            <a href="{{ route('dashboard.online-courses.lesson', [$enrollment, $les]) }}"
                               class="d-flex align-items-start gap-2 text-start flex-grow-1 min-w-0 {{ $les->id === $lesson->id ? 'fw-bold text-primary' : 'text-decoration-none text-body' }}">
                                @if($lesDone)
                                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1 js-oc-lesson-status-icon" aria-hidden="true"></i>
                                    <span><span class="visually-hidden">Ukończone: </span>{{ $les->title }}</span>
                                @else
                                    <i class="bi bi-circle text-secondary flex-shrink-0 mt-1 js-oc-lesson-status-icon" aria-hidden="true"></i>
                                    <span><span class="visually-hidden">Do zrobienia: </span>{{ $les->title }}</span>
                                @endif
                            </a>
                            @if(array_key_exists((string) (int) $les->id, $lessonNotesForSidebar))
                                <button type="button"
                                        class="btn btn-link p-0 border-0 lh-1 mt-1 text-primary flex-shrink-0 js-oc-lesson-note-popover"
                                        data-oc-note-lesson-id="{{ $les->id }}"
                                        aria-label="Podgląd notatki do lekcji: {{ $les->title }}">
                                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>
