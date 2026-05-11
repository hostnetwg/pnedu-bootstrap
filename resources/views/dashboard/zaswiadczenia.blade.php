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
            @include('certificates.partials.list-body', [
                'token' => null,
                'items' => $items,
                'isDashboardContext' => true,
                'highlightCourseId' => $highlightCourseId ?? null,
                'fromLink' => $fromLink ?? false,
            ])
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
<style>
.certificates-pagination .small.text-muted {
    display: none !important;
}
</style>
@endpush

@if(!empty($highlightCourseId))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('cert-row-{{ (int) $highlightCourseId }}');
    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
});
</script>
@endpush
@endif
