{{--
    Etap B2 + 2C + 2D — JS collector formularza zamówienia (fail-silent, RODO-safe).

    Zasady:
    - legacy B1: order_form_started, order_form_section_interacted, order_form_cta_clicked, order_form_submit_clicked,
    - schema v2: form_visible, form_first_interaction, form_section_*, form_field_changed,
      form_submit_clicked, client_validation_failed, form_last_activity,
    - sekcje v2: preferuj data-analytics-section-v2, fallback do legacy mapowania,
    - NIGDY nie wysyła wartości pól — tylko whitelistowane klucze techniczne.
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
        var formSessionId = cfgEl.getAttribute('data-form-session-id') || null;
        var trackingSchemaVersion = parseInt(cfgEl.getAttribute('data-tracking-schema-version') || '2', 10);

        var maxBatch = parseInt(cfgEl.getAttribute('data-max-batch') || '', 10);
        if (!maxBatch || maxBatch <= 0) { maxBatch = 20; }

        var DEBOUNCE_MS = 3000;
        var LAST_ACTIVITY_THROTTLE_MS = 5000;
        var FIELD_CHANGED_DEBOUNCE_MS = 400;
        var pageLoadTime = Date.now();

        var LEGACY_SECTION_KEYS = ['buyer_data', 'recipient_data', 'participants', 'payment_method', 'invoice', 'consents', 'summary'];
        var CTA_KEYS = ['add_participant', 'remove_participant', 'select_online_payment', 'select_deferred_invoice', 'back_to_course', 'submit_order'];
        var V2_SECTION_KEYS = ['contact', 'invoice_buyer', 'invoice_recipient', 'participants', 'payment', 'consents', 'submit'];

        var FIELD_KEY_MAP = {
            contact_name: 'contact_name',
            contact_name_display: 'contact_name',
            contact_first_name: 'contact_name',
            contact_last_name: 'contact_name',
            contact_phone: 'contact_phone',
            contact_email: 'contact_email',
            buyer_type: 'buyer_type',
            buyer_nip: 'buyer_nip',
            buyer_address: 'buyer_address',
            buyer_postcode: 'buyer_postcode',
            buyer_city: 'buyer_city',
            recipient_nip: 'recipient_nip',
            recipient_address: 'recipient_address',
            recipient_postcode: 'recipient_postcode',
            recipient_city: 'recipient_city',
            participant_first_name: 'participant_first_name',
            participant_last_name: 'participant_last_name',
            participant_email: 'participant_email',
            payment_type: 'payment_type',
            payment_terms: 'payment_terms',
            payment_gateway: 'payment_gateway',
            invoice_notes: 'invoice_notes',
            consent: 'consent'
        };

        var PARTICIPANT_FIELD_MAP = {
            first_name: 'participant_first_name',
            last_name: 'participant_last_name',
            email: 'participant_email'
        };

        var form = cfgEl.previousElementSibling;
        var anySection = document.querySelector('[data-analytics-section], [data-analytics-section-v2]');
        if (anySection && anySection.closest) { form = anySection.closest('form'); }
        if (!form) { return; }

        var queue = [];
        var flushTimer = null;
        var legacyStartedSent = false;
        var legacySectionsSent = {};
        var formVisibleSent = false;
        var firstInteractionSent = false;
        var sectionsViewedSent = {};
        var sectionsStartedSent = {};
        var sectionsCompletedSent = {};
        var fieldsChangedSent = {};
        var fieldChangedTimers = {};
        var copiedFieldSources = {};
        var gusApplying = false;
        var gusSuccessAt = { buyer: null, recipient: null };
        var gusErrorAt = { buyer: null, recipient: null };
        var gusAppliedFieldKeys = {};
        var fieldsEditedAfterGusSent = {};
        var gusFallbackStarted = { buyer: false, recipient: false };
        var lastActivityTimer = null;
        var lastActivityPending = null;

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
            return null;
        }

        function secondsFromPageLoad() {
            return Math.max(0, Math.round((Date.now() - pageLoadTime) / 1000));
        }

        function buildBody(batch) {
            var payload = { course_id: courseId, events: batch };
            if (priceVariantId !== null) { payload.price_variant_id = priceVariantId; }
            if (formSessionId) { payload.form_session_id = formSessionId; }
            payload.tracking_schema_version = trackingSchemaVersion || 2;
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
                    } catch (e) {}
                }

                if (typeof fetch === 'function') {
                    fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: body,
                        keepalive: true,
                        credentials: 'same-origin'
                    }).catch(function () {});
                }
            } catch (e) {}
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

        function isVisible(el) {
            if (!el) { return false; }
            try {
                if (el.offsetParent === null && el !== document.body) {
                    var style = window.getComputedStyle(el);
                    if (style.display === 'none' || style.visibility === 'hidden') { return false; }
                }
            } catch (e) {}
            return true;
        }

        function isWhitelistedV2Section(key) {
            return key && V2_SECTION_KEYS.indexOf(key) !== -1;
        }

        function normalizeFieldKey(el) {
            if (!el) { return null; }
            var raw = el.name || el.id || '';
            if (!raw) { return null; }

            var participantMatch = raw.match(/^participants\[\d+\]\[([a-z_]+)\]$/i);
            if (participantMatch && PARTICIPANT_FIELD_MAP[participantMatch[1]]) {
                return PARTICIPANT_FIELD_MAP[participantMatch[1]];
            }

            return FIELD_KEY_MAP[raw] || null;
        }

        function fieldKeyForElement(el) {
            return normalizeFieldKey(el);
        }

        function fieldTypeForElement(el) {
            if (!el) { return 'unknown'; }
            var tag = (el.tagName || '').toLowerCase();
            if (tag === 'textarea') { return 'textarea'; }
            if (tag === 'select') { return 'select'; }
            var type = (el.type || '').toLowerCase();
            if (type === 'email') { return 'email'; }
            if (type === 'tel') { return 'tel'; }
            if (type === 'number') { return 'number'; }
            if (type === 'radio') { return 'radio'; }
            if (type === 'checkbox') { return 'checkbox'; }
            if (type === 'hidden') { return 'hidden'; }
            if (type === 'text' || type === '') { return 'text'; }
            return 'unknown';
        }

        function buyerFieldsets() {
            return form.querySelectorAll('fieldset.order-form-section[data-analytics-section="buyer_data"]');
        }

        function legacyV2SectionKeyForElement(el) {
            if (!el || !el.closest) { return null; }

            var submitBtn = document.getElementById('order-form-submit-btn');
            if (submitBtn && (el === submitBtn || submitBtn.contains(el))) {
                return 'submit';
            }

            var legacyEl = el.closest('[data-analytics-section]');
            if (!legacyEl) { return null; }

            var legacy = legacyEl.getAttribute('data-analytics-section');
            if (legacy === 'buyer_data') {
                var buyer = buyerFieldsets();
                if (buyer[0] && buyer[0].contains(el)) { return 'contact'; }
                if (legacyEl.closest('.recipient-wrapper') || legacy === 'recipient_data') {
                    return 'invoice_recipient';
                }
                return 'invoice_buyer';
            }

            var map = {
                recipient_data: 'invoice_recipient',
                participants: 'participants',
                payment_method: 'payment',
                invoice: 'payment',
                consents: 'consents',
                summary: 'submit'
            };

            return map[legacy] || null;
        }

        function v2SectionKeyForElement(el) {
            if (!el || !el.closest) { return null; }

            var v2El = el.closest('[data-analytics-section-v2]');
            if (v2El) {
                var explicit = v2El.getAttribute('data-analytics-section-v2');
                if (isWhitelistedV2Section(explicit)) {
                    return explicit;
                }
            }

            return legacyV2SectionKeyForElement(el);
        }

        function sectionRoot(v2Key) {
            if (!v2Key) { return null; }

            var explicit = form.querySelector('[data-analytics-section-v2="' + v2Key + '"]');
            if (explicit) { return explicit; }

            var buyer = buyerFieldsets();
            switch (v2Key) {
                case 'contact':
                    return buyer[0] || null;
                case 'invoice_buyer':
                    return buyer[1] || null;
                case 'invoice_recipient':
                    return form.querySelector('[data-analytics-section="recipient_data"]');
                case 'participants':
                    return form.querySelector('[data-analytics-section="participants"]');
                case 'payment':
                    return form.querySelector('[data-analytics-section="payment_method"]');
                case 'submit':
                    var btn = document.getElementById('order-form-submit-btn');
                    return btn ? (btn.closest('[data-analytics-section-v2="submit"]') || btn.closest('.d-flex') || btn) : null;
                case 'consents':
                    return form.querySelector('[data-analytics-section-v2="consents"], [data-analytics-section="consents"]');
                default:
                    return null;
            }
        }

        function completedSectionsCount() {
            var count = 0;
            for (var i = 0; i < V2_SECTION_KEYS.length; i++) {
                if (sectionsCompletedSent[V2_SECTION_KEYS[i]]) { count++; }
            }
            return count;
        }

        function isFieldComplete(el) {
            if (!el || el.disabled) { return false; }
            var type = (el.type || '').toLowerCase();
            if (type === 'checkbox' || type === 'radio') {
                if (type === 'radio') {
                    var group = form.querySelectorAll('input[type="radio"][name="' + el.name + '"]');
                    for (var i = 0; i < group.length; i++) {
                        if (group[i].checked) { return true; }
                    }
                    return false;
                }
                return el.checked;
            }
            if (type === 'hidden') { return true; }
            var val = el.value;
            return typeof val === 'string' && val.trim() !== '';
        }

        function sectionCompletion(v2Key) {
            var root = sectionRoot(v2Key);
            if (!root) { return { required: 0, completed: 0, complete: false }; }

            var fields = root.querySelectorAll('input, select, textarea');
            var required = 0;
            var completed = 0;
            var seenRadio = {};

            for (var i = 0; i < fields.length; i++) {
                var el = fields[i];
                if (!isVisible(el)) { continue; }

                var type = (el.type || '').toLowerCase();
                var isRequired = el.required || el.getAttribute('aria-required') === 'true';

                if (type === 'radio') {
                    if (seenRadio[el.name]) { continue; }
                    seenRadio[el.name] = true;
                    if (!isRequired) { continue; }
                    required++;
                    if (isFieldComplete(el)) { completed++; }
                    continue;
                }

                if (!isRequired) { continue; }
                required++;
                if (isFieldComplete(el)) { completed++; }
            }

            return {
                required: required,
                completed: completed,
                complete: required > 0 && completed >= required
            };
        }

        function markCopiedFields(fieldKeys) {
            for (var i = 0; i < fieldKeys.length; i++) {
                copiedFieldSources[fieldKeys[i]] = Date.now() + 3000;
            }
        }

        function detectFieldSource(el, fieldKey) {
            if (fieldKey && copiedFieldSources[fieldKey] && copiedFieldSources[fieldKey] > Date.now()) {
                return 'copied';
            }
            try {
                if (el.matches && el.matches(':-webkit-autofill')) {
                    return 'browser_autofill';
                }
            } catch (e) {}
            return 'manual';
        }

        function gusSectionKeyForTarget(target) {
            return target === 'recipient' ? 'invoice_recipient' : 'invoice_buyer';
        }

        function gusTargetForSection(sectionKey) {
            if (sectionKey === 'invoice_recipient') { return 'recipient'; }
            if (sectionKey === 'invoice_buyer') { return 'buyer'; }
            return null;
        }

        function secondsAfter(timestamp) {
            if (!timestamp) { return null; }
            return Math.max(0, Math.round((Date.now() - timestamp) / 1000));
        }

        function handleGusLookupClick(target) {
            var nipInput = target === 'recipient'
                ? document.getElementById('recipient_nip')
                : document.getElementById('buyer_nip');
            var nipDigits = nipInput && nipInput.value
                ? String(nipInput.value).replace(/\D+/g, '')
                : '';

            enqueue('gus_lookup_clicked', {
                target: target,
                section_key: gusSectionKeyForTarget(target),
                nip_present: nipDigits.length > 0,
                nip_format_valid_client: nipDigits.length === 10,
                seconds_from_page_load: secondsFromPageLoad()
            });
            scheduleLastActivity('gus_lookup_clicked', gusSectionKeyForTarget(target), null, 'gus_lookup_clicked');
        }

        function markGusAppliedFields(target, fieldKeys) {
            for (var i = 0; i < fieldKeys.length; i++) {
                gusAppliedFieldKeys[fieldKeys[i]] = true;
            }
        }

        function maybeEmitGusManualFallback(target, sectionKey, fieldKey) {
            if (!target || gusFallbackStarted[target] || !gusErrorAt[target]) { return; }
            gusFallbackStarted[target] = true;
            enqueue('gus_manual_fallback_started', {
                target: target,
                section_key: sectionKey,
                first_field_key: fieldKey || undefined,
                seconds_after_gus_error: secondsAfter(gusErrorAt[target])
            });
            scheduleLastActivity('gus_manual_fallback_started', sectionKey, fieldKey, 'gus_manual_fallback_started');
        }

        function maybeEmitFieldEditedAfterGus(el, v2Key, fKey) {
            var gusTarget = gusTargetForSection(v2Key);
            if (!gusTarget || !gusAppliedFieldKeys[fKey] || fieldsEditedAfterGusSent[fKey]) {
                return false;
            }

            fieldsEditedAfterGusSent[fKey] = true;
            fieldsChangedSent[fKey] = true;

            enqueue('form_field_edited_after_gus', {
                gus_target: gusTarget,
                section_key: v2Key,
                field_key: fKey,
                field_type: fieldTypeForElement(el),
                seconds_after_gus_success: secondsAfter(gusSuccessAt[gusTarget])
            });
            scheduleLastActivity('field_changed', v2Key, fKey, 'form_field_edited_after_gus');

            return true;
        }

        function scheduleLastActivity(activityType, sectionKey, fieldKey, emittedEventName) {
            lastActivityPending = {
                last_activity_type: activityType || 'unknown',
                last_section_key: sectionKey || undefined,
                last_field_key: fieldKey || undefined,
                completed_sections_count: completedSectionsCount()
            };
            if (emittedEventName) {
                lastActivityPending.last_event_name = emittedEventName;
            }

            if (lastActivityTimer) { return; }
            lastActivityTimer = setTimeout(function () {
                lastActivityTimer = null;
                if (!lastActivityPending) { return; }
                var payload = lastActivityPending;
                lastActivityPending = null;
                enqueue('form_last_activity', payload);
            }, LAST_ACTIVITY_THROTTLE_MS);
        }

        function checkSectionCompletion(v2Key) {
            if (sectionsCompletedSent[v2Key]) { return; }
            var stats = sectionCompletion(v2Key);
            if (!stats.complete) { return; }
            sectionsCompletedSent[v2Key] = true;
            enqueue('form_section_completed', {
                section_key: v2Key,
                required_fields_count: stats.required,
                completed_fields_count: stats.completed
            });
            scheduleLastActivity('section_completed', v2Key, null, 'form_section_completed');
        }

        function checkAllSectionCompletions() {
            for (var i = 0; i < V2_SECTION_KEYS.length; i++) {
                checkSectionCompletion(V2_SECTION_KEYS[i]);
            }
        }

        function maybeEmitFieldChanged(el, event) {
            if (!el || el.disabled || gusApplying) { return; }
            if ((el.type || '').toLowerCase() === 'hidden') { return; }

            var fKey = normalizeFieldKey(el);
            if (!fKey) { return; }

            var v2Key = v2SectionKeyForElement(el);
            if (!v2Key) { return; }

            var gusTarget = gusTargetForSection(v2Key);
            if (gusTarget) {
                maybeEmitGusManualFallback(gusTarget, v2Key, fKey);
            }

            if (maybeEmitFieldEditedAfterGus(el, v2Key, fKey)) {
                checkAllSectionCompletions();
                return;
            }

            if (fieldsChangedSent[fKey]) { return; }

            fieldsChangedSent[fKey] = true;

            enqueue('form_field_changed', {
                section_key: v2Key,
                field_key: fKey,
                field_type: fieldTypeForElement(el),
                source: detectFieldSource(el, fKey),
                has_value: isFieldComplete(el),
                seconds_from_page_load: secondsFromPageLoad()
            });
            scheduleLastActivity('field_changed', v2Key, fKey, 'form_field_changed');
            checkAllSectionCompletions();
        }

        function scheduleFieldChanged(el, event) {
            var fKey = normalizeFieldKey(el);
            if (!fKey || fieldsChangedSent[fKey]) { return; }

            if (fieldChangedTimers[fKey]) { clearTimeout(fieldChangedTimers[fKey]); }
            fieldChangedTimers[fKey] = setTimeout(function () {
                fieldChangedTimers[fKey] = null;
                maybeEmitFieldChanged(el, event);
            }, FIELD_CHANGED_DEBOUNCE_MS);
        }

        function onFieldInputOrChange(e) {
            try {
                var el = e.target;
                if (!el || !form.contains(el)) { return; }

                var type = (el.type || '').toLowerCase();
                if (type === 'radio' || type === 'checkbox' || el.tagName === 'SELECT') {
                    maybeEmitFieldChanged(el, e);
                } else if (e.type === 'change') {
                    maybeEmitFieldChanged(el, e);
                } else if (e.type === 'input') {
                    scheduleFieldChanged(el, e);
                }
            } catch (err) {}
        }

        function onFieldBlur(e) {
            try {
                var el = e.target;
                if (!el || !form.contains(el)) { return; }
                maybeEmitFieldChanged(el, e);
            } catch (err) {}
        }

        function markFormVisible() {
            if (formVisibleSent) { return; }
            formVisibleSent = true;
            enqueue('form_visible', { seconds_from_page_load: secondsFromPageLoad() });
            scheduleLastActivity('form_visible', null, null, 'form_visible');
        }

        function markSectionViewed(v2Key) {
            if (!v2Key || sectionsViewedSent[v2Key]) { return; }
            sectionsViewedSent[v2Key] = true;
            enqueue('form_section_viewed', {
                section_key: v2Key,
                seconds_from_page_load: secondsFromPageLoad()
            });
            scheduleLastActivity('section_viewed', v2Key, null, 'form_section_viewed');
        }

        function markSectionStarted(v2Key, fieldKey) {
            if (!v2Key || sectionsStartedSent[v2Key]) { return; }
            sectionsStartedSent[v2Key] = true;
            enqueue('form_section_started', {
                section_key: v2Key,
                first_field_key: fieldKey || undefined,
                trigger: 'first_interaction'
            });
            scheduleLastActivity('section_started', v2Key, fieldKey, 'form_section_started');
        }

        function markFirstInteraction(type, v2Key, fieldKey) {
            if (firstInteractionSent) { return; }
            firstInteractionSent = true;

            var payload = {
                first_interaction_type: type || 'unknown',
                seconds_from_page_load: secondsFromPageLoad(),
                trigger: 'first_interaction'
            };
            if (v2Key) { payload.first_section_key = v2Key; }
            if (fieldKey) { payload.first_field_key = fieldKey; }

            enqueue('form_first_interaction', payload);
            scheduleLastActivity('first_interaction', v2Key, fieldKey, 'form_first_interaction');
            legacyMarkStarted('first_interaction');
        }

        function legacyMarkStarted(trigger) {
            if (legacyStartedSent) { return; }
            legacyStartedSent = true;
            enqueue('order_form_started', { trigger: trigger || 'first_interaction' });
        }

        function legacyHandleSection(target) {
            try {
                if (!target || !target.closest) { return; }
                var el = target.closest('[data-analytics-section]');
                if (!el) { return; }
                var key = el.getAttribute('data-analytics-section');
                if (LEGACY_SECTION_KEYS.indexOf(key) === -1) { return; }
                if (legacySectionsSent[key]) { return; }
                legacySectionsSent[key] = true;
                enqueue('order_form_section_interacted', { section_key: key, trigger: 'section_click' });
            } catch (e) {}
        }

        function legacyHandleCta(target) {
            try {
                if (!target || !target.closest) { return; }
                var el = target.closest('[data-analytics-cta]');
                if (!el) { return; }
                var key = el.getAttribute('data-analytics-cta');
                if (CTA_KEYS.indexOf(key) === -1) { return; }
                enqueue('order_form_cta_clicked', { cta_key: key, trigger: 'cta_click' });
            } catch (e) {}
        }

        function interactionType(event) {
            if (!event || !event.type) { return 'unknown'; }
            if (event.type === 'focusin' || event.type === 'focus') { return 'focus'; }
            if (event.type === 'click') { return 'click'; }
            if (event.type === 'change') { return 'change'; }
            if (event.type === 'input') { return 'input'; }
            return 'unknown';
        }

        function onInteraction(e) {
            try {
                var target = e.target;
                if (!target || !form.contains(target)) { return; }

                var v2Key = v2SectionKeyForElement(target);
                var fKey = fieldKeyForElement(target);
                var iType = interactionType(e);

                if (!firstInteractionSent && (iType === 'focus' || iType === 'click' || iType === 'change' || iType === 'input')) {
                    markFirstInteraction(iType, v2Key, fKey);
                }

                if (v2Key) {
                    markSectionStarted(v2Key, fKey);
                }

                legacyMarkStarted('first_interaction');
                legacyHandleSection(target);
                if (e.type === 'click' || e.type === 'change') { legacyHandleCta(target); }

                if (e.type === 'change' && target.id === 'participant_copy_from_contact' && target.checked) {
                    markCopiedFields(['participant_first_name', 'participant_last_name', 'participant_email']);
                }
                if (e.type === 'change' && target.id === 'buyer_person_name_independent' && target.checked) {
                    markCopiedFields(['contact_name']);
                }
            } catch (err) {}
        }

        function selectedPaymentMethod() {
            var selected = form.querySelector('input[name="payment_type"]:checked');
            if (!selected) { return null; }
            var val = (selected.value || '').toLowerCase();
            if (val === 'online') { return 'online_payment'; }
            if (val === 'deferred') { return 'deferred_invoice'; }
            return val || null;
        }

        function collectValidationErrors() {
            var errorSections = [];
            var errorFields = [];
            var errorCodes = [];
            var firstSection = null;
            var firstField = null;
            var fields = form.querySelectorAll('input, select, textarea');

            for (var i = 0; i < fields.length; i++) {
                var el = fields[i];
                if (!isVisible(el) || el.disabled) { continue; }
                if (el.checkValidity()) { continue; }

                var section = v2SectionKeyForElement(el);
                var fKey = fieldKeyForElement(el);
                var code = 'invalid';

                if (el.validity) {
                    if (el.validity.valueMissing) { code = 'required'; }
                    else if (el.validity.typeMismatch) { code = 'type_mismatch'; }
                    else if (el.validity.patternMismatch) { code = 'pattern_mismatch'; }
                    else if (el.validity.tooShort) { code = 'too_short'; }
                    else if (el.validity.tooLong) { code = 'too_long'; }
                    else if (el.validity.rangeUnderflow) { code = 'range_underflow'; }
                    else if (el.validity.rangeOverflow) { code = 'range_overflow'; }
                }

                if (section && errorSections.indexOf(section) === -1) { errorSections.push(section); }
                if (fKey && errorFields.indexOf(fKey) === -1) { errorFields.push(fKey); }
                if (errorCodes.indexOf(code) === -1) { errorCodes.push(code); }
                if (!firstSection && section) { firstSection = section; }
                if (!firstField && fKey) { firstField = fKey; }
            }

            return {
                errors_count: errorFields.length > 0 ? errorFields.length : errorSections.length,
                error_sections: errorSections,
                error_fields: errorFields,
                first_error_section: firstSection,
                first_error_field: firstField,
                validation_error_codes: errorCodes
            };
        }

        function visibleValidationErrorsCount() {
            return form.querySelectorAll('.is-invalid, :invalid').length;
        }

        function onSubmitClick(e) {
            try {
                var btn = e.target && e.target.closest ? e.target.closest('#order-form-submit-btn,[data-analytics-cta="submit_order"]') : null;
                if (!btn) { return; }

                markFirstInteraction('click', 'submit', null);
                markSectionStarted('submit', null);

                enqueue('form_submit_clicked', {
                    completed_sections_count: completedSectionsCount(),
                    visible_validation_errors_count: visibleValidationErrorsCount(),
                    selected_payment_method: selectedPaymentMethod(),
                    seconds_from_page_load: secondsFromPageLoad(),
                    trigger: 'cta_click'
                });
                scheduleLastActivity('form_submit_clicked', 'submit', null, 'form_submit_clicked');

                if (!form.checkValidity()) {
                    var validation = collectValidationErrors();
                    if (validation.errors_count > 0) {
                        enqueue('client_validation_failed', validation);
                        scheduleLastActivity('client_validation_failed', validation.first_error_section, validation.first_error_field, 'client_validation_failed');
                    }
                }

                send(true);
            } catch (err) {}
        }

        form.addEventListener('focusin', onInteraction, true);
        form.addEventListener('input', onFieldInputOrChange, true);
        form.addEventListener('change', function (e) {
            onFieldInputOrChange(e);
            onInteraction(e);
        }, true);
        form.addEventListener('blur', onFieldBlur, true);
        form.addEventListener('click', function (e) {
            try {
                var gusBtn = e.target && e.target.closest ? e.target.closest('[data-gus-target]') : null;
                if (gusBtn) {
                    var gusTarget = gusBtn.getAttribute('data-gus-target');
                    if (gusTarget === 'buyer' || gusTarget === 'recipient') {
                        handleGusLookupClick(gusTarget);
                    }
                }
            } catch (err) {}
        }, true);
        form.addEventListener('click', onInteraction, true);
        form.addEventListener('click', onSubmitClick, true);

        form.addEventListener('submit', function () {
            try {
                legacyMarkStarted('first_interaction');
                enqueue('order_form_submit_clicked', { trigger: 'cta_click' });
                send(true);
            } catch (e) {}
        }, true);

        if (typeof IntersectionObserver === 'function') {
            try {
                var formObserver = new IntersectionObserver(function (entries) {
                    for (var i = 0; i < entries.length; i++) {
                        if (entries[i].isIntersecting) { markFormVisible(); }
                    }
                }, { threshold: 0.15 });
                formObserver.observe(form);

                for (var s = 0; s < V2_SECTION_KEYS.length; s++) {
                    (function (key) {
                        var root = sectionRoot(key);
                        if (!root) { return; }
                        var sectionObserver = new IntersectionObserver(function (entries) {
                            for (var j = 0; j < entries.length; j++) {
                                if (entries[j].isIntersecting) { markSectionViewed(key); }
                            }
                        }, { threshold: 0.2 });
                        sectionObserver.observe(root);
                    })(V2_SECTION_KEYS[s]);
                }
            } catch (e) {}
        } else {
            markFormVisible();
        }

        window.pneOrderFormAnalytics = {
            onGusSuccess: function (payload) {
                try {
                    var target = payload && payload.target;
                    if (target !== 'buyer' && target !== 'recipient') { return; }
                    gusSuccessAt[target] = Date.now();
                    gusErrorAt[target] = null;
                    gusFallbackStarted[target] = false;
                } catch (e) {}
            },
            onGusError: function (payload) {
                try {
                    var target = payload && payload.target;
                    if (target !== 'buyer' && target !== 'recipient') { return; }
                    gusErrorAt[target] = Date.now();
                } catch (e) {}
            },
            onGusDataApplied: function (payload) {
                try {
                    gusApplying = true;
                    var target = payload && payload.target;
                    if (target !== 'buyer' && target !== 'recipient') { return; }

                    if (Array.isArray(payload.field_keys)) {
                        markGusAppliedFields(payload.field_keys);
                    }

                    enqueue('gus_data_applied', {
                        target: target,
                        section_key: gusSectionKeyForTarget(target),
                        fields_applied_count: Math.max(0, parseInt(payload.fields_applied_count || 0, 10)),
                        overwritten_manual_fields_count: Math.max(0, parseInt(payload.overwritten_manual_fields_count || 0, 10)),
                        seconds_after_gus_success: secondsAfter(gusSuccessAt[target])
                    });
                    scheduleLastActivity('gus_data_applied', gusSectionKeyForTarget(target), null, 'gus_data_applied');
                } catch (e) {} finally {
                    gusApplying = false;
                }
            }
        };

        document.addEventListener('visibilitychange', function () {
            try { if (document.visibilityState === 'hidden') { send(true); } } catch (e) {}
        });
        window.addEventListener('pagehide', function () { try { send(true); } catch (e) {} });
    } catch (e) {}
})();
</script>
