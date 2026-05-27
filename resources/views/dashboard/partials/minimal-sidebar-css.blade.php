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
.dashboard-sidebar-offer-cta {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e9ecef;
}
.dashboard-sidebar-offer-cta__link {
    display: block;
    padding: 0.9rem 1rem;
    border-radius: 0.65rem;
    text-decoration: none;
    color: #fff;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 55%, #084298 100%);
    box-shadow: 0 0.35rem 0.9rem rgba(13, 110, 253, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.dashboard-sidebar-offer-cta__link:hover,
.dashboard-sidebar-offer-cta__link:focus-visible {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1.1rem rgba(13, 110, 253, 0.45);
}
.dashboard-sidebar-offer-cta__link[aria-current="page"] {
    outline: 2px solid rgba(255, 255, 255, 0.85);
    outline-offset: 2px;
}
.dashboard-sidebar-offer-cta__badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.2rem 0.5rem;
    margin-bottom: 0.45rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
}
.dashboard-sidebar-offer-cta__title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 1.02rem;
    font-weight: 600;
    line-height: 1.3;
}
.dashboard-sidebar-offer-cta__title i {
    font-size: 1.15rem;
    color: #fff;
}
.dashboard-sidebar-offer-cta__hint {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.8rem;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 400;
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
    .dashboard-sidebar-offer-cta {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
        margin-bottom: 1rem;
    }
}
</style>
