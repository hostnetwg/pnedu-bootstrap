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
    padding: 0.95rem;
    border-radius: 1rem;
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.04);
}
.dashboard-sidebar-offer__header {
    padding: 0 0.15rem 0.85rem;
    margin-bottom: 0.15rem;
    border-bottom: 1px solid #dee2e6;
    border-left: 3px solid #c6a300;
    padding-left: 0.75rem;
}
.dashboard-sidebar-offer__badge {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.15rem 0.45rem;
    margin-bottom: 0.35rem;
    border-radius: 0.25rem;
    background: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}
.dashboard-sidebar-offer__heading {
    display: block;
    font-size: 1.02rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.25;
}
.dashboard-sidebar-offer__lead {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    line-height: 1.35;
    color: #6c757d;
}
.dashboard-sidebar-upcoming {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    max-height: min(92vh, calc(4 * 11.5rem + 1.25rem));
    overflow-y: auto;
    padding: 0.25rem 0.05rem 0.25rem 0;
    background: #f1f3f5;
    scrollbar-width: thin;
    scrollbar-color: #ced4da transparent;
}
.dashboard-sidebar-upcoming::-webkit-scrollbar {
    width: 4px;
}
.dashboard-sidebar-upcoming::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 999px;
}
.dashboard-sidebar-upcoming__item {
    display: block;
    flex-shrink: 0;
    padding: 0;
    border-radius: 0.65rem;
    border: 1px solid #e9ecef;
    background: #fff;
    text-decoration: none;
    color: inherit;
    overflow: visible;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.dashboard-sidebar-upcoming__item:hover,
.dashboard-sidebar-upcoming__item:focus-visible {
    border-color: #ced4da;
    box-shadow: 0 0.25rem 0.65rem rgba(0, 0, 0, 0.07);
    color: inherit;
}
.dashboard-sidebar-upcoming__top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    padding: 0.45rem 0.65rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eef0f2;
}
.dashboard-sidebar-upcoming__item--free .dashboard-sidebar-upcoming__top {
    background: rgba(25, 135, 84, 0.06);
    border-bottom-color: rgba(25, 135, 84, 0.12);
}
.dashboard-sidebar-upcoming__date {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #495057;
}
.dashboard-sidebar-upcoming__date i {
    color: #0d6efd;
    font-size: 0.8rem;
}
.dashboard-sidebar-upcoming__item--free .dashboard-sidebar-upcoming__date i {
    color: #198754;
}
.dashboard-sidebar-upcoming__tag {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    padding: 0.1rem 0.38rem;
    border-radius: 999px;
}
.dashboard-sidebar-upcoming__tag--free {
    color: #146c43;
    background: rgba(25, 135, 84, 0.14);
}
.dashboard-sidebar-upcoming__tag--paid {
    color: #084298;
    background: rgba(13, 110, 253, 0.1);
    border: 1px solid rgba(13, 110, 253, 0.18);
}
.dashboard-sidebar-upcoming__title {
    display: block;
    padding: 0.55rem 0.65rem 0;
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.35;
    color: #212529;
    overflow-wrap: anywhere;
}
.dashboard-sidebar-upcoming__trainer {
    display: block;
    padding: 0.3rem 0.65rem 0.15rem;
    font-size: 0.72rem;
    line-height: 1.4;
}
.dashboard-sidebar-upcoming__trainer-label {
    font-weight: 600;
    color: #868e96;
    margin-right: 0.15rem;
}
.dashboard-sidebar-upcoming__trainer-details {
    color: #495057;
}
.dashboard-sidebar-upcoming__trainer-title {
    font-weight: 600;
    margin-right: 0.15rem;
}
.dashboard-sidebar-upcoming__trainer-name {
    font-weight: 500;
}
.dashboard-sidebar-upcoming__action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.45rem;
    padding: 0.45rem 0.65rem;
    font-size: 0.74rem;
    font-weight: 700;
    color: #0d6efd;
    background: rgba(13, 110, 253, 0.06);
    border-top: 1px solid rgba(13, 110, 253, 0.1);
}
.dashboard-sidebar-upcoming__item--free .dashboard-sidebar-upcoming__action {
    color: #198754;
    background: rgba(25, 135, 84, 0.08);
    border-top-color: rgba(25, 135, 84, 0.12);
}
.dashboard-sidebar-upcoming__action i {
    font-size: 0.85rem;
    transition: transform 0.15s ease;
}
.dashboard-sidebar-upcoming__item:hover .dashboard-sidebar-upcoming__action i,
.dashboard-sidebar-upcoming__item:focus-visible .dashboard-sidebar-upcoming__action i {
    transform: translateX(2px);
}
.dashboard-sidebar-upcoming__empty {
    margin: 0;
    padding: 0.75rem 0.25rem;
    font-size: 0.82rem;
    color: #6c757d;
    text-align: center;
}
.dashboard-sidebar-offer__footer {
    margin-top: 0.85rem;
    padding-top: 0.75rem;
    border-top: 1px solid #dee2e6;
    background: #f1f3f5;
}
.dashboard-sidebar-offer__all-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.1rem;
    padding: 0.48rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #212529;
    text-decoration: none;
    border-radius: 0.5rem;
    background: #fff;
    border: 1px solid #dee2e6;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}
.dashboard-sidebar-offer__all-link:hover,
.dashboard-sidebar-offer__all-link:focus-visible {
    color: #fff;
    background: #212529;
    border-color: #212529;
    text-decoration: none;
}
.dashboard-sidebar-offer__all-link[aria-current="page"] {
    color: #fff;
    background: #495057;
    border-color: #495057;
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
        margin-bottom: 1rem;
    }
    .dashboard-sidebar-upcoming {
        max-height: none;
    }
}
</style>
