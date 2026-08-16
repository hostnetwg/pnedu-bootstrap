{{--
  Wielu uczestników na formularzu zamówienia (legacy + V2).
  Osoba prywatna: max 1. Szkoła/firma: do max_participants, przycisk dodaj/usuń, live-przeliczenie ceny.
--}}
@php
    $maxParticipants = (int) config('order_form.max_participants', 50);
    $unitPrice = null;
    if (isset($priceInfo) && is_array($priceInfo) && isset($priceInfo['price'])) {
        $unitPrice = (float) $priceInfo['price'];
    } elseif (isset($course) && method_exists($course, 'getPriceInfoForOrderFormHeader')) {
        $headerVariantId = $prefillPriceVariantId ?? null;
        $pi = $course->getPriceInfoForOrderFormHeader(filled($headerVariantId) ? (int) $headerVariantId : null);
        $unitPrice = $pi['price'] ?? null;
    }
    $prefillRows = old('participants');
    if (! is_array($prefillRows) || $prefillRows === []) {
        $prefillRows = $testData['participants'] ?? null;
    }
    if (! is_array($prefillRows) || $prefillRows === []) {
        $prefillRows = [[
            'first_name' => old('participant_first_name', $testData['participant_first_name'] ?? ''),
            'last_name' => old('participant_last_name', $testData['participant_last_name'] ?? ''),
            'email' => old('participant_email', $testData['participant_email'] ?? (($isTestMode ?? false) ? '' : (auth()->check() ? auth()->user()->email : ''))),
        ]];
    }
    $variantClass = ($formVariant ?? 'legacy') === 'v2' ? 'order-v2' : 'order-legacy';
@endphp

<div id="order-form-participants-root"
     class="order-form-participants {{ $variantClass }}"
     data-max-participants="{{ $maxParticipants }}"
     data-unit-price="{{ $unitPrice !== null ? number_format((float) $unitPrice, 2, '.', '') : '' }}"
     data-email-availability-url="{{ route('courses.participant-email-availability', $course->id) }}"
     data-except-ident="{{ $testData['order_ident'] ?? '' }}"
     data-analytics-section="participants">

    <div id="order-form-participant-rows">
        @foreach($prefillRows as $idx => $row)
            @include('courses.partials.order-form-participant-row', [
                'index' => $idx,
                'row' => $row,
                'formVariant' => $formVariant ?? 'legacy',
                'isTestMode' => $isTestMode ?? false,
            ])
        @endforeach
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2" id="order-form-participants-actions">
        <button type="button" class="btn btn-outline-success btn-sm" id="order-form-add-participant" hidden>
            <i class="bi bi-person-plus"></i> Dodaj kolejnego uczestnika
        </button>
        <div class="ms-auto text-end" id="order-form-total-price-wrap" @if($unitPrice === null) hidden @endif>
            <span class="text-muted small">Do zapłaty:</span>
            <strong id="order-form-total-price" class="fs-5 text-primary">
                {{ $unitPrice !== null ? number_format((float) $unitPrice, 2, ',', ' ') : '—' }}
            </strong>
            <span class="text-muted small">PLN brutto</span>
            <div class="small text-muted" id="order-form-price-breakdown"></div>
        </div>
    </div>

    <template id="order-form-participant-row-template">
        @include('courses.partials.order-form-participant-row', [
            'index' => '__INDEX__',
            'row' => ['first_name' => '', 'last_name' => '', 'email' => ''],
            'formVariant' => $formVariant ?? 'legacy',
            'isTestMode' => $isTestMode ?? false,
            'isTemplate' => true,
        ])
    </template>
</div>
