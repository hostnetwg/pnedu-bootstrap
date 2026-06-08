@if(! request()->routeIs('dashboard.online-courses.lesson'))
<div class="dashboard-sidebar-offer">
    <div class="dashboard-sidebar-offer__header">
        <span class="dashboard-sidebar-offer__badge">Aktualna oferta</span>
        <span class="dashboard-sidebar-offer__heading">Zapisz się na szkolenie</span>
        <span class="dashboard-sidebar-offer__lead">Wybierz temat i termin</span>
    </div>

    <div class="dashboard-sidebar-upcoming">
        @forelse($dashboardUpcomingCourses ?? [] as $course)
            @php
                $start = \Carbon\Carbon::parse($course->start_date)->locale('pl');
                $titlePlain = \Illuminate\Support\Str::limit(strip_tags((string) $course->title), 90);
                $priceInfo = $course->is_paid ? $course->getCurrentPrice() : null;
            @endphp
            <a href="{{ route('courses.show', $course->id) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="dashboard-sidebar-upcoming__item @if(! $course->is_paid) dashboard-sidebar-upcoming__item--free @endif"
               title="{{ $titlePlain }}">
                <span class="dashboard-sidebar-upcoming__top">
                    <span class="dashboard-sidebar-upcoming__date">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        {{ $start->format('d.m.Y') }} · {{ $start->format('H:i') }}
                    </span>
                    @if(! $course->is_paid)
                        <span class="dashboard-sidebar-upcoming__tag dashboard-sidebar-upcoming__tag--free">Bezpłatne</span>
                    @elseif($priceInfo)
                        <span class="dashboard-sidebar-upcoming__tag dashboard-sidebar-upcoming__tag--paid">
                            {{ number_format($priceInfo['price'], 0, ',', ' ') }} PLN
                        </span>
                    @endif
                </span>
                <span class="dashboard-sidebar-upcoming__title">{{ $titlePlain }}</span>
                <span class="dashboard-sidebar-upcoming__trainer">
                    <span class="dashboard-sidebar-upcoming__trainer-label">{{ $course->trainer_title }}</span>
                    <span class="dashboard-sidebar-upcoming__trainer-details">
                        @if($course->instructor)
                            @if(filled($course->instructor->title))
                                <span class="dashboard-sidebar-upcoming__trainer-title">{{ $course->instructor->title }}</span>
                            @endif
                            <span class="dashboard-sidebar-upcoming__trainer-name">{{ $course->instructor->full_name }}</span>
                        @else
                            <span class="dashboard-sidebar-upcoming__trainer-name">{{ $course->trainer }}</span>
                        @endif
                    </span>
                </span>
                <span class="dashboard-sidebar-upcoming__action">
                    @if(! $course->is_paid)
                        Zapisz się
                    @else
                        Zamów szkolenie
                    @endif
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </span>
            </a>
        @empty
            <p class="dashboard-sidebar-upcoming__empty">Brak dostępnych terminów — sprawdź pełną ofertę.</p>
        @endforelse
    </div>

    <div class="dashboard-sidebar-offer__footer">
        <a href="{{ route('courses.individual') }}"
           target="_blank"
           rel="noopener noreferrer"
           class="dashboard-sidebar-offer__all-link"
           @if(request()->routeIs('courses.individual')) aria-current="page" @endif>
            Zobacz pełną ofertę
            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
        </a>
    </div>
</div>
@endif
