<div @class([
    'js-dashboard-offer-sidebar dashboard-offer-sidebar-mount',
    $offerMountClass ?? null,
])
     data-offer-url="{{ url('/dashboard/fragments/aktualna-oferta') }}"
     aria-live="polite"
     aria-busy="true">
    @include('dashboard.partials.sidebar-nav-offer-skeleton')
</div>

@once
@push('scripts')
@include('dashboard.partials.sidebar-nav-offer-loader-script')
@endpush
@endonce
