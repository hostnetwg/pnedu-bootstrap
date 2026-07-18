<script>
(function () {
    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function formatRemaining(ms) {
        if (ms <= 0) {
            return '0s';
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

    function tickLiveCountdowns() {
        var nodes = document.querySelectorAll('[data-live-countdown]');
        if (!nodes.length) {
            return;
        }

        var now = Date.now();

        nodes.forEach(function (el) {
            var targetIso = el.getAttribute('data-countdown-target');
            var valueEl = el.querySelector('.js-live-countdown-value');
            if (!targetIso || !valueEl) {
                return;
            }

            var targetMs = Date.parse(targetIso);
            if (Number.isNaN(targetMs)) {
                valueEl.textContent = '—';
                return;
            }

            var remaining = targetMs - now;
            if (remaining <= 0) {
                var phase = el.getAttribute('data-countdown-phase');
                valueEl.textContent = phase === 'until_start' ? 'Trwa lub właśnie się zaczęło' : 'Zakończone';
                return;
            }

            valueEl.textContent = formatRemaining(remaining);
        });
    }

    function disposeJoinTooltip(wrap) {
        if (!wrap || typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            return;
        }

        var instance = bootstrap.Tooltip.getInstance(wrap);
        if (instance) {
            instance.dispose();
        }

        wrap.removeAttribute('data-bs-toggle');
        wrap.removeAttribute('data-bs-placement');
        wrap.removeAttribute('title');
        wrap.removeAttribute('data-bs-original-title');
        wrap.removeAttribute('aria-describedby');
        wrap.removeAttribute('tabindex');
        wrap.removeAttribute('data-live-join-tooltip-wrap');
    }

    function unlockJoinButton(btn) {
        if (!btn || btn.getAttribute('data-join-unlocked') === '1') {
            return;
        }

        var url = btn.getAttribute('data-join-url');
        if (!url) {
            return;
        }

        btn.setAttribute('href', url);
        btn.setAttribute('target', '_blank');
        btn.setAttribute('rel', 'noopener noreferrer');
        btn.removeAttribute('role');
        btn.removeAttribute('aria-disabled');
        btn.removeAttribute('tabindex');
        btn.classList.remove('disabled', 'pe-none');
        btn.setAttribute('data-join-unlocked', '1');

        disposeJoinTooltip(btn.parentElement);
    }

    function tickLiveJoinButtons() {
        var buttons = document.querySelectorAll('[data-live-join-btn]');
        if (!buttons.length) {
            return;
        }

        var now = Date.now();

        buttons.forEach(function (btn) {
            if (btn.getAttribute('data-join-unlocked') === '1') {
                return;
            }

            var unlockIso = btn.getAttribute('data-join-unlock-at');
            if (!unlockIso) {
                return;
            }

            var unlockMs = Date.parse(unlockIso);
            if (Number.isNaN(unlockMs)) {
                return;
            }

            if (now >= unlockMs) {
                unlockJoinButton(btn);
            }
        });
    }

    function initJoinTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            return;
        }

        document.querySelectorAll('[data-live-join-tooltip-wrap]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });
    }

    function tickAll() {
        tickLiveCountdowns();
        tickLiveJoinButtons();
    }

    initJoinTooltips();
    tickAll();
    setInterval(tickAll, 1000);
})();
</script>
