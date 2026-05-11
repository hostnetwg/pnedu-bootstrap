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
                    <h2 class="h4 mb-4">Moje dane</h2>
                    <p>Zarządzaj swoimi danymi osobowymi, adresem e-mail oraz hasłem. Tutaj możesz zaktualizować swoje informacje lub zmienić ustawienia konta.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
@endpush