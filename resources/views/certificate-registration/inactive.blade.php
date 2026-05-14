@extends('layouts.app')

@section('title', 'Rejestracja zaświadczenia – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2 text-center text-uppercase text-primary">Rejestracja zaświadczenia</h1>
                    @if(!empty($courseTitle))
                        <p class="fs-5 fw-semibold text-dark text-center {{ !empty($instructorName) ? 'mb-2' : 'mb-4' }}">„{{ $courseTitle }}”</p>
                    @endif
                    @if(!empty($courseStartDisplay))
                        <p class="text-center text-muted small {{ !empty($instructorName) ? 'mb-2' : 'mb-3' }}">Data rozpoczęcia: <span class="text-body">{{ $courseStartDisplay }}</span></p>
                    @endif
                    @if(!empty($instructorName))
                        <p class="text-muted mb-4 d-flex align-items-center justify-content-center">
                            @if(!empty($instructorPhoto))
                                <img src="{{ rtrim(config('services.pneadm.public_url'), '/') . '/storage/' . ltrim($instructorPhoto, '/') }}" alt="{{ $instructorName }}" class="rounded me-2" style="max-width: 48px; height: auto;">
                            @endif
                            <span><strong>Prowadzący:</strong> {{ $instructorName }}</span>
                        </p>
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
