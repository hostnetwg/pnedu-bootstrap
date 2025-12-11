@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>
                <ul class="list-unstyled dashboard-minimal-menu">
                    <li>
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active @endif">
                            <i class="bi bi-house-door"></i> Panel
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.szkolenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.szkolenia')) active @endif">
                            <i class="bi bi-journal-text"></i> Moje szkolenia
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.zaswiadczenia') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.zaswiadczenia')) active @endif">
                            <i class="bi bi-award"></i> Zaświadczenia
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.moje-dane') }}" class="d-flex align-items-center gap-2 @if(request()->routeIs('dashboard.moje-dane')) active @endif">
                            <i class="bi bi-person-circle"></i> Moje dane
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h2 class="h4 mb-4">Moje szkolenia</h2>
                    
                    @if($participants->isEmpty())
                        <p class="text-muted">Nie jesteś jeszcze zarejestrowany na żadne szkolenie.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nazwa szkolenia</th>
                                        <th>Data szkolenia</th>
                                        <th>Prowadzący</th>
                                        <th class="text-center">Zaświadczenia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($participants as $participant)
                                        @if($participant->course)
                                            <tr>
                                                <td>
                                                    <strong>{{ $participant->course->title }}</strong>
                                                </td>
                                                <td>
                                                    @if($participant->course->start_date)
                                                        {{ \Carbon\Carbon::parse($participant->course->start_date)->format('d.m.Y') }}
                                                    @else
                                                        <span class="text-muted">Brak daty</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($participant->course->instructor)
                                                        {{ $participant->course->instructor->full_name }}
                                                        @if($participant->course->instructor->title)
                                                            <small class="text-muted">({{ $participant->course->instructor->title }})</small>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Brak prowadzącego</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('certificates.generate.by-participant', $participant->id) }}" 
                                                       class="certificate-download-link" 
                                                       title="Pobierz zaświadczenie">
                                                        <img src="{{ asset('images/certificate.png') }}" 
                                                             alt="Zaświadczenie" 
                                                             class="certificate-icon">
                                                    </a>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
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
.certificate-download-link {
    display: inline-block;
    text-decoration: none;
    transition: all 0.3s ease;
}
.certificate-icon {
    width: 60px;
    height: auto;
    display: block;
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}
.certificate-download-link:hover .certificate-icon {
    transform: scale(1.15);
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}
</style>
@endpush