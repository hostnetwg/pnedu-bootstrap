{{--
  $course, $paymentOptions, $activeCoursePriceVariants (Collection), $suffix (unikalny fragment id dla etykiet)
--}}
@php
    $variantCount = $activeCoursePriceVariants->count();
    $priceInfo = $course->getCurrentPrice();
    $onlyVariant = $variantCount === 1 ? $activeCoursePriceVariants->first() : null;
    $marketingAttribution = app(\App\Services\MarketingAttributionService::class);
    $fbParam = trim((string) $marketingAttribution->resolveCampaignCode(request()));
    $marketingSuffix = $marketingAttribution->querySuffixForLinks(request());
    $orderFormBase = route('payment.order-form', $course->id);
    $deferredBase = route('payment.deferred', $course->id);
    $orderFormHref = $onlyVariant
        ? ($orderFormBase.'?price_variant_id='.$onlyVariant->id.$marketingSuffix)
        : ($orderFormBase.($marketingSuffix !== '' ? '?'.ltrim($marketingSuffix, '&') : ''));
    $deferredHref = $onlyVariant
        ? ($deferredBase.'?price_variant_id='.$onlyVariant->id.$marketingSuffix)
        : ($deferredBase.($marketingSuffix !== '' ? '?'.ltrim($marketingSuffix, '&') : ''));
@endphp
<h3>Wybierz formę płatności i&nbsp;zarezerwuj miejsce!</h3>
@if($variantCount > 1)
    <p class="small text-muted text-center mb-2 px-1">Domyślnie wybrany jest wariant o <strong>najniższym numerze ID</strong> w systemie (pierwszy na liście); możesz go zmienić przed przejściem do zamówienia.</p>
    <div class="text-start mb-3 px-1">
        @foreach($activeCoursePriceVariants as $v)
            <div class="form-check mb-2">
                <input
                    class="form-check-input"
                    type="radio"
                    name="course_show_price_variant_id"
                    id="cv_{{ $suffix }}_{{ $v->id }}"
                    value="{{ $v->id }}"
                    @checked($loop->first)
                >
                <label class="form-check-label small" for="cv_{{ $suffix }}_{{ $v->id }}">
                    {{ filled($v->name) ? $v->name : 'Wariant #'.$v->id }}
                    — <strong>{{ number_format($v->getCurrentPrice(), 2, ',', ' ') }} PLN</strong> (brutto)
                </label>
            </div>
        @endforeach
    </div>
@elseif($priceInfo)
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
                    Najniższa cena z ostatnich 30 dni przed obniżką wynosiła: <strong style="color: #aaa;">{{ number_format($priceInfo['original_price'], 2, ',', ' ') }} PLN</strong>
                </small>
            </div>
        @else
            <span class="fw-bold" style="font-size: 1.2rem; color: #1976d2;">{{ number_format($priceInfo['price'], 2, ',', ' ') }} PLN</span> <span style="font-size: 1.2rem; color: #1976d2;">(brutto)</span>
        @endif
    </div>
@endif
<div class="d-flex flex-column gap-2 mb-3 align-items-center">
    @if($paymentOptions['show_pay_publigo'] ?? true)
        <a href="{{ $course->getPubligoPaymentUrl() ?? route('payment.online', $course->id) }}" target="_blank" class="btn btn-primary-custom btn-lg fw-bold shadow-sm w-100">Zapłać online</a>
    @endif
    @if($paymentOptions['show_pay_online'] ?? true)
        <a href="{{ route('payment.online', $course->id) }}" class="btn btn-lg fw-bold shadow-sm w-100 text-white" style="background-color: #6f42c1; border-color: #6f42c1;">Zapłać online</a>
    @endif
    @if($paymentOptions['show_deferred_order'] ?? true)
        @if($variantCount > 1)
            <a href="#"
               class="btn btn-orange btn-lg fw-bold shadow-sm w-100 js-cta-needs-variant disabled pe-none"
               data-href-base="{{ $deferredBase }}"
               data-marketing-suffix="{{ $marketingSuffix }}"
               aria-disabled="true"
               title="Najpierw wybierz wariant cenowy powyżej"
            >Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
        @else
            <a href="{{ $deferredHref }}" class="btn btn-orange btn-lg fw-bold shadow-sm w-100">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
        @endif
    @endif
    @if($paymentOptions['show_order_form'] ?? true)
        @if($variantCount > 1)
            <a href="#"
               class="btn btn-purchase-cta btn-lg fw-bold w-100 js-cta-needs-variant disabled pe-none"
               data-href-base="{{ $orderFormBase }}"
               data-marketing-suffix="{{ $marketingSuffix }}"
               aria-disabled="true"
               title="Najpierw wybierz wariant cenowy powyżej"
            >Zamawiam szkolenie</a>
        @else
            <a href="{{ $orderFormHref }}" class="btn btn-purchase-cta btn-lg fw-bold w-100">Zamawiam szkolenie</a>
        @endif
    @endif
    @if((($paymentOptions['show_order_form_alt'] ?? true) && !empty($course->id_old)))
        <a href="https://zdalna-lekcja.pl/zamowienia/formularz/?idP={{ $course->id_old }}" target="_blank" class="btn btn-lg fw-bold shadow-sm w-100 text-white" style="background-color: #0d6b0d; border-color: #0d6b0d;">Formularz zamówienia z&nbsp;odroczonym terminem płatności</a>
    @endif
</div>
<div class="mt-2 text-muted">Liczba miejsc ograniczona –<br>nie zwlekaj z&nbsp;rejestracją!</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var radios = document.querySelectorAll('input[name="course_show_price_variant_id"]');
                if (!radios.length) {
                    return;
                }
                if (!document.querySelector('input[name="course_show_price_variant_id"]:checked')) {
                    radios[0].checked = true;
                }
                function selectedId() {
                    var c = document.querySelector('input[name="course_show_price_variant_id"]:checked');
                    return c ? c.value : null;
                }
                function updateCtas() {
                    var vid = selectedId();
                    document.querySelectorAll('a.js-cta-needs-variant').forEach(function (a) {
                        var base = a.getAttribute('data-href-base');
                        if (!base) {
                            return;
                        }
                        if (!vid) {
                            a.setAttribute('href', '#');
                            a.classList.add('disabled', 'pe-none');
                            a.setAttribute('aria-disabled', 'true');
                        } else {
                            var sep = base.indexOf('?') >= 0 ? '&' : '?';
                            var href = base + sep + 'price_variant_id=' + encodeURIComponent(vid);
                            var marketingSuffix = (a.getAttribute('data-marketing-suffix') || '').trim();
                            if (marketingSuffix) {
                                href += marketingSuffix.indexOf('&') === 0 ? marketingSuffix : '&' + marketingSuffix;
                            }
                            a.setAttribute('href', href);
                            a.classList.remove('disabled', 'pe-none');
                            a.removeAttribute('aria-disabled');
                        }
                    });
                }
                radios.forEach(function (r) {
                    r.addEventListener('change', updateCtas);
                });
                updateCtas();
            });
        </script>
    @endpush
@endonce
