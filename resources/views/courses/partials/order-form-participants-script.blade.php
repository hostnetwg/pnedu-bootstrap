<script>
/**
 * Wielu uczestników na formularzu zamówienia: dodawanie/usuwanie, cena × N, walidacja e-maila.
 * Wymaga: #order-form-participants-root, radio/select buyer_type lub #v2-buyer-type.
 */
(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var root = document.getElementById('order-form-participants-root');
        if (!root) {
            return;
        }

        var rowsWrap = document.getElementById('order-form-participant-rows');
        var addBtn = document.getElementById('order-form-add-participant');
        var template = document.getElementById('order-form-participant-row-template');
        var totalEl = document.getElementById('order-form-total-price');
        var breakdownEl = document.getElementById('order-form-price-breakdown');
        var max = parseInt(root.dataset.maxParticipants || '50', 10) || 50;
        var unitPrice = parseFloat(root.dataset.unitPrice || '');
        var availabilityUrl = root.dataset.emailAvailabilityUrl || '';
        var exceptIdent = root.dataset.exceptIdent || '';

        function buyerType() {
            var v2 = document.getElementById('v2-buyer-type');
            if (v2 && v2.value) {
                return v2.value;
            }
            var checked = document.querySelector('input[name="buyer_type"]:checked');
            return checked ? checked.value : 'organisation';
        }

        function allowsMultiple() {
            return buyerType() === 'organisation';
        }

        function rows() {
            return Array.prototype.slice.call(rowsWrap.querySelectorAll('.order-form-participant-row'));
        }

        function reindex() {
            rows().forEach(function (row, index) {
                row.dataset.participantIndex = String(index);
                var num = row.querySelector('.js-participant-number');
                if (num) {
                    num.textContent = String(index + 1);
                }
                row.querySelectorAll('input').forEach(function (input) {
                    var name = input.getAttribute('name') || '';
                    name = name.replace(/participants\[\d+]/, 'participants[' + index + ']');
                    name = name.replace(/participants\[__INDEX__\]/, 'participants[' + index + ']');
                    input.setAttribute('name', name);
                    var id = input.getAttribute('id') || '';
                    id = id.replace(/_\d+$/, '_' + index).replace(/___INDEX__$/, '_' + index);
                    if (/participant_(first_name|last_name|email)/.test(id) || id.indexOf('__INDEX__') >= 0) {
                        input.id = id.replace('__INDEX__', String(index));
                    }
                    var label = row.querySelector('label[for="' + (input.getAttribute('id') || '') + '"]');
                    // labels updated via for= matching new id — rewrite for attrs:
                });
                row.querySelectorAll('label[for]').forEach(function (label) {
                    var f = label.getAttribute('for') || '';
                    f = f.replace(/_\d+$/, '_' + index).replace(/___INDEX__$/, '_' + index);
                    label.setAttribute('for', f.replace('__INDEX__', String(index)));
                });
                var removeBtn = row.querySelector('.js-remove-participant');
                if (removeBtn) {
                    removeBtn.hidden = index === 0;
                }
                // Primary field aliases for copy-from-contact JS (legacy / v2)
                row.querySelectorAll('[data-primary-field]').forEach(function (el) {
                    if (index === 0) {
                        var kind = el.getAttribute('data-primary-field');
                        el.id = 'participant_' + kind;
                    }
                });
                if (index === 0) {
                    ensureHiddenPrimaryMirrors(row);
                }
            });
            updateAddButton();
            recalculatePrice();
            syncPrimaryHiddenFields();
        }

        function ensureHiddenPrimaryMirrors(firstRow) {
            // Legacy JS often reads #participant_first_name — keep ids on first row inputs.
            ['first_name', 'last_name', 'email'].forEach(function (kind) {
                var input = firstRow.querySelector('[data-primary-field="' + kind + '"]');
                if (input) {
                    input.id = 'participant_' + kind;
                }
            });
        }

        function syncPrimaryHiddenFields() {
            // Also keep classic names for backends that look at participant_* (compat).
            var form = root.closest('form');
            if (!form) {
                return;
            }
            ['first_name', 'last_name', 'email'].forEach(function (kind) {
                var src = document.getElementById('participant_' + kind);
                if (!src) {
                    return;
                }
                var hiddenName = 'participant_' + kind;
                var hidden = form.querySelector('input[type="hidden"][name="' + hiddenName + '"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = hiddenName;
                    form.appendChild(hidden);
                }
                hidden.value = src.value || '';
            });
        }

        function updateAddButton() {
            if (!addBtn) {
                return;
            }
            var multi = allowsMultiple();
            var count = rows().length;
            addBtn.hidden = !multi || count >= max;
            // Przy przejściu na osobę prywatną — zostaw tylko pierwszego
            if (!multi && count > 1) {
                rows().slice(1).forEach(function (r) { r.remove(); });
                reindex();
            }
        }

        function formatPln(n) {
            return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        function recalculatePrice() {
            if (!totalEl || isNaN(unitPrice)) {
                return;
            }
            var count = Math.max(1, rows().length);
            var total = Math.round(unitPrice * count * 100) / 100;
            totalEl.textContent = formatPln(total);
            if (breakdownEl) {
                breakdownEl.textContent = count > 1
                    ? (formatPln(unitPrice) + ' PLN × ' + count + ' os.')
                    : '';
            }
            try {
                if (window.pneduAnalytics && typeof window.pneduAnalytics.track === 'function') {
                    window.pneduAnalytics.track('price_recalculated', {
                        participant_count: count,
                        amount_snapshot: total,
                    });
                }
            } catch (e) {}
        }

        function addRow() {
            if (!allowsMultiple() || rows().length >= max || !template) {
                return;
            }
            var html = template.innerHTML.replace(/__INDEX__/g, String(rows().length));
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            var node = wrap.firstElementChild;
            rowsWrap.appendChild(node);
            bindRowEvents(node);
            reindex();
            try {
                if (window.pneduAnalytics && typeof window.pneduAnalytics.track === 'function') {
                    window.pneduAnalytics.track('participant_added', { participant_count: rows().length });
                }
            } catch (e) {}
            var emailInput = node.querySelector('.js-participant-email');
            if (emailInput) {
                emailInput.focus();
            }
        }

        function bindRowEvents(row) {
            var removeBtn = row.querySelector('.js-remove-participant');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    if (rows().length <= 1) {
                        return;
                    }
                    row.remove();
                    reindex();
                    try {
                        if (window.pneduAnalytics && typeof window.pneduAnalytics.track === 'function') {
                            window.pneduAnalytics.track('participant_removed', { participant_count: rows().length });
                        }
                    } catch (e) {}
                });
            }
            var emailInput = row.querySelector('.js-participant-email');
            if (emailInput) {
                var timer = null;
                emailInput.addEventListener('blur', function () {
                    checkEmail(emailInput);
                });
                emailInput.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () { checkEmail(emailInput); }, 500);
                    syncPrimaryHiddenFields();
                });
            }
            row.querySelectorAll('input').forEach(function (input) {
                input.addEventListener('input', syncPrimaryHiddenFields);
            });
        }

        function checkEmail(input) {
            var email = (input.value || '').trim().toLowerCase();
            var feedback = input.parentElement.querySelector('.js-email-feedback');
            if (!availabilityUrl || email === '' || email.indexOf('@') < 0) {
                input.classList.remove('is-invalid');
                if (feedback) {
                    feedback.textContent = '';
                }
                return;
            }
            // Duplikat w formularzu
            var dup = false;
            rows().forEach(function (row) {
                var other = row.querySelector('.js-participant-email');
                if (other && other !== input && (other.value || '').trim().toLowerCase() === email) {
                    dup = true;
                }
            });
            if (dup) {
                input.classList.add('is-invalid');
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.textContent = 'Ten sam adres e-mail nie może powtórzyć się na zamówieniu.';
                }
                return;
            }
            var url = availabilityUrl + '?email=' + encodeURIComponent(email);
            if (exceptIdent) {
                url += '&except_ident=' + encodeURIComponent(exceptIdent);
            }
            fetch(url, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.available === false) {
                        input.classList.add('is-invalid');
                        if (feedback) {
                            feedback.style.display = 'block';
                            feedback.textContent = data.message || 'Ten e-mail jest już użyty na tym szkoleniu.';
                        }
                    } else {
                        input.classList.remove('is-invalid');
                        if (feedback) {
                            feedback.textContent = '';
                        }
                    }
                })
                .catch(function () {});
        }

        if (addBtn) {
            addBtn.addEventListener('click', addRow);
        }

        document.querySelectorAll('input[name="buyer_type"]').forEach(function (radio) {
            radio.addEventListener('change', updateAddButton);
        });

        // V2 profile changes update #v2-buyer-type
        var observerTarget = document.getElementById('v2-buyer-type');
        if (observerTarget) {
            var obs = new MutationObserver(updateAddButton);
            obs.observe(observerTarget, { attributes: true, attributeFilter: ['value'] });
            observerTarget.addEventListener('change', updateAddButton);
        }

        rows().forEach(bindRowEvents);
        reindex();

        // Po zmianie profilu V2 (syncProfile ustawia value)
        document.querySelectorAll('input[name="customer_profile"]').forEach(function (el) {
            el.addEventListener('change', function () {
                setTimeout(updateAddButton, 0);
            });
        });

        var form = root.closest('form');
        if (form) {
            form.addEventListener('submit', syncPrimaryHiddenFields);
        }

        window.orderFormParticipantsRecalc = recalculatePrice;
        window.orderFormParticipantsUpdateBuyer = updateAddButton;
    });
})();

</script>
