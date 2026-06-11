<div class="dashboard-sidebar-offer dashboard-sidebar-offer--skeleton">
    <div class="dashboard-sidebar-offer__header">
        <span class="dashboard-sidebar-offer__badge">Aktualna oferta</span>
        <span class="dashboard-sidebar-offer__heading">Zapisz się na szkolenie</span>
        <span class="dashboard-sidebar-offer__lead">Ładowanie terminów…</span>
    </div>

    <div class="dashboard-sidebar-upcoming dashboard-sidebar-upcoming--skeleton" aria-hidden="true">
        @for ($i = 0; $i < 3; $i++)
            <div class="dashboard-sidebar-upcoming__skeleton-item">
                <span class="dashboard-sidebar-upcoming__skeleton-line dashboard-sidebar-upcoming__skeleton-line--short"></span>
                <span class="dashboard-sidebar-upcoming__skeleton-line"></span>
                <span class="dashboard-sidebar-upcoming__skeleton-line dashboard-sidebar-upcoming__skeleton-line--medium"></span>
            </div>
        @endfor
    </div>
</div>
