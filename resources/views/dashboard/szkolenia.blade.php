@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-3 order-1 order-lg-1 mb-4 mb-lg-0">
            <nav>
                @include('dashboard.partials.sidebar-nav-menu')
            </nav>
            <div class="d-none d-lg-block">
                @include('dashboard.partials.sidebar-nav-offer-mount', ['offerMountClass' => ''])
            </div>
        </div>
        <div class="col-12 col-lg-9 order-2 order-lg-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h2 class="h4 mb-2">Twoje szkolenia</h2>
                    <div class="js-szkolenia-list-root"
                         data-fragment-url="{{ url('/dashboard/fragments/szkolenia-list') }}"
                         data-page-url="{{ url('/dashboard/szkolenia') }}"
                         aria-live="polite"
                         aria-busy="false">
                        @include('dashboard.partials.szkolenia-list-inner', ['szkoleniaFilterRoute' => 'dashboard.szkolenia'])
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 d-lg-none order-3">
            @include('dashboard.partials.sidebar-nav-offer-mount', ['offerMountClass' => ''])
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
<style>
@include('dashboard.partials.szkolenia-training-styles')
.szkolenia-list-root--loading { opacity: 0.55; pointer-events: none; transition: opacity 0.15s ease; }
</style>
@endpush

@once
@push('scripts')
@include('dashboard.partials.szkolenia-list-ajax-script')
@endpush
@endonce
