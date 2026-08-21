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

        var url = btn.getAttribute('data-join-url') || btn.getAttribute('data-embed-url');
        if (!url) {
            return;
        }

        btn.setAttribute('href', url);
        if (btn.hasAttribute('data-live-join-btn')) {
            btn.setAttribute('target', '_blank');
            btn.setAttribute('rel', 'noopener noreferrer');
        }
        btn.removeAttribute('role');
        btn.removeAttribute('aria-disabled');
        btn.removeAttribute('tabindex');
        btn.classList.remove('disabled', 'pe-none');
        btn.setAttribute('data-join-unlocked', '1');

        disposeJoinTooltip(btn.parentElement);
    }

    function tickLiveJoinButtons() {
        var buttons = document.querySelectorAll('[data-live-join-btn], [data-live-embed-btn]');
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

    function isMobileUa() {
        return /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i.test(
            navigator.userAgent || ''
        );
    }

    function launchEmbedNativeFullscreen(url) {
        var existing = document.getElementById('cm-fs-host');
        if (existing) {
            existing.remove();
        }

        var host = document.createElement('div');
        host.id = 'cm-fs-host';
        host.setAttribute('role', 'presentation');
        host.style.cssText = 'position:fixed;inset:0;z-index:2147483646;background:#000;';

        var iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.title = 'Transmisja';
        iframe.allow = 'microphone; camera; display-capture; fullscreen; autoplay; encrypted-media';
        iframe.style.cssText = 'width:100%;height:100%;border:0;display:block;background:#000;';
        host.appendChild(iframe);
        document.body.appendChild(host);

        function cleanup() {
            window.removeEventListener('message', onMessage);
            var modalEl = document.getElementById('cmEmbedFsGateModal');
            if (modalEl) {
                try {
                    var inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) {
                        inst.hide();
                    }
                } catch (e) {}
                modalEl.remove();
            }
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(function () {});
            }
            if (host.parentNode) {
                host.remove();
            }
        }

        function onMessage(event) {
            if (event.origin !== window.location.origin) {
                return;
            }
            if (event.data && event.data.type === 'cm-embed-close') {
                cleanup();
            }
        }

        window.addEventListener('message', onMessage);

        function showFsGateModal() {
            var old = document.getElementById('cmEmbedFsGateModal');
            if (old) {
                old.remove();
            }

            var wrap = document.createElement('div');
            wrap.innerHTML = ''
                + '<div class="modal fade" id="cmEmbedFsGateModal" tabindex="-1" aria-labelledby="cmEmbedFsGateModalLabel" aria-hidden="true" data-bs-backdrop="static">'
                + '  <div class="modal-dialog modal-dialog-centered">'
                + '    <div class="modal-content">'
                + '      <div class="modal-header border-0 pb-0">'
                + '        <h2 class="modal-title fs-5" id="cmEmbedFsGateModalLabel">Pełny ekran</h2>'
                + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>'
                + '      </div>'
                + '      <div class="modal-body pt-2">'
                + '        <p class="mb-0">Aby ukryć pasek Windows i pasek przeglądarki, włącz pełny ekran. Później możesz wyjść z FS bez zamykania pokoju.</p>'
                + '      </div>'
                + '      <div class="modal-footer border-0 pt-0">'
                + '        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zostań bez pełnego ekranu</button>'
                + '        <button type="button" class="btn btn-success" id="cmEmbedFsGateConfirm">'
                + '          <i class="bi bi-fullscreen me-1" aria-hidden="true"></i>Włącz pełny ekran'
                + '        </button>'
                + '      </div>'
                + '    </div>'
                + '  </div>'
                + '</div>';
            document.body.appendChild(wrap.firstElementChild);

            var modalEl = document.getElementById('cmEmbedFsGateModal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            document.getElementById('cmEmbedFsGateConfirm').addEventListener('click', function () {
                host.requestFullscreen().then(function () {
                    modal.hide();
                }).catch(function () {
                    modal.hide();
                });
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                // Bez FS albo po włączeniu FS — host z pokojem zostaje.
                modalEl.remove();
            }, { once: true });

            modal.show();
        }

        var req = host.requestFullscreen
            ? host.requestFullscreen()
            : Promise.reject(new Error('no fs'));

        Promise.resolve(req).catch(function () {
            showFsGateModal();
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target && event.target.closest
            ? event.target.closest('[data-cm-embed-autofullscreen="1"]')
            : null;
        if (!link) {
            return;
        }

        var href = link.getAttribute('href');
        if (!href || link.getAttribute('aria-disabled') === 'true' || link.classList.contains('disabled')) {
            return;
        }

        // Mobile: zostaw zwykłą nawigację (redirect do CM).
        if (isMobileUa()) {
            try {
                sessionStorage.setItem('cm_embed_autofullscreen', '1');
            } catch (e) {}
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var url;
        try {
            url = new URL(href, window.location.origin);
        } catch (e) {
            window.location.href = href;
            return;
        }
        url.searchParams.set('bare', '1');
        url.searchParams.delete('fullscreen');

        launchEmbedNativeFullscreen(url.toString());
    }, true);
})();
</script>
