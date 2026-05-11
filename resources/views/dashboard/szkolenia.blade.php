@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>
                @include('dashboard.partials.sidebar-nav')
            </nav>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h2 class="h4 mb-2">Moje szkolenia</h2>
                    @include('dashboard.partials.szkolenia-list-inner', ['szkoleniaFilterRoute' => 'dashboard.szkolenia'])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
<style>
@include('dashboard.partials.szkolenia-training-styles')
</style>
@endpush
