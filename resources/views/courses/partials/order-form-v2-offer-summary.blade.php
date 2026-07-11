@php
    use App\Support\OrderFormV2OfferSummary;

    $offerSummary = OrderFormV2OfferSummary::fromCourse($course, $priceInfo ?? null);
    $price = $offerSummary['price_info'] ?? null;
    $showPromotionalPrice = $price
        && isset($price['original_price'], $price['price'])
        && (float) $price['original_price'] > (float) $price['price'];
@endphp

<section class="order-v2__offer-summary p-2 p-md-3 mb-3"
    aria-labelledby="v2-offer-summary-title"
    data-analytics-section-v2="offer_summary">
    <p class="order-v2__offer-eyebrow text-uppercase small fw-semibold text-success mb-1">Zamawiasz szkolenie</p>
    <h1 class="order-v2__offer-title mb-2 text-center" id="v2-offer-summary-title">
        <a href="{{ $offerSummary['course_url'] }}"
            class="order-v2__offer-title-link"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Przejdź do opisu szkolenia"
            data-analytics-cta="view_course_from_offer_title">{{ $offerSummary['title'] }}</a>
    </h1>

    <div class="row g-2 align-items-start">
        <div class="col-lg-8">
            <div class="order-v2__offer-meta-row">
                @if($offerSummary['trainer_photo_url'])
                    <div class="order-v2__offer-photo flex-shrink-0">
                        <img src="{{ $offerSummary['trainer_photo_url'] }}"
                            alt="{{ $offerSummary['trainer_name'] ?? 'Prowadzący szkolenie' }}"
                            class="order-v2__offer-trainer-photo"
                            loading="lazy"
                            decoding="async">
                    </div>
                @endif
                <div class="order-v2__offer-meta-content flex-grow-1 min-w-0">
                    <div class="order-v2__offer-meta-text">
                        @if($offerSummary['date_line'] || $offerSummary['duration'])
                            <p>
                                @if($offerSummary['date_line'])
                                    <strong>Data:</strong> {{ $offerSummary['date_line'] }}
                                @endif
                                @if($offerSummary['date_line'] && $offerSummary['duration'])
                                    <span aria-hidden="true"> | </span>
                                @endif
                                @if($offerSummary['duration'])
                                    <strong>Czas trwania:</strong> {{ $offerSummary['duration'] }}
                                @endif
                            </p>
                        @endif
                        @if($offerSummary['trainer_name'])
                            <p>
                                <strong>{{ $offerSummary['trainer_label'] ?? 'Prowadzący' }}:</strong> {{ $offerSummary['trainer_name'] }}
                            </p>
                        @endif
                        @if($offerSummary['format_label'] || $offerSummary['platform_label'])
                            <p>
                                @if($offerSummary['format_label'])
                                    <strong>Forma:</strong> {{ $offerSummary['format_label'] }}
                                @endif
                                @if($offerSummary['format_label'] && $offerSummary['platform_label'])
                                    <span aria-hidden="true"> | </span>
                                @endif
                                @if($offerSummary['platform_label'])
                                    <strong>Platforma:</strong> {{ $offerSummary['platform_label'] }}
                                @endif
                            </p>
                        @endif
                        @if($offerSummary['additional_line'])
                            <p><strong>Dodatkowo:</strong> {{ $offerSummary['additional_line'] }}</p>
                        @endif
                        @if($offerSummary['recording_line'])
                            <p><strong>Dostęp do nagrania:</strong> {{ $offerSummary['recording_line'] }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="order-v2__offer-price-box order-v2__offer-price-box--mobile d-lg-none">
                @include('courses.partials.order-form-v2-offer-price', [
                    'offerSummary' => $offerSummary,
                    'price' => $price,
                    'showPromotionalPrice' => $showPromotionalPrice,
                ])
            </div>
        </div>

        <div class="col-lg-4 d-none d-lg-block">
            <div class="order-v2__offer-price-box text-lg-end">
                @include('courses.partials.order-form-v2-offer-price', [
                    'offerSummary' => $offerSummary,
                    'price' => $price,
                    'showPromotionalPrice' => $showPromotionalPrice,
                ])
            </div>
        </div>
    </div>
</section>
