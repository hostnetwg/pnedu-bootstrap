@php
    $days = $offer->satisfactionGuaranteeDays();
    $contactEmail = config('seo.organization.email', 'kontakt@pnedu.pl');
    $contactPhone = '501 654 274';
@endphp
@if($days > 0)
    <div class="{{ $wrapperClass ?? 'alert alert-light border small mb-0' }}">
        {{ $days }} dni gwarancji satysfakcji od rozpoczęcia dostępu do kursu.
        Zwrot zgłosisz
        <a href="mailto:{{ $contactEmail }}">e-mailem</a>
        lub
        <a href="tel:+48501654274">telefonicznie</a>
        ({{ $contactPhone }}).
    </div>
@endif
