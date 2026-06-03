<ul class="list-unstyled dashboard-minimal-menu">
    <li>
        <a href="{{ $dashboardTwojeZasobyUrl ?? route('dashboard') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active @endif">
            <i class="bi bi-grid-1x2-fill"></i> Twoje zasoby
        </a>
    </li>
    <li>
        <a href="{{ route('dashboard.szkolenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.szkolenia') || request()->routeIs('dashboard.szkolenia.wideo')) active @endif">
            <i class="bi bi-journal-text"></i> Moje szkolenia ({{ $dashboardSzkoleniaCount ?? 0 }})
        </a>
    </li>
    <li>
        <a href="{{ route('dashboard.online-courses.index') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.online-courses*')) active @endif">
            <i class="bi bi-collection-play"></i> Kursy online ({{ $dashboardOnlineCoursesCount ?? 0 }})
        </a>
    </li>
    <li>
        <a href="{{ route('dashboard.zaswiadczenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.zaswiadczenia*')) active @endif">
            <i class="bi bi-award"></i> Zaświadczenia ({{ $dashboardZaswiadczeniaCount ?? 0 }})
        </a>
    </li>
</ul>

<div class="dashboard-sidebar-offer">
    <div class="dashboard-sidebar-offer__header">
        <span class="dashboard-sidebar-offer__badge">Aktualna oferta</span>
        <span class="dashboard-sidebar-offer__heading">Najbliższe szkolenia</span>
    </div>

    <div class="dashboard-sidebar-upcoming">
        @forelse($dashboardUpcomingCourses ?? [] as $course)
            @php
                $start = \Carbon\Carbon::parse($course->start_date)->locale('pl');
                $titlePlain = \Illuminate\Support\Str::limit(strip_tags((string) $course->title), 90);
            @endphp
            <a href="{{ route('courses.show', $course->id) }}"
               class="dashboard-sidebar-upcoming__item"
               title="{{ $titlePlain }}">
                <span class="dashboard-sidebar-upcoming__date">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    {{ $start->format('d.m.Y') }} · {{ $start->format('H:i') }}
                </span>
                <span class="dashboard-sidebar-upcoming__title">{{ $titlePlain }}</span>
                <span class="dashboard-sidebar-upcoming__trainer">
                    <span class="dashboard-sidebar-upcoming__trainer-label">{{ $course->trainer_title }}:</span>
                    @if($course->instructor)
                        @if(filled($course->instructor->title))
                            <span class="dashboard-sidebar-upcoming__trainer-title">{{ $course->instructor->title }}</span>
                        @endif
                        <span class="dashboard-sidebar-upcoming__trainer-name">{{ $course->instructor->full_name }}</span>
                    @else
                        <span class="dashboard-sidebar-upcoming__trainer-name">{{ $course->trainer }}</span>
                    @endif
                </span>
                @if(! $course->is_paid)
                    <span class="dashboard-sidebar-upcoming__meta dashboard-sidebar-upcoming__meta--free">Bezpłatne</span>
                @endif
            </a>
        @empty
            <p class="dashboard-sidebar-upcoming__empty">Brak zaplanowanych szkoleń.</p>
        @endforelse
    </div>

    <a href="{{ route('courses.individual') }}"
       class="dashboard-sidebar-offer__all-link"
       @if(request()->routeIs('courses.individual')) aria-current="page" @endif>
        Zobacz pełną ofertę
        <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
    </a>
</div>
