@if($price)
    @if($offerSummary['variant_label'])
        <p class="order-v2__offer-variant small text-muted mb-1">{{ $offerSummary['variant_label'] }}</p>
    @endif
    @if($showPromotionalPrice)
        <p class="order-v2__offer-price-label small text-muted mb-0">Cena promocyjna</p>
        <p class="order-v2__offer-price mb-0 text-danger">{{ number_format($price['price'], 2, ',', ' ') }} PLN <span class="small fw-semibold">brutto</span></p>
        <p class="order-v2__offer-compare small text-muted mb-2">zamiast {{ number_format($price['original_price'], 2, ',', ' ') }} PLN</p>
    @else
        <p class="order-v2__offer-price mb-2 text-success">{{ number_format($price['price'], 2, ',', ' ') }} PLN <span class="small fw-semibold">brutto</span></p>
    @endif
@endif
<a href="{{ $offerSummary['course_url'] }}" class="order-v2__offer-course-link small link-secondary" data-analytics-cta="view_full_course_description">
    Zobacz pełny opis szkolenia
</a>
