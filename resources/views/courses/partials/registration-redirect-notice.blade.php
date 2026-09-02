@if(!empty($registrationRedirectNotice))
    @php
        $registrationRedirectNoticeContext = $registrationRedirectNoticeContext ?? 'form';
    @endphp
    <div class="alert alert-warning border-warning shadow-sm my-4" role="alert">
        <h2 class="h5 alert-heading mb-2">Uwaga: zmiana terminu szkolenia</h2>
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
@endif
