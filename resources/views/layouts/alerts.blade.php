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
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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