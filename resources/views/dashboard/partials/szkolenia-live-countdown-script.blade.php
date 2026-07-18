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

    tickLiveCountdowns();
    setInterval(tickLiveCountdowns, 1000);
})();
</script>
