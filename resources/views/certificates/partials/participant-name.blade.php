@php
    $certificateParticipantName = '';
    if (isset($participant) && $participant) {
        $certificateParticipantName = trim(implode(' ', array_filter([
            trim((string) ($participant->first_name ?? '')),
            trim((string) ($participant->last_name ?? '')),
        ])));
    }
@endphp
@if($certificateParticipantName !== '')
    <p class="mb-3">
        Zaświadczenie dla:
        <strong>{{ $certificateParticipantName }}</strong>
    </p>
@endif
