@if($upcomingTikCourse ?? null)
<!-- Modal rejestracji na szkolenie TIK -->
<div class="modal fade" id="tikCourseRegistrationModal" tabindex="-1" aria-labelledby="tikCourseRegistrationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header with Image -->
            <div class="position-relative bg-light" style="overflow: hidden; padding: 20px 0;">
                @if(!empty($upcomingTikCourse->image))
                    <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($upcomingTikCourse->image, '/') }}" 
                         alt="{{ strip_tags($upcomingTikCourse->title) }}" 
                         class="w-100" 
                         style="width: 100%; height: auto; object-fit: contain; display: block;">
                @else
                    <div class="w-100 d-flex align-items-center justify-content-center" style="min-height: 150px;">
                        <div class="text-center">
                            <div class="fw-bold text-primary mb-1" style="font-size: 1.2rem; letter-spacing: 2px;">TIK</div>
                            <div class="text-muted small">Technologie Informacyjno-Komunikacyjne</div>
                            <div class="text-primary fw-bold mt-1">w pracy NAUCZYCIELA</div>
                        </div>
                    </div>
                @endif
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" style="z-index: 10;" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Course Title and Details -->
                <div class="text-center mb-3">
                    <div class="badge bg-success text-white mb-2 px-3 py-2" style="font-size: 0.85rem; font-weight: 600;">
                        Darmowy webinar on-line
                    </div>
                    <h6 class="fw-bold mb-2" id="tikCourseRegistrationModalLabel" style="font-size: 1rem; color: #333;">
                        {{ $upcomingTikCourse->title }}
                    </h6>
                    
                    @php
                        $startDate = \Carbon\Carbon::parse($upcomingTikCourse->start_date)->locale('pl');
                    @endphp
                    <div class="text-muted small mb-2">
                        <div><strong>Data:</strong> {{ $startDate->format('d.m.Y') }} o godz. {{ $startDate->format('H:i') }}</div>
                        @if($upcomingTikCourse->trainer)
                            <div class="mt-2 d-flex align-items-center justify-content-center gap-2">
                                @if($upcomingTikCourse->instructor && !empty($upcomingTikCourse->instructor->photo))
                                    <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($upcomingTikCourse->instructor->photo, '/') }}" 
                                         alt="{{ $upcomingTikCourse->instructor->full_name }}" 
                                         class="rounded-circle"
                                         style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #dee2e6;">
                                @endif
                                <span><strong>{{ $upcomingTikCourse->trainer_title }}:</strong> {{ $upcomingTikCourse->trainer }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <hr class="my-3">

                <!-- Registration Form -->
                <div class="text-center">
                    <h6 class="fw-bold mb-2" style="font-size: 1rem;">Dołącz do listy mailowej</h6>
                    <p class="text-muted mb-2" style="font-size: 0.9rem;">i odbierz dostęp do szkolenia</p>
                    <p class="text-muted mb-4" style="font-size: 0.85rem; font-style: italic; line-height: 1.5;">
                        Raz na jakiś czas wyślemy Ci też e-maila z ciekawymi materiałami.<br>
                        Możesz się wypisać w dowolnym momencie.
                    </p>
                    
                    <form id="tikCourseRegistrationForm">
                        <div class="mb-3">
                            <input type="email" 
                                   class="form-control text-center tik-email-input" 
                                   id="registrationEmail" 
                                   name="email" 
                                   placeholder="Twój adres e-mail" 
                                   required>
                        </div>
                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-primary fw-bold" style="padding: 12px; font-size: 1rem;">
                                Zapisz Mnie!
                            </button>
                        </div>
                        <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal" style="font-size: 0.85rem; padding: 0;">
                            Może później
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show modal after page load (with a small delay for better UX)
    // TODO: Przywrócić localStorage dla produkcji - na razie wyłączone dla testów
    const modalElement = document.getElementById('tikCourseRegistrationModal');
    if (modalElement) {
        // Zawsze pokazuj modal przy każdym odświeżeniu (dla łatwiejszego testowania)
        setTimeout(function() {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }, 1000);

        // Handle form submission (for now, just prevent default and show message)
        const form = document.getElementById('tikCourseRegistrationForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // TODO: Implement actual registration logic
                // For now, just show a message
                const email = document.getElementById('registrationEmail').value;
                alert('Formularz zapisu jest w przygotowaniu. Twój adres e-mail: ' + email + '\n\nFunkcjonalność zostanie wkrótce uruchomiona.');
                
                // Close modal after showing message
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            });
        }
    }
});
</script>
@endpush

@push('styles')
<style>
    #tikCourseRegistrationModal .modal-content {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #tikCourseRegistrationModal .modal-body {
        padding: 1.5rem;
    }

    #tikCourseRegistrationModal .tik-email-input {
        padding: 16px;
        font-size: 1.1rem;
        border: 2px solid #dee2e6;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

    #tikCourseRegistrationModal .tik-email-input:focus {
        border-color: #0d6efd;
        background-color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        outline: none;
    }

    #tikCourseRegistrationModal .tik-email-input::placeholder {
        color: #6c757d;
        font-weight: 500;
    }

    #tikCourseRegistrationModal .btn-primary {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        border: none;
        transition: all 0.3s ease;
    }

    #tikCourseRegistrationModal .btn-primary:hover {
        background: linear-gradient(135deg, #0b5ed7 0%, #0a58ca 100%);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }

    #tikCourseRegistrationModal .btn-close {
        z-index: 10;
        opacity: 0.9;
    }

    #tikCourseRegistrationModal .btn-close:hover {
        opacity: 1;
    }
</style>
@endpush
@endif
