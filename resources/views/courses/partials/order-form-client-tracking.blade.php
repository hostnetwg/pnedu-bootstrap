{{--
    Etap B2 — JS collector formularza zamówienia (fail-silent, RODO-safe).

    Zasady:
    - wysyła WYŁĄCZNIE 4 eventy: order_form_started, order_form_section_interacted,
      order_form_cta_clicked, order_form_submit_clicked,
    - NIGDY nie czyta wartości pól (żadnych input.value/textarea/select, FormData, innerText),
      payload zawiera tylko: event_name, event_uuid, course_id, price_variant_id oraz
      whitelistowane section_key / cta_key / trigger (z atrybutów data-analytics-*),
    - batch w pamięci (<= max), debounce ~3 s, flush na visibilitychange/pagehide/submit,
    - sendBeacon dla flush przy opuszczaniu strony, fetch(keepalive) dla debounce,
    - każdy błąd jest łapany i ignorowany; collector nigdy nie blokuje formularza
      (brak preventDefault na submit). Backend (B1/B1a) wymusza tryby i deduplikację.
--}}
<script>
(function () {
    'use strict';

    try {
        var cfgEl = document.getElementById('order-form-analytics-config');
        if (!cfgEl) { return; }

        var endpoint = cfgEl.getAttribute('data-endpoint') || '';
        if (!endpoint) { return; }

        var courseId = parseInt(cfgEl.getAttribute('data-course-id') || '', 10);
        if (!courseId || courseId <= 0) { return; }

        var priceVariantRaw = parseInt(cfgEl.getAttribute('data-price-variant-id') || '', 10);
        var priceVariantId = (priceVariantRaw && priceVariantRaw > 0) ? priceVariantRaw : null;

        var maxBatch = parseInt(cfgEl.getAttribute('data-max-batch') || '', 10);
        if (!maxBatch || maxBatch <= 0) { maxBatch = 20; }

        var DEBOUNCE_MS = 3000;

        // Whitelisty zgodne z backendem B1/B1a (defensywnie również po stronie JS).
        var SECTION_KEYS = ['buyer_data', 'recipient_data', 'participants', 'payment_method', 'invoice', 'consents', 'summary'];
        var CTA_KEYS = ['add_participant', 'remove_participant', 'select_online_payment', 'select_deferred_invoice', 'back_to_course', 'submit_order'];

        var form = cfgEl.previousElementSibling;
        // Znajdź właściwy formularz przez dowolną oznaczoną sekcję (pewniejsze niż rodzeństwo).
        var anySection = document.querySelector('[data-analytics-section]');
        if (anySection && anySection.closest) { form = anySection.closest('form'); }
        if (!form) { return; }

        var queue = [];
        var startedSent = false;
        var sectionsSent = {};
        var flushTimer = null;

        function uuid() {
            try {
                if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                    return window.crypto.randomUUID();
                }
                if (window.crypto && window.crypto.getRandomValues) {
                    var b = new Uint8Array(16);
                    window.crypto.getRandomValues(b);
                    b[6] = (b[6] & 0x0f) | 0x40;
                    b[8] = (b[8] & 0x3f) | 0x80;
                    var h = [];
                    for (var i = 0; i < 16; i++) { h.push((b[i] + 0x100).toString(16).slice(1)); }
                    return h[0] + h[1] + h[2] + h[3] + '-' + h[4] + h[5] + '-' + h[6] + h[7] + '-' + h[8] + h[9] + '-' + h[10] + h[11] + h[12] + h[13] + h[14] + h[15];
                }
            } catch (e) {}
            // Brak crypto — pomijamy event_uuid; serwer wygeneruje własny.
            return null;
        }

        function buildBody(batch) {
            var payload = { course_id: courseId, events: batch };
            if (priceVariantId !== null) { payload.price_variant_id = priceVariantId; }
            return JSON.stringify(payload);
        }

        function send(useBeacon) {
            try {
                if (queue.length === 0) { return; }
                var batch = queue.splice(0, maxBatch);
                var body = buildBody(batch);

                if (useBeacon && navigator && typeof navigator.sendBeacon === 'function') {
                    try {
                        var blob = new Blob([body], { type: 'application/json' });
                        if (navigator.sendBeacon(endpoint, blob)) { return; }
                    } catch (e) { /* fall through do fetch */ }
                }

                if (typeof fetch === 'function') {
                    fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: body,
                        keepalive: true,
                        credentials: 'same-origin'
                    }).catch(function () { /* fail-silent */ });
                }
            } catch (e) { /* fail-silent */ }
        }

        function scheduleFlush() {
            try {
                if (flushTimer) { clearTimeout(flushTimer); }
                flushTimer = setTimeout(function () { send(false); }, DEBOUNCE_MS);
            } catch (e) {}
        }

        function enqueue(name, fields) {
            try {
                var ev = { event_name: name };
                var id = uuid();
                if (id) { ev.event_uuid = id; }
                if (fields) {
                    for (var k in fields) {
                        if (Object.prototype.hasOwnProperty.call(fields, k) && fields[k] != null) {
                            ev[k] = fields[k];
                        }
                    }
                }
                queue.push(ev);
                if (queue.length >= maxBatch) { send(false); } else { scheduleFlush(); }
            } catch (e) {}
        }

        function markStarted(trigger) {
            if (startedSent) { return; }
            startedSent = true;
            enqueue('order_form_started', { trigger: trigger || 'first_interaction' });
        }

        function handleSection(target) {
            try {
                if (!target || !target.closest) { return; }
                var el = target.closest('[data-analytics-section]');
                if (!el) { return; }
                var key = el.getAttribute('data-analytics-section');
                if (SECTION_KEYS.indexOf(key) === -1) { return; }
                if (sectionsSent[key]) { return; }
                sectionsSent[key] = true;
                enqueue('order_form_section_interacted', { section_key: key, trigger: 'section_click' });
            } catch (e) {}
        }

        function handleCta(target) {
            try {
                if (!target || !target.closest) { return; }
                var el = target.closest('[data-analytics-cta]');
                if (!el) { return; }
                var key = el.getAttribute('data-analytics-cta');
                if (CTA_KEYS.indexOf(key) === -1) { return; }
                enqueue('order_form_cta_clicked', { cta_key: key, trigger: 'cta_click' });
            } catch (e) {}
        }

        function onInteraction(e) {
            try {
                markStarted('first_interaction');
                handleSection(e.target);
                if (e.type === 'click' || e.type === 'change') { handleCta(e.target); }
            } catch (err) {}
        }

        form.addEventListener('input', onInteraction, true);
        form.addEventListener('change', onInteraction, true);
        form.addEventListener('click', onInteraction, true);

        // Submit: rejestrujemy kliknięcie submitu po stronie JS i flushujemy beaconem.
        // NIE wywołujemy preventDefault — formularz wysyła się normalnie.
        form.addEventListener('submit', function () {
            try {
                markStarted('first_interaction');
                enqueue('order_form_submit_clicked', { trigger: 'cta_click' });
                send(true);
            } catch (e) {}
        }, true);

        document.addEventListener('visibilitychange', function () {
            try { if (document.visibilityState === 'hidden') { send(true); } } catch (e) {}
        });
        window.addEventListener('pagehide', function () { try { send(true); } catch (e) {} });
    } catch (e) { /* collector nigdy nie może wpłynąć na formularz */ }
})();
</script>
