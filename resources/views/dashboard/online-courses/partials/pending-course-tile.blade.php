@php
    $imgUrl = $pending->course->publicImageUrl();
    $modalId = 'resignPendingOrderModal-'.$pending->order->id;
@endphp
<div class="col d-flex">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 w-100 d-flex flex-column pending-course-tile">
        <div class="online-course-tile-media bg-body-secondary border-bottom position-relative">
            @if($imgUrl)
                <img src="{{ $imgUrl }}"
                     class="online-course-tile-img pending-course-tile-img"
                     alt="Okładka kursu: {{ $pending->course->title }}"
                     loading="lazy"
                     decoding="async">
            @else
                <div class="online-course-tile-placeholder text-muted small">
                    <i class="bi bi-collection-play d-block fs-2 mb-2 opacity-50" aria-hidden="true"></i>
                    Brak okładki
                </div>
            @endif
            <div class="pending-course-tile-lock" aria-hidden="true">
                <i class="bi bi-lock-fill"></i>
            </div>
        </div>
        <div class="card-body d-flex flex-column flex-grow-1 pt-3">
            <h3 class="h6 mb-2">{{ $pending->course->title }}</h3>
            @if($pending->course->instructor)
                <p class="small text-muted mb-2">{{ $pending->course->instructor->full_name_with_title }}</p>
            @endif
            <p class="small text-muted mb-2">Zamówienie nr {{ $pending->order->id }}</p>

            @if($pending->isAwaitingPayment())
                <div class="alert alert-warning small mb-3">
                    Zamówienie oczekuje na płatność. Dokończ płatność albo zrezygnuj — kurs odblokuje się po zaksięgowaniu wpłaty.
                </div>
            @else
                <div class="alert alert-info small mb-3">
                    Zamówienie zostało przyjęte. Dostęp nadamy po weryfikacji przez zespół pnedu.pl. Kurs pojawi się tutaj automatycznie.
                </div>
            @endif

            <div class="mt-auto d-grid gap-2">
                @if($pending->isAwaitingPayment() && $pending->paymentUrl)
                    <a href="{{ $pending->paymentUrl }}" class="btn btn-primary">
                        Dokończ płatność
                    </a>
                @endif
                <a href="{{ route('online-courses.checkout.summary', $pending->order->ident) }}" class="btn btn-outline-secondary">
                    Szczegóły zamówienia
                </a>
                @if($pending->canResign)
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#{{ $modalId }}">
                        Anuluję / rezygnuję z zamówienia
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@if($pending->canResign)
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="post" action="{{ route('dashboard.online-courses.pending.resign', $pending->order->ident) }}">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h3 class="modal-title h5" id="{{ $modalId }}-title">Rezygnacja z zamówienia</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Czy na pewno chcesz anulować zamówienie nr {{ $pending->order->id }} ({{ $pending->course->title }})?</p>
                        <div class="alert alert-warning small mb-0">
                            Po rezygnacji karta zniknie z listy. Jeśli zmienisz zdanie, złożysz nowe zamówienie.
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Wróć</button>
                        <button type="submit" class="btn btn-danger">Tak, rezygnuję</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
