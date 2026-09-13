@if($price->isPromotionActive())
    @if($price->promotionEndLabel())
        <small class="d-block {{ $endClass ?? '' }}" style="font-size: 0.85rem; color: #000;">
            Promocja trwa do: {{ $price->promotionEndLabel() }}
        </small>
    @endif
    <small class="d-block {{ $omnibusClass ?? '' }}" style="font-size: 0.75rem; color: #aaa;">
        Najniższa cena z 30 dni przed obniżką:
        <strong style="color: #aaa;">{{ number_format((float) ($price->omnibusLowestPrice() ?? $price->price), 2, ',', ' ') }} PLN</strong>
    </small>
    @if($price->shouldShowPromotionCountdown())
        <div class="small fw-semibold text-danger mt-1"
             data-promotion-countdown
             data-countdown-target="{{ $price->promotionCountdownTargetIso() }}">
            Do końca promocji:
            <strong class="js-promotion-countdown-value" aria-live="polite">—</strong>
        </div>
    @endif
@endif
