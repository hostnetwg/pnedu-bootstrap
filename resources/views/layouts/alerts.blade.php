{{-- resources/views/layouts/alerts.blade.php --}}

@php
    $certificateDownloaded = request()->query('certificate_downloaded') === '1';
@endphp

@if(
    $certificateDownloaded ||
    session('course_registration_message') ||
    session('success') ||
    session('info') ||
    session('error') ||
    $errors->any()
)
    <div class="container mt-3">
        @if($certificateDownloaded)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Zaświadczenie zostało zapisane na Twoim komputerze. Domyślnie znajdziesz je w folderze „Pobrane”, chyba że masz ustawioną inną lokalizację zapisu w przeglądarce.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('course_registration_message'))
            <div class="alert alert-{{ session('course_registration_success') ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                {{ session('course_registration_message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('info'))
            {{-- Bez klasy alert-dismissible: domyślne position:absolute na .btn-close psuje flex i tekst wchodzi pod X --}}
            <div class="alert alert-primary fade show d-flex align-items-start gap-3 gap-md-4 py-3 ps-3 ps-md-4 pe-3 mb-0 shadow border-0 border-start border-primary border-5 rounded-0 rounded-end" role="alert" style="font-size: 1.0625rem; line-height: 1.55;">
                <span class="flex-shrink-0 text-primary" aria-hidden="true">
                    <i class="bi bi-info-circle-fill" style="font-size: 1.85rem; line-height: 1;"></i>
                </span>
                <div class="flex-grow-1 min-w-0 fw-medium">
                    {{ session('info') }}
                </div>
                <button type="button" class="btn-close flex-shrink-0 ms-1 ms-md-2" style="margin-top: 0.15rem;" data-bs-dismiss="alert" aria-label="Zamknij"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>
@endif