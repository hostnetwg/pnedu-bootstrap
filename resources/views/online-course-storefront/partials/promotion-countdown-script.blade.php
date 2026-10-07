<script>
(function () {
    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function formatRemaining(ms) {
        if (ms <= 0) {
            return 'zakończona';
        }

        var totalSec = Math.floor(ms / 1000);
        var days = Math.floor(totalSec / 86400);
        var hours = Math.floor((totalSec % 86400) / 3600);
        var minutes = Math.floor((totalSec % 3600) / 60);
        var seconds = totalSec % 60;

        if (days > 0) {
            return days + 'd ' + pad2(hours) + 'h ' + pad2(minutes) + 'm ' + pad2(seconds) + 's';
        }

        if (hours > 0) {
            return hours + 'h ' + pad2(minutes) + 'm ' + pad2(seconds) + 's';
        }

        return minutes + 'm ' + pad2(seconds) + 's';
    }

    function catalogOfferState(offer, now) {
        if (offer.promoAmount == null) {
            return { amount: offer.regularAmount, promo: false };
        }

        if (offer.ends) {
            var endMs = Date.parse(offer.ends);
            if (!Number.isNaN(endMs) && now > endMs) {
                return { amount: offer.regularAmount, promo: false };
            }
        }

        return { amount: offer.promoAmount, promo: true };
    }

    function renderCatalogRegular(view, offer) {
        view.replaceChildren();
        var paragraph = document.createElement('p');
        paragraph.className = 'mb-3';
        var strong = document.createElement('strong');
        strong.textContent = offer.regular + ' zł';
        paragraph.appendChild(strong);
        view.appendChild(paragraph);
    }

    function renderCatalogPromo(view, offer) {
        view.replaceChildren();
        var wrap = document.createElement('div');
        wrap.className = 'd-flex flex-column gap-1 mb-3';

        var row = document.createElement('div');
        row.className = 'd-flex flex-wrap align-items-baseline gap-2';
        var oldPrice = document.createElement('span');
        oldPrice.className = 'text-muted text-decoration-line-through';
        oldPrice.style.fontSize = '0.85rem';
        oldPrice.textContent = offer.regular + ' PLN';
        var current = document.createElement('strong');
        current.className = 'text-danger';
        current.textContent = offer.promo + ' PLN';
        row.appendChild(oldPrice);
        row.appendChild(current);
        wrap.appendChild(row);

        if (offer.endLabel) {
            var end = document.createElement('small');
            end.className = 'd-block';
            end.style.fontSize = '0.85rem';
            end.style.color = '#000';
            end.textContent = 'Promocja trwa do: ' + offer.endLabel;
            wrap.appendChild(end);
        }

        if (offer.omnibus) {
            var omnibus = document.createElement('small');
            omnibus.className = 'd-block';
            omnibus.style.fontSize = '0.75rem';
            omnibus.style.color = '#aaa';
            omnibus.append('Najniższa cena z 30 dni przed obniżką: ');
            var omnibusValue = document.createElement('strong');
            omnibusValue.style.color = '#aaa';
            omnibusValue.textContent = offer.omnibus + ' PLN';
            omnibus.appendChild(omnibusValue);
            wrap.appendChild(omnibus);
        }

        if (offer.countdown && offer.ends) {
            var countdown = document.createElement('div');
            countdown.className = 'small fw-semibold text-danger mt-1';
            countdown.setAttribute('data-promotion-countdown', '');
            countdown.setAttribute('data-countdown-target', offer.ends);
            countdown.append('Do końca promocji: ');
            var countdownValue = document.createElement('strong');
            countdownValue.className = 'js-promotion-countdown-value';
            countdownValue.setAttribute('aria-live', 'polite');
            countdownValue.textContent = '—';
            countdown.appendChild(countdownValue);
            wrap.appendChild(countdown);
        }

        view.appendChild(wrap);
    }

    function tickCatalogPrices(now) {
        document.querySelectorAll('[data-catalog-price]').forEach(function (root) {
            var dataEl = root.querySelector('.js-catalog-price-offers');
            var view = root.querySelector('.js-catalog-price-view');
            if (!dataEl || !view) {
                return;
            }

            var offers;
            try {
                offers = JSON.parse(dataEl.textContent || '[]');
            } catch (error) {
                return;
            }

            var best = null;
            offers.forEach(function (offer) {
                var state = catalogOfferState(offer, now);
                if (!best || state.amount < best.amount || (state.amount === best.amount && offer.id < best.offer.id)) {
                    best = { amount: state.amount, promo: state.promo, offer: offer };
                }
            });

            if (!best) {
                return;
            }

            var key = (best.promo ? 'promo-' : 'regular-') + best.offer.id;
            if (root.getAttribute('data-shown-key') === key) {
                return;
            }

            if (best.promo) {
                renderCatalogPromo(view, best.offer);
            } else {
                renderCatalogRegular(view, best.offer);
            }

            var orderLink = root.parentElement ? root.parentElement.querySelector('[data-catalog-order]') : null;
            if (orderLink && best.offer.orderUrl) {
                orderLink.setAttribute('href', best.offer.orderUrl);
            }

            root.setAttribute('data-shown-key', key);
        });
    }

    function tick() {
        var now = Date.now();
        tickCatalogPrices(now);
        document.querySelectorAll('[data-promotion-countdown]').forEach(function (el) {
            var valueEl = el.querySelector('.js-promotion-countdown-value');
            var targetIso = el.getAttribute('data-countdown-target');
            if (!valueEl || !targetIso) {
                return;
            }

            var targetMs = Date.parse(targetIso);
            if (Number.isNaN(targetMs)) {
                valueEl.textContent = '—';
                return;
            }

            valueEl.textContent = formatRemaining(targetMs - now);
        });
    }

    if (!document.querySelector('[data-promotion-countdown], [data-catalog-price]')) {
        return;
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
