@extends('layouts.app')

@section('title', 'Twoje zaświadczenia – ' . config('app.name'))

@push('styles')
<style>
    .certificates-pagination .small.text-muted {
        display: none !important;
    }
</style>
@endpush

@section('content')
    @include('certificates.partials.list-body', [
        'token' => $token,
        'items' => $items,
        'isDashboardContext' => false,
        'highlightCourseId' => null,
        'fromLink' => false,
    ])
@endsection
