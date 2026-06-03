<style>
.dashboard-minimal-menu {
    background: #fff;
    padding: 0;
    margin: 0;
}
.dashboard-minimal-menu li {
    margin-bottom: 0.5rem;
}
.dashboard-minimal-menu a {
    color: #6c757d;
    font-size: 1.08rem;
    padding: 0.75rem 1rem 0.75rem 0.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
    font-weight: 400;
    position: relative;
    display: block;
}
.dashboard-minimal-menu a.active, .dashboard-minimal-menu a:hover {
    color: #0d6efd;
    background: #f5f7fa;
}
.dashboard-minimal-menu i {
    font-size: 1.2rem;
    color: #adb5bd;
    transition: color 0.15s;
}
.dashboard-minimal-menu a.active i, .dashboard-minimal-menu a:hover i {
    color: #0d6efd;
}
.dashboard-sidebar-offer {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e9ecef;
}
.dashboard-sidebar-offer__header {
    margin-bottom: 0.75rem;
}
.dashboard-sidebar-offer__badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.2rem 0.5rem;
    margin-bottom: 0.35rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);
    color: #fff;
}
.dashboard-sidebar-offer__heading {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    color: #212529;
    line-height: 1.3;
}
.dashboard-sidebar-upcoming {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    max-height: min(52vh, 28rem);
    overflow-y: auto;
    padding-right: 0.15rem;
}
.dashboard-sidebar-upcoming__item {
    display: block;
    padding: 0.65rem 0.75rem;
    border-radius: 0.55rem;
    border: 1px solid #e9ecef;
    background: #fff;
    text-decoration: none;
    color: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
.dashboard-sidebar-upcoming__item:hover,
.dashboard-sidebar-upcoming__item:focus-visible {
    border-color: #b6d4fe;
    background: #f8fbff;
    box-shadow: 0 0.15rem 0.45rem rgba(13, 110, 253, 0.12);
    color: inherit;
}
.dashboard-sidebar-upcoming__date {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #0d6efd;
    margin-bottom: 0.25rem;
}
.dashboard-sidebar-upcoming__date i {
    margin-right: 0.2rem;
}
.dashboard-sidebar-upcoming__title {
    display: block;
    font-size: 0.82rem;
    font-weight: 500;
    line-height: 1.35;
    color: #212529;
}
.dashboard-sidebar-upcoming__trainer {
    display: block;
    margin-top: 0.3rem;
    font-size: 0.76rem;
    line-height: 1.35;
    color: #495057;
}
.dashboard-sidebar-upcoming__trainer-label {
    font-weight: 600;
    color: #6c757d;
}
.dashboard-sidebar-upcoming__trainer-title {
    font-weight: 600;
    margin-right: 0.2rem;
}
.dashboard-sidebar-upcoming__trainer-name {
    font-weight: 500;
}
.dashboard-sidebar-upcoming__meta {
    display: inline-block;
    margin-top: 0.35rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #6c757d;
}
.dashboard-sidebar-upcoming__meta--free {
    color: #198754;
}
.dashboard-sidebar-upcoming__empty {
    margin: 0;
    font-size: 0.82rem;
    color: #6c757d;
}
.dashboard-sidebar-offer__all-link {
    display: inline-flex;
    align-items: center;
    gap: 0.1rem;
    margin-top: 0.75rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0d6efd;
    text-decoration: none;
}
.dashboard-sidebar-offer__all-link:hover,
.dashboard-sidebar-offer__all-link:focus-visible {
    color: #0a58ca;
    text-decoration: underline;
}
.dashboard-sidebar-offer__all-link[aria-current="page"] {
    color: #084298;
}
@media (max-width: 991.98px) {
    .dashboard-minimal-menu {
        display: flex;
        flex-direction: row;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .dashboard-minimal-menu li {
        margin-bottom: 0;
    }
    .dashboard-minimal-menu a {
        padding: 0.5rem 0.9rem;
        font-size: 1rem;
    }
    .dashboard-sidebar-offer {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
        margin-bottom: 1rem;
    }
    .dashboard-sidebar-upcoming {
        max-height: none;
    }
}
</style>
