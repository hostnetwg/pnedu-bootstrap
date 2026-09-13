@php
    $buttonLabel = $buttonLabel ?? 'Zamawiam ten wariant';
@endphp
<div class="border rounded p-3">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <h3 class="h6 mb-1">{{ $price->name }}</h3>
            <div class="small text-muted">{{ $price->accessLabel() }}</div>
        </div>
        <div class="text-end text-nowrap">
            @if($price->isPromotionActive())
                <small class="text-decoration-line-through text-muted d-block">{{ number_format((float) $price->price, 2, ',', ' ') }} PLN</small>
                <strong class="fs-5 text-danger">{{ number_format((float) $price->currentPrice(), 2, ',', ' ') }} PLN</strong>
            @else
                <strong class="fs-5">{{ number_format((float) $price->currentPrice(), 2, ',', ' ') }} zł</strong>
            @endif
        </div>
    </div>
    @if($price->isPromotionActive())
        <div class="mt-2">
            @include('online-course-storefront.partials.promotion-notice', ['price' => $price])
        </div>
    @endif
    @if(filled($price->description))
        <p class="small mt-2 mb-0">{{ $price->description }}</p>
    @endif
    @if(filled($price->access_note))
        <p class="small text-muted mt-2 mb-0">{{ $price->access_note }}</p>
    @endif
    <a href="{{ route('online-courses.checkout.create', ['product' => $product->slug, 'price' => $price->id]) }}"
       class="btn btn-primary w-100 mt-3">
        {{ $buttonLabel }}
    </a>
</div>
