@php
    $legalService = app(\App\Services\LegalCheckoutService::class);
    $termsVersion = (string) config('legal.terms.current_version');
    $earlyWindow = $legalService->requiresEarlyPerformanceStatement(
        $course,
        \App\Support\OrderFormCustomerProfile::PERSON
    );
    $legalPriceInfo = $priceInfo ?? $course->getCurrentPrice();
    $legalUnitPrice = isset($legalPriceInfo['price']) ? (float) $legalPriceInfo['price'] : null;
    $legalProfile = old('customer_profile', $customerProfile ?? old('buyer_type', $defaultCustomerProfile ?? 'school'));
@endphp

<section class="border rounded bg-light p-3 mt-4 paid-checkout-legal"
    data-early-window="{{ $earlyWindow ? '1' : '0' }}"
    data-unit-price="{{ $legalUnitPrice !== null ? number_format($legalUnitPrice, 2, '.', '') : '' }}"
    data-payment-mode="{{ $paymentMode ?? '' }}"
    aria-labelledby="paid-checkout-summary-title">
    <h3 class="h6 mb-3" id="paid-checkout-summary-title">Podsumowanie zamówienia</h3>
    <dl class="row small mb-3">
        <dt class="col-sm-4">Szkolenie</dt>
        <dd class="col-sm-8">{{ $course->plainTitle() }}</dd>
        <dt class="col-sm-4">Termin i forma</dt>
        <dd class="col-sm-8">
            {{ $course->start_date?->locale('pl')->translatedFormat('l, d.m.Y H:i') ?? 'zgodnie z ofertą' }}
            · {{ in_array(strtolower((string) $course->type), ['offline', 'stacjonarne', 'stationary', 'onsite'], true) ? 'stacjonarnie' : 'online' }}
        </dd>
        <dt class="col-sm-4">Uczestnicy</dt>
        <dd class="col-sm-8"><span data-legal-participant-count>1</span></dd>
        @if(filled($course->recording_access))
            <dt class="col-sm-4">Dostęp do nagrania</dt>
            <dd class="col-sm-8">{{ $course->recording_access }}</dd>
        @endif
        <dt class="col-sm-4">Płatność</dt>
        <dd class="col-sm-8">
            <span data-legal-payment-label>{{ $paymentLabel ?? 'zgodnie z wybraną opcją' }}</span>
            <p class="small text-muted mb-0 mt-1" data-online-payment-hint @if(($paymentMode ?? null) !== 'online') hidden @endif>
                Po potwierdzeniu zakupu przejdziesz do bezpiecznej płatności online.
            </p>
        </dd>
        <dt class="col-sm-4">Do zapłaty</dt>
        <dd class="col-sm-8 fw-bold">
            <span data-legal-total-price>{{ $legalUnitPrice !== null ? number_format($legalUnitPrice, 2, ',', ' ') : 'zgodnie z ofertą' }}</span>
            @if($legalUnitPrice !== null) PLN brutto @endif
        </dd>
    </dl>

    <p class="small mb-2">
        Składając zamówienie, zawierasz umowę zgodnie z
        <a href="{{ route('regulamin.version', ['version' => $termsVersion]) }}" target="_blank" rel="noopener">Regulaminem</a>.
    </p>
    <p class="small mb-2">
        Administratorem danych jest Platforma Nowoczesnej Edukacji Waldemar Grabowski.
        Dane wykorzystujemy do obsługi i realizacji zamówienia oraz rozliczeń.
        Szczegóły: <a href="{{ route('rodo') }}" target="_blank" rel="noopener">RODO</a>
        i <a href="{{ route('polityka-prywatnosci') }}" target="_blank" rel="noopener">Polityka prywatności</a>.
    </p>

    <div data-consumer-withdrawal-info hidden>
        <p class="small mb-2">
            Masz co do zasady 14 dni na odstąpienie od umowy zawartej na odległość.
            <a href="{{ route('withdrawal') }}" target="_blank" rel="noopener">Zasady odstąpienia</a>.
        </p>
    </div>

    <div class="form-check mt-3" data-early-performance-wrap hidden>
        <input class="form-check-input @error('early_performance_accepted') is-invalid @enderror"
            type="checkbox"
            name="early_performance_accepted"
            id="early_performance_accepted"
            value="1"
            @checked(old('early_performance_accepted'))>
        <label class="form-check-label small" for="early_performance_accepted">
            {{ $legalService->statement() }} <span class="text-danger">*</span>
        </label>
        @error('early_performance_accepted')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</section>

@once
@push('scripts')
<script>
(function () {
    function initLegalCheckout() {
        document.querySelectorAll('.paid-checkout-legal').forEach(function (box) {
            var form = box.closest('form');
            if (!form) return;
            var earlyWindow = box.dataset.earlyWindow === '1';
            var statementWrap = box.querySelector('[data-early-performance-wrap]');
            var statement = box.querySelector('[name="early_performance_accepted"]');
            var withdrawalInfo = box.querySelector('[data-consumer-withdrawal-info]');
            var countOutput = box.querySelector('[data-legal-participant-count]');
            var totalOutput = box.querySelector('[data-legal-total-price]');
            var paymentOutput = box.querySelector('[data-legal-payment-label]');
            var onlineHint = box.querySelector('[data-online-payment-hint]');
            var unitPrice = parseFloat(box.dataset.unitPrice || '');
            var forcedPaymentMode = box.dataset.paymentMode || '';

            function profile() {
                var selectedProfile = form.querySelector('[name="customer_profile"]:checked');
                if (selectedProfile) return selectedProfile.value;
                var buyer = form.querySelector('[name="buyer_type"]:checked');
                if (buyer) return buyer.value === 'company' ? 'organisation' : buyer.value;
                var hiddenProfile = form.querySelector('[name="customer_profile"]');
                return hiddenProfile ? hiddenProfile.value : 'school';
            }

            function participantCount() {
                var rows = form.querySelectorAll('#order-form-participant-rows .order-form-participant-row');
                return Math.max(1, rows.length || 1);
            }

            function formatPrice(value) {
                return value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }

            function update() {
                var protectedProfile = ['person', 'jdg'].indexOf(profile()) !== -1;
                var requiresStatement = protectedProfile && earlyWindow;
                if (withdrawalInfo) withdrawalInfo.hidden = !protectedProfile;
                if (statementWrap) statementWrap.hidden = !requiresStatement;
                if (statement) {
                    statement.required = requiresStatement;
                    statement.disabled = !requiresStatement;
                    if (!requiresStatement) statement.checked = false;
                }

                var count = participantCount();
                if (countOutput) countOutput.textContent = String(count);
                if (totalOutput && !isNaN(unitPrice)) totalOutput.textContent = formatPrice(unitPrice * count);

                var payment = form.querySelector('[name="payment_type"]:checked');
                var gateway = form.querySelector('[name="payment_gateway"]:checked');
                var isOnline = forcedPaymentMode === 'online'
                    || (!forcedPaymentMode && payment && payment.value === 'online');
                var isDeferred = forcedPaymentMode === 'deferred'
                    || (!forcedPaymentMode && payment && payment.value === 'deferred');
                if (onlineHint) onlineHint.hidden = !isOnline;
                if (paymentOutput && (payment || forcedPaymentMode)) {
                    var terms = form.querySelector('[name="payment_terms"]');
                    if (isDeferred) {
                        paymentOutput.textContent = 'faktura z odroczonym terminem'
                            + (terms && terms.value !== '' ? ' (' + terms.value + ' dni)' : '');
                    } else if (isOnline) {
                        paymentOutput.textContent = 'płatność online'
                            + (gateway ? ' (' + gateway.value.toUpperCase() + ')' : '');
                    }
                }
            }

            form.addEventListener('change', update);
            var participants = form.querySelector('#order-form-participant-rows');
            if (participants && typeof MutationObserver === 'function') {
                new MutationObserver(update).observe(participants, {childList: true});
            }
            update();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLegalCheckout);
    } else {
        initLegalCheckout();
    }
})();
</script>
@endpush
@endonce
