@php
    $instructor = $course?->instructor;
    $authorName = $instructor
        ? trim($instructor->first_name.' '.$instructor->last_name)
        : null;
    $paidCount = $prices->count();
@endphp
<section class="order-v2__offer-summary p-2 p-md-3 mb-3" aria-labelledby="checkout-offer-title">
    <p class="order-v2__offer-eyebrow text-uppercase small fw-semibold text-success mb-1">
        {{ ($isEditMode ?? false) ? 'Edytuj zamówienie kursu online' : 'Zamawiasz kurs online' }}
    </p>
    <h1 class="order-v2__offer-title mb-2 text-center" id="checkout-offer-title">
        <a href="{{ route('online-courses.catalog.show', $product->slug) }}"
           class="order-v2__offer-title-link"
           title="Przejdź do opisu kursu">{{ $product->name }}</a>
    </h1>
    <div class="row g-2 align-items-start">
        <div class="col-lg-8">
            <div class="order-v2__offer-meta-text">
                @if($authorName)
                    <p><strong>Autor:</strong> {{ $authorName }}</p>
                @endif
                <p><strong>Dostęp:</strong> <span id="checkoutOfferAccessLabel">{{ $selectedPrice->accessLabel() }}</span></p>
                @if(filled($selectedPrice->access_note))
                    <p>{{ $selectedPrice->access_note }}</p>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="order-v2__offer-price-box text-lg-end">
                @if($selectedPrice->isPromotionActive())
                    <div class="text-muted text-decoration-line-through small">{{ number_format((float) $selectedPrice->price, 2, ',', ' ') }} PLN</div>
                    <div class="order-v2__offer-price text-danger" id="checkoutOfferPrice">{{ number_format((float) $selectedPrice->currentPrice(), 2, ',', ' ') }} zł</div>
                @else
                    <div class="order-v2__offer-price" id="checkoutOfferPrice">{{ number_format((float) $selectedPrice->currentPrice(), 2, ',', ' ') }} zł</div>
                @endif
                @if($selectedPrice->isPromotionActive())
                    <div class="mt-2">
                        @include('online-course-storefront.partials.promotion-notice', ['price' => $selectedPrice])
                    </div>
                @endif
                @if($paidCount > 1)
                    <p class="small text-muted mb-0 mt-1">Najniższa cena zależy od wybranego wariantu.</p>
                @endif
            </div>
        </div>
    </div>
</section>
