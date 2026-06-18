@if(!empty($checkoutResumeBanner))
    <div class="alert alert-info border-info mb-4" style="border-left-width: 4px;" role="status">
        <h5 class="alert-heading mb-2">
            <i class="bi bi-arrow-repeat me-2"></i>Masz niedawno złożone zamówienie na to szkolenie
        </h5>
        <p class="mb-2">
            Numer: <strong>{{ $checkoutResumeBanner['ident'] }}</strong>.
            Ponowne wysłanie formularza z tym samym e-mailem uczestnika
            @if(!empty($checkoutResumeBanner['participant_email']))
                (<strong>{{ $checkoutResumeBanner['participant_email'] }}</strong>)
            @endif
            <strong>zaktualizuje</strong> to zamówienie zamiast tworzyć duplikat.
        </p>
        <p class="mb-3 small text-muted">
            Chcesz zamówić szkolenie dla <strong>innego nauczyciela</strong>?
            Zmień dane uczestnika (imię, nazwisko, e-mail) albo użyj przycisku poniżej — wtedy powstanie osobne zamówienie, a dane adresowe możesz zostawić bez zmian.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-2 flex-wrap">
            <a href="{{ $checkoutResumeBanner['edit_url'] }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edytuj ostatnie zamówienie
            </a>
            <a href="{{ $checkoutResumeBanner['new_order_url'] }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person-plus me-1"></i>Zamówienie dla innego uczestnika
            </a>
        </div>
    </div>
@endif
