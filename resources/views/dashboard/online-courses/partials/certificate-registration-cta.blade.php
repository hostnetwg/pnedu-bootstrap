@if(!empty($certificateContext['show']))
    @php
        $ctx = $certificateContext;
        $openModal = empty($ctx['already_registered'])
            && (session('open_certificate_modal') || $errors->has('confirmation_consent'));
        $showRegistrationModal = empty($ctx['already_registered']) && ! empty($ctx['needs_registration']);
    @endphp
    <section class="online-lesson-certificate mt-4 pt-4 border-top" aria-labelledby="lesson-certificate-heading">
        <h2 id="lesson-certificate-heading" class="visually-hidden">Zaświadczenie</h2>

        @if(session('error') && $openModal)
            <div class="alert alert-danger small py-2">{{ session('error') }}</div>
        @endif

        @if(!empty($ctx['show_button']))
            @if(!empty($ctx['already_registered']) && !empty($ctx['can_download']))
                <a href="{{ $ctx['dashboard_certificate_url'] }}"
                   class="online-lesson-certificate-trigger online-lesson-certificate-trigger--link w-100 d-block text-decoration-none">
                    <div class="online-lesson-certificate-badge rounded-4 shadow-sm">
                        <span class="online-lesson-certificate-badge-icon" aria-hidden="true">
                            <i class="bi bi-patch-check-fill"></i>
                        </span>
                        <span class="online-lesson-certificate-badge-label">Zaświadczenie</span>
                        <span class="online-lesson-certificate-badge-hint small">Pobierz PDF</span>
                    </div>
                </a>
            @elseif(!empty($ctx['already_registered']))
                <div class="alert alert-warning mb-0 small">
                    {{ $ctx['message'] ?? 'Zaświadczenie jest w przygotowaniu — pobieranie nie jest jeszcze udostępnione.' }}
                </div>
            @else
                <button type="button"
                        class="online-lesson-certificate-trigger w-100 border-0 bg-transparent p-0 text-start"
                        data-bs-toggle="modal"
                        data-bs-target="#online-lesson-certificate-modal"
                        aria-haspopup="dialog">
                    <div class="online-lesson-certificate-badge rounded-4 shadow-sm">
                        <span class="online-lesson-certificate-badge-icon" aria-hidden="true">
                            <i class="bi bi-patch-check-fill"></i>
                        </span>
                        <span class="online-lesson-certificate-badge-label">Zaświadczenie</span>
                        <span class="online-lesson-certificate-badge-hint small">Po obejrzeniu szkolenia</span>
                    </div>
                </button>
            @endif
        @elseif(!empty($ctx['message']))
            <div class="alert alert-secondary mb-0 small">{{ $ctx['message'] }}</div>
        @endif

        @if($showRegistrationModal)
        <div class="modal fade" id="online-lesson-certificate-modal" tabindex="-1" aria-labelledby="online-lesson-certificate-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h3 class="modal-title h5 fw-semibold" id="online-lesson-certificate-modal-title">
                            <i class="bi bi-patch-check text-primary me-1" aria-hidden="true"></i> Zaświadczenie
                        </h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <p class="fw-semibold text-center mb-3">{{ $ctx['course_title'] ?? 'Szkolenie' }}</p>

                        <form method="post"
                              action="{{ route('dashboard.online-courses.lesson-certificate.submit', [$enrollment, $lesson]) }}"
                              id="online-lesson-certificate-form">
                            @csrf
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('confirmation_consent') is-invalid @enderror"
                                           type="checkbox"
                                           name="confirmation_consent"
                                           id="online-lesson-certificate-confirm"
                                           value="1"
                                           {{ old('confirmation_consent') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="online-lesson-certificate-confirm">
                                        Potwierdzam obejrzenie szkolenia oraz wyrażam zgodę na przetwarzanie moich danych osobowych
                                        (imię, nazwisko, e-mail z zapisu na kurs online) w celu wydania zaświadczenia zgodnie z przepisami MEN
                                        oraz <a href="{{ route('rodo') }}" target="_blank" rel="noopener">klauzulą RODO</a>
                                        i <a href="{{ route('polityka-prywatnosci') }}" target="_blank" rel="noopener">Polityką prywatności</a>.
                                        <span class="text-danger">*</span>
                                    </label>
                                    @error('confirmation_consent')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit"
                                        class="btn btn-primary btn-lg"
                                        id="online-lesson-certificate-submit"
                                        disabled>
                                    Pobierz zaświadczenie
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </section>

    @push('styles')
    <style>
        .online-lesson-certificate-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 1.25rem 1rem;
            background: linear-gradient(145deg, #f8f9fb 0%, #eef2ff 100%);
            border: 2px solid rgba(13, 110, 253, 0.25);
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .online-lesson-certificate-trigger:hover .online-lesson-certificate-badge,
        .online-lesson-certificate-trigger:focus-visible .online-lesson-certificate-badge {
            transform: translateY(-2px);
            border-color: rgba(13, 110, 253, 0.55);
            box-shadow: 0 0.5rem 1.25rem rgba(13, 110, 253, 0.15) !important;
        }
        .online-lesson-certificate-trigger:focus-visible {
            outline: none;
        }
        .online-lesson-certificate-trigger--link {
            color: inherit;
        }
        .online-lesson-certificate-badge-icon {
            font-size: 2.25rem;
            line-height: 1;
            color: var(--bs-primary);
        }
        .online-lesson-certificate-badge-label {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #1a1a2e;
        }
        .online-lesson-certificate-badge-hint {
            color: var(--bs-secondary-color);
        }
    </style>
    @endpush

    @if($showRegistrationModal)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('online-lesson-certificate-modal');
        var confirmCb = document.getElementById('online-lesson-certificate-confirm');
        var submitBtn = document.getElementById('online-lesson-certificate-submit');

        function syncSubmitState() {
            if (!submitBtn || !confirmCb) return;
            submitBtn.disabled = !confirmCb.checked;
        }

        if (confirmCb) {
            confirmCb.addEventListener('change', syncSubmitState);
            syncSubmitState();
        }

        @if($openModal)
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
        @endif
    });
    </script>
    @endpush
    @endif
@endif
