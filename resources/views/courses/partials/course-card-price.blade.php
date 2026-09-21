{{-- Cena na karcie listy szkoleń (nadchodzące i zakończone) + Omnibus przy aktywnej promocji --}}
@php
    $priceInfo = $course->getCurrentPrice();
    if (! $priceInfo && $course->relationLoaded('priceVariants') && $course->priceVariants && $course->priceVariants->count() > 0) {
        $firstVariant = $course->priceVariants
            ->where('is_active', true)
            ->first(fn ($variant) => $variant->isAvailableForCourseEndState($course->hasEnded()));
        if ($firstVariant) {
            $isPromotionActive = $firstVariant->isPromotionActive();
            $currentPrice = $firstVariant->getCurrentPrice();
            $priceInfo = [
                'price' => round((float) $currentPrice, 2),
                'original_price' => $isPromotionActive ? round((float) $firstVariant->price, 2) : null,
                'omnibus_lowest_price' => $isPromotionActive
                    ? (float) app(\App\Services\PriceOmnibusService::class)->lowestFor($firstVariant)
                    : null,
                'is_promotion' => $isPromotionActive,
                'promotion_end' => $isPromotionActive && $firstVariant->promotion_type === 'time_limited' ? $firstVariant->promotion_end : null,
                'promotion_type' => $firstVariant->promotion_type,
            ];
        }
    }
@endphp
@if($priceInfo)
    <div class="text-center mb-3">
        @if($priceInfo['is_promotion'] && $priceInfo['original_price'])
            <div class="d-flex flex-column align-items-center gap-1">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <span class="text-muted text-decoration-line-through" style="font-size: 0.9rem;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</span>
                    <span class="fw-bold text-danger" style="font-size: 1.2rem;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span class="text-danger" style="font-size: 1.2rem;">(brutto)</span>
                </div>
                @if($priceInfo['promotion_end'] && $priceInfo['promotion_type'] === 'time_limited')
                    <small style="font-size: 0.85rem; color: #000;">
                        Promocja trwa do: {{ \Carbon\Carbon::parse($priceInfo['promotion_end'])->format('d.m.Y H:i') }}
                    </small>
                @endif
                <small style="font-size: 0.75rem; color: #aaa;">
                    Najniższa cena z 30 dni przed obniżką: <strong style="color: #aaa;">{{ number_format($priceInfo['omnibus_lowest_price'] ?? $priceInfo['original_price'], 2, ',', ' ') }} PLN</strong>
                </small>
            </div>
        @else
            <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span style="font-size: 1.2rem; color: #1976d2;">(brutto)</span>
        @endif
    </div>
@endif
