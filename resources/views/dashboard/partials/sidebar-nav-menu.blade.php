@php
    $szkoleniaCount = (int) ($dashboardSzkoleniaCount ?? 0);
    $onlineCoursesCount = (int) ($dashboardOnlineCoursesCount ?? 0);
    $hasLearningResources = $szkoleniaCount > 0 || $onlineCoursesCount > 0;
@endphp

<ul class="list-unstyled dashboard-minimal-menu">
    <li>
        @if($hasLearningResources)
            <span class="dashboard-minimal-menu__label d-flex align-items-center gap-2">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i> Twoje zasoby
            </span>
        @else
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active @endif">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i> Twoje zasoby
            </a>
        @endif
    </li>
    <li>
        <a href="{{ route('dashboard.szkolenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.szkolenia') || request()->routeIs('dashboard.szkolenia.wideo')) active @endif">
            <i class="bi bi-journal-text"></i> Twoje szkolenia ({{ $dashboardSzkoleniaCount ?? 0 }})
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
