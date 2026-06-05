@extends('layouts.certificate-registration')

@section('title', 'Rejestracja zaświadczenia – ' . config('app.name'))

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Ten sam układ i treść co modal na stronie głównej (welcome.blade.php) --}}
            <div class="modal-dialog modal-lg modal-dialog-centered mx-auto">
                <div class="modal-content border-0 shadow-lg overflow-hidden">
                    <div class="bg-success bg-gradient text-white px-3 py-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="me-2"><i class="bi bi-patch-check-fill"></i></span>
                            <h2 class="h6 mb-0 text-uppercase">Rejestracja zaświadczenia</h2>
                        </div>
                    </div>
                    <div class="modal-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-center">
                        <div class="me-md-4 mb-3 mb-md-0 text-center">
                            <img src="{{ asset('logo-pne.png') }}" alt="Platforma Nowoczesnej Edukacji" style="max-width: 210px; height: auto;">
                        </div>
                        <div class="text-center">
                            @include('certificate-registration.partials.thanks-content', ['updated' => !empty($updated)])
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 pe-4 justify-content-center">
                        <a href="{{ route('home') }}" class="btn btn-primary btn-lg px-4">Zamknij</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
