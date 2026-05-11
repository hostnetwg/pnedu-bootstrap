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
}
</style>
