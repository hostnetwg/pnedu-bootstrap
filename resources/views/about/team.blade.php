@extends('layouts.app')

@section('title', 'Zespół - O nas - Platforma Nowoczesnej Edukacji')

@section('content')

<!-- ===== HERO BANNER ======================================= -->
<div class="bg-primary bg-gradient text-white py-3 text-center">
    <div class="container">
        <p class="lead fw-semibold mb-0">
            Zespół<br>
            <span style="color: #c6a300; font-style: normal; font-weight: 600;">
                Poznaj naszych ekspertów
            </span>
        </p>
    </div>
</div>

<!-- ===== TEAM SECTION ======================================= -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Nasz zespół</h2>
                <p class="lead">Poznaj ekspertów, którzy tworzą Platformę Nowoczesnej Edukacji</p>
            </div>
        </div>
        
        {{-- Dyrektor --}}
        @if(isset($director) && $director)
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mb-4">Dyrektor</h3>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                    @if(!empty($director->photo))
                                        <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($director->photo, '/') }}" 
                                             alt="{{ $director->full_name }}" 
                                             class="img-fluid rounded shadow-sm"
                                             style="max-width: 200px;">
                                    @else
                                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto;">
                                            <i class="bi bi-person-circle text-white" style="font-size: 5rem;"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-9">
                                    <h3 class="mb-3">
                                        @if(!empty($director->title))
                                            {{ $director->title }} 
                                        @endif
                                        {{ $director->full_name }}
                                    </h3>
                                    @if(!empty($director->bio_html))
                                        <div class="mb-3">
                                            {!! $director->bio_html !!}
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">
                                            <em>Opis zostanie dodany później</em>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Lista trenerów --}}
        @if(isset($instructors) && $instructors->count() > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mb-4">Edukatorzy</h3>
                </div>
            </div>
            <div class="row g-4">
                @foreach($instructors as $instructor)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center mb-3 mb-md-0">
                                        @if(!empty($instructor->photo))
                                            <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($instructor->photo, '/') }}" 
                                                 alt="{{ $instructor->full_name }}" 
                                                 class="img-fluid rounded shadow-sm"
                                                 style="max-width: 200px;">
                                        @else
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center mx-auto" style="width: 200px; height: 200px;">
                                                <i class="bi bi-person-circle text-white" style="font-size: 5rem;"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-9">
                                        <h5 class="mb-3">
                                            @if(!empty($instructor->title))
                                                {{ $instructor->title }} 
                                            @endif
                                            {{ $instructor->full_name }}
                                        </h5>
                                        @if(!empty($instructor->bio_html))
                                            <div class="card-text">
                                                {!! $instructor->bio_html !!}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection

