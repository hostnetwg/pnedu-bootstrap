@if(!empty($registrationRedirectNotice))
    @php
        $registrationRedirectNoticeContext = $registrationRedirectNoticeContext ?? 'form';
    @endphp
    @once
        @push('styles')
            <style>
                #registration-redirect-notice-heading {
                    scroll-margin-top: 6.5rem;
                }
            </style>
        @endpush
    @endonce
    <div class="alert alert-warning border-warning shadow-sm my-4" role="alert" id="registration-redirect-notice" tabindex="-1">
        <h2 class="h5 alert-heading mb-2" id="registration-redirect-notice-heading">Uwaga: zmiana terminu szkolenia</h2>
        <p class="mb-2">
            Na szkolenie w terminie
            <strong class="text-danger">{{ $registrationRedirectNotice['closed_label'] }}</strong>
            nie mamy już wolnych miejsc.
        </p>
        <p class="mb-2">
            {{ $registrationRedirectNoticeContext === 'course' ? 'Jesteś na stronie kolejnej edycji w terminie' : 'Ten formularz dotyczy kolejnej edycji w terminie' }}
            <strong class="text-danger">{{ $registrationRedirectNotice['target_label'] }}</strong>.
        </p>
        @if(!empty($registrationRedirectNotice['custom_message']))
            <p class="mb-0">{{ $registrationRedirectNotice['custom_message'] }}</p>
        @endif
    </div>
    <script>
        window.pneduScrollToRegistrationRedirectNotice = function () {
            var heading = document.getElementById('registration-redirect-notice-heading');
            if (!heading) {
                return false;
            }
            heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return true;
        };
    </script>
    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (document.getElementById('order-form-v2')) {
                        return;
                    }
                    requestAnimationFrame(function () {
                        if (typeof window.pneduScrollToRegistrationRedirectNotice === 'function') {
                            window.pneduScrollToRegistrationRedirectNotice();
                        }
                    });
                });
            </script>
        @endpush
    @endonce
@endif
