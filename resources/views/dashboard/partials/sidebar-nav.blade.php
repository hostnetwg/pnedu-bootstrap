<ul class="list-unstyled dashboard-minimal-menu">
    <li>
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active @endif">
            <i class="bi bi-house-door"></i> Panel
        </a>
    </li>
    <li>
        <a href="{{ route('dashboard.szkolenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.szkolenia') || request()->routeIs('dashboard.szkolenia.wideo')) active @endif">
            <i class="bi bi-journal-text"></i> Moje szkolenia
        </a>
    </li>
    <li>
        <a href="{{ route('dashboard.online-courses.index') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.online-courses*')) active @endif">
            <i class="bi bi-collection-play"></i> Kursy online
        </a>
    </li>
    <li>
        <a href="{{ route('dashboard.zaswiadczenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.zaswiadczenia*')) active @endif">
            <i class="bi bi-award"></i> Zaświadczenia
        </a>
    </li>
</ul>

<div class="dashboard-sidebar-offer-cta">
    <a href="{{ route('courses.individual') }}"
       class="dashboard-sidebar-offer-cta__link"
       @if(request()->routeIs('courses.individual')) aria-current="page" @endif>
        <span class="dashboard-sidebar-offer-cta__badge">Aktualna oferta</span>
        <span class="dashboard-sidebar-offer-cta__title">
            <i class="bi bi-calendar2-week" aria-hidden="true"></i>
            Nasza oferta szkoleń
        </span>
        <span class="dashboard-sidebar-offer-cta__hint">Zobacz terminy i zapisz się na kolejne szkolenia</span>
    </a>
</div>
