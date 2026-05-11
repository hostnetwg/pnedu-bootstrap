@extends('layouts.app')

@section('title', ($course->title ?? 'Kurs').' – Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <nav>@include('dashboard.partials.sidebar-nav')</nav>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h2 class="h4">{{ $course->title }}</h2>
                    <p class="text-muted">Ten kurs nie ma jeszcze opublikowanych lekcji. Wróć później lub skontaktuj się z pomocą techniczną.</p>
                    <a href="{{ route('dashboard.online-courses.index') }}" class="btn btn-outline-primary btn-sm">Lista kursów</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
@endpush
