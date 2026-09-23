@extends('layouts.certificate-registration')

@section('title', 'Dostęp do nagrania – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2 text-center text-uppercase text-primary">Dostęp do nagrania</h1>
                    @if(!empty($courseTitle))
                        <p class="fs-5 fw-semibold text-dark text-center mb-3">„{{ $courseTitle }}”</p>
                    @endif
                    @if(!empty($courseStartDisplay))
                        <p class="text-center text-muted small mb-3">Data szkolenia: <span class="text-body">{{ $courseStartDisplay }}</span></p>
                    @endif
                    <p class="mb-4 text-center">{{ $message }}</p>
                    <div class="text-center">
                        <a href="{{ route('home') }}" class="btn btn-primary">Strona główna</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
