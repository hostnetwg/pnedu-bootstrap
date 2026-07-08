@extends('layouts.app')

@section('title', 'Zaświadczenie – '.$course->title)

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>@include('dashboard.partials.sidebar-nav')</nav>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Zaświadczenie — {{ $course->title }}</h1>
                    <p class="text-muted small mb-4">Sprawdź dane przed pobraniem. Jeśli coś jest nie tak, popraw je w <a href="{{ route('profile.edit') }}">profilu konta</a>.</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <dl class="row mb-4">
                        <dt class="col-sm-4">Imię i nazwisko</dt>
                        <dd class="col-sm-8">{{ trim($user->first_name.' '.$user->last_name) }}</dd>
                        <dt class="col-sm-4">E-mail</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>
                        @if($course->certificate_collect_birth_data && $user->birth_date)
                            <dt class="col-sm-4">Data urodzenia</dt>
                            <dd class="col-sm-8">{{ $user->birth_date->format('d.m.Y') }}</dd>
                        @endif
                        @if($course->certificate_collect_birth_data && $user->birth_place)
                            <dt class="col-sm-4">Miejsce urodzenia</dt>
                            <dd class="col-sm-8">{{ $user->birth_place }}</dd>
                        @endif
                    </dl>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $context['download_url'] }}" class="btn btn-primary">
                            <i class="bi bi-download me-1" aria-hidden="true"></i> Pobierz zaświadczenie (PDF)
                        </a>
                        <a href="{{ route('dashboard.online-courses.show', $enrollment) }}" class="btn btn-outline-secondary">Wróć do kursu</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
@endpush
