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

    function tick() {
        var now = Date.now();
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

    if (!document.querySelector('[data-promotion-countdown]')) {
        return;
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
