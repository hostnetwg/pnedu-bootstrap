<div @class([
    'js-dashboard-offer-sidebar dashboard-offer-sidebar-mount',
    $offerMountClass ?? null,
])
     data-offer-url="{{ url('/dashboard/fragments/aktualna-oferta') }}"
     aria-live="polite"
     aria-busy="true">
    @include('dashboard.partials.sidebar-nav-offer-skeleton')
</div>
