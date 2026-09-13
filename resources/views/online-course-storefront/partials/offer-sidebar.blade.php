@php
    $accessState = $viewerAccess?->state;
@endphp

@if($viewerAccess?->isUnlimited())
    <h2 class="h4">Twój dostęp</h2>
    <p class="mb-3">Masz dostęp bezterminowy do tego kursu.</p>
    <a href="{{ $viewerAccess->dashboardUrl() }}" class="btn btn-primary w-100">
        Przejdź do kursu
    </a>
@elseif($salesOpen === false)
    <h2 class="h4">Dostęp</h2>
    <p class="mb-3">Sprzedaż wyłączona</p>
    @if($viewerAccess?->canEnter())
        <p class="small text-muted mb-3">
            Masz aktywny dostęp
            @if($viewerAccess->endsLabel())
                do <strong>{{ $viewerAccess->endsLabel() }}</strong>
            @endif.
        </p>
        <a href="{{ $viewerAccess->dashboardUrl() }}" class="btn btn-primary w-100">
            Przejdź do kursu
        </a>
    @elseif($accessState === 'scheduled')
        <p class="mb-0">Dostęp od <strong>{{ $viewerAccess->startsLabel() }}</strong>.</p>
    @elseif($accessState === 'expired')
        <p class="mb-0">Twój dostęp skończył się <strong>{{ $viewerAccess->endsLabel() }}</strong>.</p>
    @endif
@else
    @if($accessState === 'active')
        <div class="alert alert-success">
            <p class="mb-2">Masz aktywny dostęp do <strong>{{ $viewerAccess->endsLabel() }}</strong>.</p>
            <a href="{{ $viewerAccess->dashboardUrl() }}" class="btn btn-primary w-100">
                Przejdź do kursu
            </a>
        </div>
        <h2 class="h4">Chcesz dłużej?</h2>
        <p class="text-muted small">Nowy okres dolicza się do obecnej daty końca, nie zaczyna się od dziś. Szkoła lub firma może też kupić dostęp dla kolejnych osób.</p>
    @elseif($accessState === 'scheduled')
        <div class="alert alert-info">
            Dostęp od <strong>{{ $viewerAccess->startsLabel() }}</strong>. Lekcje otworzą się w tym dniu.
        </div>
        <h2 class="h4">Wybierz dostęp</h2>
        <p class="text-muted small">Cena za jednego uczestnika. Szkoła lub firma może kupić dostęp dla wielu osób.</p>
    @elseif($accessState === 'expired')
        <div class="alert alert-warning">
            Twój dostęp skończył się <strong>{{ $viewerAccess->endsLabel() }}</strong>.
        </div>
        <h2 class="h4">Wybierz dostęp</h2>
        <p class="text-muted small">Cena za jednego uczestnika. Szkoła lub firma może kupić dostęp dla wielu osób.</p>
    @else
        <h2 class="h4">Wybierz dostęp</h2>
        <p class="text-muted small">Cena za jednego uczestnika. Szkoła lub firma może kupić dostęp dla wielu osób.</p>
    @endif

    <div class="d-grid gap-3">
        @foreach($prices as $price)
            @include('online-course-storefront.partials.price-variant-card', [
                'product' => $product,
                'price' => $price,
                'buttonLabel' => $viewerAccess?->buyButtonLabel() ?? 'Zamawiam ten wariant',
            ])
        @endforeach
    </div>

    @if($offer?->hasSatisfactionGuarantee())
        <hr>
        @include('online-course-storefront.partials.satisfaction-guarantee', ['offer' => $offer])
    @endif
@endif
