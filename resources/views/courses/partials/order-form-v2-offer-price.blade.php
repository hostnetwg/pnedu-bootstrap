@if($price)
    @if($offerSummary['variant_label'])
        <p class="order-v2__offer-variant small text-muted mb-1">{{ $offerSummary['variant_label'] }}</p>
    @endif
    @if($showPromotionalPrice)
        <p class="order-v2__offer-price-label small text-muted mb-0">Cena promocyjna</p>
        <p class="order-v2__offer-price mb-0 text-danger">{{ number_format($price['price'], 2, ',', ' ') }} PLN <span class="small fw-semibold">brutto</span></p>
        <p class="order-v2__offer-compare small text-muted mb-1">zamiast {{ number_format($price['original_price'], 2, ',', ' ') }} PLN</p>
        <p class="order-v2__offer-omnibus small mb-2" style="font-size: 0.75rem; color: #aaa;">
            Najniższa cena z 30 dni przed obniżką:
            <strong style="color: #aaa;">{{ number_format($price['omnibus_lowest_price'] ?? $price['original_price'], 2, ',', ' ') }} PLN</strong>
        </p>
    @else
        <p class="order-v2__offer-price mb-2 text-success">{{ number_format($price['price'], 2, ',', ' ') }} PLN <span class="small fw-semibold">brutto</span></p>
    @endif
@endif
<a href="{{ $offerSummary['course_url'] }}" class="order-v2__offer-course-link small link-secondary" data-analytics-cta="view_full_course_description">
    Zobacz pełny opis szkolenia
</a>
