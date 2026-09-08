<script>
(function() {
    var forms = document.querySelectorAll('form[action*="order-form"], form[action*="deferred-order"]');

    function submittingLabel(btn) {
        return btn.getAttribute('data-submitting-text') || btn.dataset.submittingText || 'Wysyłanie…';
    }

    function showSubmittingState(btn) {
        btn.disabled = true;
        btn.dataset.originalText = (btn.dataset.originalText || btn.textContent || '').trim()
            || 'Potwierdzam zakup';
        btn.replaceChildren();

        var spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');

        var label = document.createElement('span');
        label.textContent = submittingLabel(btn);

        btn.append(spinner, label);
        btn.setAttribute('aria-busy', 'true');
    }

    function resetSubmitButtons() {
        forms.forEach(function(formEl) {
            var btn = formEl.querySelector('button[type="submit"]');
            if (!btn) {
                return;
            }
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.textContent = btn.dataset.originalText;
            }
            btn.removeAttribute('aria-busy');
        });
    }

    forms.forEach(function(formEl) {
        formEl.addEventListener('submit', function(event) {
            var btn = formEl.querySelector('button[type="submit"]');
            if (!btn) {
                return;
            }

            if (event.defaultPrevented || (typeof formEl.checkValidity === 'function' && !formEl.checkValidity())) {
                resetSubmitButtons();
                return;
            }

            if (btn.disabled) {
                return;
            }

            showSubmittingState(btn);
        });
    });

    // Po „Wstecz” przeglądarka często przywraca stronę z pamięci podręcznej (bfcache) wraz z disabled na przycisku.
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            resetSubmitButtons();
            return;
        }

        try {
            var nav = performance.getEntriesByType('navigation')[0];
            if (nav && nav.type === 'back_forward') {
                resetSubmitButtons();
            }
        } catch (e) {
            // starsze przeglądarki — brak Navigation Timing Level 2
        }
    });
})();
</script>
