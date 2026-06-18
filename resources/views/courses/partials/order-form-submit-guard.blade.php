<script>
(function() {
    var forms = document.querySelectorAll('form[action*="order-form"], form[action*="deferred-order"]');

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
        formEl.addEventListener('submit', function() {
            var btn = formEl.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) {
                return;
            }
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.textContent = btn.getAttribute('data-submitting-text') || btn.dataset.submittingText || 'Wysyłanie…';
            btn.setAttribute('aria-busy', 'true');
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
