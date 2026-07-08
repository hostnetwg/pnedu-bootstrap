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
                    <h1 class="h4 mb-2">Dane na zaświadczeniu</h1>
                    <p class="text-muted small mb-4">
                        Zaświadczenie jest wystawiane na dane z Twojego konta pnedu.pl ({{ $user->email }}).
                        Uzupełnij brakujące pola — zapiszą się w profilu i będą użyte na zaświadczeniu.
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="post" action="{{ route('dashboard.online-courses.certificate.profile', $enrollment) }}">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name">
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name">
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        @if($collectBirthData)
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="birth_date" class="form-label">
                                        Data urodzenia @if($birthDataRequired)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="date" name="birth_date" id="birth_date" class="form-control @error('birth_date') is-invalid @enderror"
                                           value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}"
                                           @if($birthDataRequired) required @endif autocomplete="bday">
                                    @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="birth_place" class="form-label">
                                        Miejsce urodzenia @if($birthDataRequired)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text" name="birth_place" id="birth_place" class="form-control @error('birth_place') is-invalid @enderror"
                                           value="{{ old('birth_place', $user->birth_place) }}"
                                           @if($birthDataRequired) required @endif autocomplete="off">
                                    @error('birth_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Zapisz i kontynuuj</button>
                            <a href="{{ route('dashboard.online-courses.show', $enrollment) }}" class="btn btn-outline-secondary">Wróć do kursu</a>
                            <a href="{{ route('profile.edit') }}" class="btn btn-link">Edytuj pełny profil</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
@endpush
