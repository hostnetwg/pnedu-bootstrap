@extends('layouts.transmisja-bare')

@section('title', 'Transmisja: ' . $courseTitle . ' - Platforma Nowoczesnej Edukacji')

@section('content')
@php
    $bareLayout = ! empty($bareLayout);
@endphp
<div class="transmisja-page transmisja-page--bare">
    <div id="cm-embed-shell" class="transmisja-shell">
        {{-- Belka PNE — tylko w trybie pełnoekranowym --}}
        <div id="cm-transmisja-brand-bar" class="cm-transmisja-brand-bar">
            @include('dashboard.partials.transmisja-pne-brand')
            <div class="cm-transmisja-brand-bar__actions">
            <button type="button"
                    class="btn btn-sm btn-light flex-shrink-0"
                    id="cm-fullscreen-exit-btn"
                    title="Wyjdź z pełnego ekranu (pokój zostaje otwarty)"
                    aria-label="Wyjdź z pełnego ekranu">
                <i class="bi bi-fullscreen-exit me-1" aria-hidden="true"></i>
                Wyjdź z pełnego ekranu
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline-light flex-shrink-0"
                    id="cm-fullscreen-close-btn"
                    title="Zamknij transmisję"
                    aria-label="Zamknij transmisję">
                <i class="bi bi-x-lg me-1" aria-hidden="true"></i>
                Zamknij transmisję
            </button>
            </div>
        </div>

        <div class="transmisja-toolbar" id="cm-page-toolbar">
            <div class="transmisja-toolbar__brand">
                @include('dashboard.partials.transmisja-pne-brand')
            </div>
            <div class="transmisja-toolbar__actions">
                <a href="{{ $rejoinUrl }}"
                   class="btn btn-sm btn-light transmisja-toolbar__btn"
                   title="Wejdź ponownie (gdy ClickMeeting zgłasza wykorzystany token)"
                   aria-label="Wejdź ponownie">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    <span class="transmisja-toolbar__btn-label">Wejdź ponownie</span>
                </a>
                <button type="button"
                        class="btn btn-sm btn-outline-light transmisja-toolbar__btn"
                        id="cm-fullscreen-btn"
                        title="Pełny ekran"
                        aria-label="Pełny ekran">
                    <i class="bi bi-fullscreen" aria-hidden="true"></i>
                    <span class="transmisja-toolbar__btn-label">Pełny ekran</span>
                </button>
                <button type="button"
                        class="btn btn-sm btn-outline-light transmisja-toolbar__btn"
                        id="cm-close-transmission-btn"
                        title="Zamknij transmisję"
                        aria-label="Zamknij transmisję">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                    <span class="transmisja-toolbar__btn-label">Zamknij transmisję</span>
                </button>
            </div>
        </div>

        <div class="cm-embed-body">
            <div id="cm-embed-container">
                @if ($iframeSrc)
                    <iframe
                        id="cm-embed-frame"
                        src="{{ $iframeSrc }}"
                        title="Pokój ClickMeeting"
                        allow="microphone; camera; display-capture; fullscreen; autoplay; encrypted-media"
                        allowfullscreen
                        referrerpolicy="no-referrer"
                    ></iframe>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-white-50 p-4">
                        Nie udało się zbudować adresu osadzonego pokoju.
                        @if ($roomAutologinUrl)
                            <a class="ms-2" href="{{ $roomAutologinUrl }}" target="_blank" rel="noopener noreferrer">Otwórz w ClickMeeting</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Potwierdzenie zamknięcia transmisji (nie mylić z wyjściem z pełnego ekranu) --}}
<div class="modal fade" id="cmCloseTransmissionModal" tabindex="-1" aria-labelledby="cmCloseTransmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-5" id="cmCloseTransmissionModalLabel">Zamknąć transmisję?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Anuluj"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">Zamkniesz pokój na tym urządzeniu i przejdziesz do strony z podziękowaniem (nagranie, materiały, zaświadczenie). Możesz wejść ponownie przez „Dołącz do spotkania na żywo”, jeśli spotkanie jeszcze trwa.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Wróć do pokoju</button>
                <button type="button" class="btn btn-danger" id="cmCloseTransmissionConfirm">Zamknij transmisję</button>
            </div>
        </div>
    </div>
</div>

<style>
    .transmisja-page {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 56px);
        max-height: calc(100dvh - 56px);
        background: #111;
        overflow: hidden;
    }
    .transmisja-page--bare {
        height: 100vh;
        max-height: 100dvh;
    }
    .transmisja-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.4rem 1rem;
        background: #0b3d2e;
        color: #fff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        flex: 0 0 auto;
        overflow: hidden;
    }
    .transmisja-toolbar__brand {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
    }
    .transmisja-toolbar__actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-shrink: 0;
    }
    .transmisja-toolbar__btn {
        min-height: 2.25rem;
        padding: 0.25rem 0.65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
    }
    .transmisja-toolbar__btn-label {
        font-size: 0.8rem;
        font-weight: 500;
    }
    @media (max-width: 575.98px) {
        .transmisja-toolbar__btn-label {
            display: none;
        }
        .transmisja-toolbar__btn {
            width: 2.25rem;
            padding: 0;
        }
    }
    .transmisja-shell {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        background: #111;
    }
    .cm-embed-body {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }
    #cm-embed-container {
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
        background: #111;
    }
    #cm-embed-container iframe {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
    }
    .pne-transmisja-brand {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        column-gap: 0.65rem;
        row-gap: 0.15rem;
        min-width: 0;
        overflow: hidden;
    }
    .pne-transmisja-brand__name {
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 700;
        letter-spacing: 0.01em;
        line-height: 1.15;
        text-align: left;
    }
    .pne-transmisja-brand__sep {
        flex-shrink: 0;
        width: 1px;
        height: 1.15em;
        align-self: center;
    }
    .pne-transmisja-brand__site-link {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-weight: 500;
        letter-spacing: 0.01em;
        text-decoration: none;
        line-height: 1.15;
        transition: color 0.15s ease;
    }
    .pne-transmisja-brand__site-link:hover,
    .pne-transmisja-brand__site-link:focus-visible {
        text-decoration: underline;
        text-underline-offset: 0.18em;
    }
    .pne-transmisja-brand__site-link-icon {
        font-size: 0.72em;
        opacity: 0.85;
        position: relative;
        top: -0.05em;
    }
    .transmisja-toolbar .pne-transmisja-brand__name,
    .cm-transmisja-brand-bar .pne-transmisja-brand__name {
        font-size: 1.85rem;
        color: #fff;
    }
    .transmisja-toolbar .pne-transmisja-brand__sep,
    .cm-transmisja-brand-bar .pne-transmisja-brand__sep {
        background: rgba(255, 255, 255, 0.35);
    }
    .transmisja-toolbar .pne-transmisja-brand__site-link,
    .cm-transmisja-brand-bar .pne-transmisja-brand__site-link {
        color: rgba(255, 255, 255, 0.78);
        font-size: 1rem;
    }
    .transmisja-toolbar .pne-transmisja-brand__site-link:hover,
    .transmisja-toolbar .pne-transmisja-brand__site-link:focus-visible,
    .cm-transmisja-brand-bar .pne-transmisja-brand__site-link:hover,
    .cm-transmisja-brand-bar .pne-transmisja-brand__site-link:focus-visible {
        color: #fff;
    }
    .cm-transmisja-brand-bar {
        display: none;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        column-gap: 0.75rem;
        padding: 0.4rem 1rem;
        background: #0b3d2e;
        color: #fff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        flex: 0 0 auto;
        overflow: hidden;
    }

    .cm-transmisja-brand-bar__actions {
        display: none;
        align-items: center;
        gap: 0.4rem;
        flex-shrink: 0;
    }
    @media (max-width: 767.98px) {
        .transmisja-toolbar .pne-transmisja-brand__name,
        .cm-transmisja-brand-bar .pne-transmisja-brand__name {
            font-size: 1rem;
        }
        .transmisja-toolbar .pne-transmisja-brand__site-link,
        .cm-transmisja-brand-bar .pne-transmisja-brand__site-link {
            font-size: 0.8125rem;
        }
        .transmisja-toolbar .pne-transmisja-brand__sep,
        .cm-transmisja-brand-bar .pne-transmisja-brand__sep {
            display: none;
        }
    }

    #cm-embed-shell.is-fullscreen {
        position: fixed;
        inset: 0;
        z-index: 2000;
        background: #000;
    }
    #cm-embed-shell.is-fullscreen .cm-transmisja-brand-bar {
        display: grid;
    }
    #cm-embed-shell.is-fullscreen .cm-transmisja-brand-bar__actions {
        display: flex;
    }
    /* Modal nad warstwą FS (Bootstrap domyślnie ~1055, shell ma 2000) */
    #cmCloseTransmissionModal {
        z-index: 2100;
    }
    body.cm-transmisja-fs .modal-backdrop,
    .modal-backdrop.show {
        z-index: 2090;
    }
    .transmisja-page.is-browser-fullscreen #cm-page-toolbar {
        display: none;
    }
    body.cm-transmisja-fs {
        overflow: hidden;
    }
</style>
<script>
(function () {
    const page = document.querySelector('.transmisja-page');
    const shell = document.getElementById('cm-embed-shell');
    const btn = document.getElementById('cm-fullscreen-btn');
    const exitBtn = document.getElementById('cm-fullscreen-exit-btn');

    const heartbeatUrl = @json($presenceHeartbeatUrl ?? null);
    const leaveUrl = @json($presenceLeaveUrl ?? null);
    const heartbeatMs = {{ (int) ($presenceHeartbeatMs ?? 25000) }};
    const csrfToken = @json(csrf_token());
    const thankYouUrl = @json($postTrainingThankYouUrl ?? route('post-training.thank-you'));

    function postPresence(url, keepalive) {
        if (!url) {
            return;
        }
        const body = new URLSearchParams();
        body.set('_token', csrfToken);
        if (keepalive && navigator.sendBeacon) {
            const blob = new Blob([body.toString()], { type: 'application/x-www-form-urlencoded;charset=UTF-8' });
            navigator.sendBeacon(url, blob);
            return;
        }
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
            credentials: 'same-origin',
            keepalive: !!keepalive,
        }).catch(function () {});
    }

    if (heartbeatUrl && heartbeatMs > 0) {
        setInterval(function () {
            postPresence(heartbeatUrl, false);
        }, heartbeatMs);
    }

    function releasePresence() {
        postPresence(leaveUrl, true);
    }

    window.addEventListener('pagehide', releasePresence);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            // Nie zwalniamy przy samym przełączeniu karty — tylko pagehide/nawigacja.
        }
    });

    if (!shell || !btn) return;

    const bareLayout = @json(!empty($bareLayout));
    const inHostedFrame = (function () {
        try {
            return window.self !== window.top;
        } catch (e) {
            return true;
        }
    })();
    const closeConfirmBtn = document.getElementById('cmCloseTransmissionConfirm');

    let cssFullscreen = false;

    function topHasNativeFullscreen() {
        try {
            return !!(window.top && window.top.document && window.top.document.fullscreenElement);
        } catch (e) {
            return !!document.fullscreenElement;
        }
    }

    function requestHostNativeFullscreen() {
        try {
            const host = window.top && window.top.document
                ? window.top.document.getElementById('cm-fs-host')
                : null;
            if (host && host.requestFullscreen) {
                return host.requestFullscreen();
            }
        } catch (e) {}
        if (shell.requestFullscreen) {
            return shell.requestFullscreen();
        }
        return Promise.reject(new Error('no fs'));
    }

    function setFullscreenUi(active) {
        cssFullscreen = !!active;
        shell.classList.toggle('is-fullscreen', cssFullscreen);
        if (page) {
            page.classList.toggle('is-browser-fullscreen', cssFullscreen);
        }
        document.body.classList.toggle('cm-transmisja-fs', cssFullscreen);
        const icon = btn.querySelector('i');
        const label = btn.querySelector('.transmisja-toolbar__btn-label');
        if (icon) {
            icon.className = cssFullscreen ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
        }
        const text = cssFullscreen ? 'Wyjdź z pełnego ekranu' : 'Pełny ekran';
        if (label) {
            label.textContent = cssFullscreen ? 'Wyjdź z pełnego ekranu' : 'Pełny ekran';
        }
        btn.title = text;
        btn.setAttribute('aria-label', text);
    }

    function syncHostedFullscreenUi() {
        if (!(bareLayout || inHostedFrame)) {
            return;
        }
        // Natywny FS → pasek PNE; po Esc zostajemy w pokoju z paskiem narzędzi.
        setFullscreenUi(topHasNativeFullscreen());
    }

    function exitNativeFullscreenOnly() {
        try {
            if (window.top && window.top.document && window.top.document.fullscreenElement) {
                window.top.document.exitFullscreen().catch(function () {});
                return;
            }
        } catch (e) {}
        if (document.fullscreenElement) {
            document.exitFullscreen().catch(function () {});
        }
        setFullscreenUi(false);
    }

    function enterFullscreen() {
        if (bareLayout || inHostedFrame) {
            requestHostNativeFullscreen().then(function () {
                setFullscreenUi(true);
            }).catch(function () {
                setFullscreenUi(false);
            });
            return;
        }
        setFullscreenUi(true);
        if (shell.requestFullscreen && !document.fullscreenElement) {
            shell.requestFullscreen().catch(function () {});
        }
    }

    function exitFullscreen() {
        exitNativeFullscreenOnly();
    }

    function toggleFullscreen() {
        if (topHasNativeFullscreen() || document.fullscreenElement || cssFullscreen) {
            exitFullscreen();
            return;
        }
        enterFullscreen();
    }

    function openCloseTransmissionModal() {
        const modalEl = document.getElementById('cmCloseTransmissionModal');
        if (!modalEl || typeof bootstrap === 'undefined') {
            closeTransmission();
            return;
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function goToThankYouPage() {
        const target = thankYouUrl || @json(route('post-training.thank-you'));
        try {
            if (window.top && window.top !== window.self) {
                window.top.location.href = target;
                return;
            }
        } catch (e) {}
        window.location.href = target;
    }

    function closeTransmission() {
        releasePresence();
        try {
            if (window.top && window.top !== window.self) {
                window.top.postMessage({
                    type: 'cm-embed-close',
                    thankYouUrl: thankYouUrl,
                }, window.location.origin);
                return;
            }
        } catch (e) {}
        goToThankYouPage();
    }

    // Gdy CM przekieruje iframe na /po-szkoleniu (same-origin) — wyjdź z iframe do pełnej strony.
    const cmFrame = document.getElementById('cm-embed-frame');
    if (cmFrame) {
        cmFrame.addEventListener('load', function () {
            try {
                const href = cmFrame.contentWindow.location.href;
                if (typeof href === 'string' && href.indexOf('/po-szkoleniu') !== -1) {
                    releasePresence();
                    goToThankYouPage();
                }
            } catch (e) {
                // Cross-origin (nadal CM) — brak dostępu do location; to normalne.
            }
        });
    }

    // Auto: prezenter zakończył wydarzenie (CM status=inactive) → podziękowanie.
    const meetingStatusUrl = @json($meetingStatusUrl ?? null);
    let meetingEndedHandled = false;
    function pollMeetingEnded() {
        if (!meetingStatusUrl || meetingEndedHandled) {
            return;
        }
        fetch(meetingStatusUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).then(function (res) {
            if (!res.ok) {
                return null;
            }
            return res.json();
        }).then(function (data) {
            if (!data || !data.ended) {
                return;
            }
            meetingEndedHandled = true;
            if (typeof data.thank_you_url === 'string' && data.thank_you_url !== '') {
                // Preferuj URL z API (zawsze z course=).
                try {
                    if (window.top && window.top !== window.self) {
                        window.top.postMessage({
                            type: 'cm-embed-close',
                            thankYouUrl: data.thank_you_url,
                        }, window.location.origin);
                        releasePresence();
                        return;
                    }
                } catch (e) {}
                releasePresence();
                window.location.href = data.thank_you_url;
                return;
            }
            closeTransmission();
        }).catch(function () {});
    }
    if (meetingStatusUrl) {
        setTimeout(pollMeetingEnded, 8000);
        setInterval(pollMeetingEnded, 12000);
    }

    btn.addEventListener('click', toggleFullscreen);
    if (exitBtn) {
        exitBtn.addEventListener('click', function () {
            exitNativeFullscreenOnly();
        });
    }

    const toolbarCloseBtn = document.getElementById('cm-close-transmission-btn');
    const barCloseBtn = document.getElementById('cm-fullscreen-close-btn');
    if (toolbarCloseBtn) {
        toolbarCloseBtn.addEventListener('click', openCloseTransmissionModal);
    }
    if (barCloseBtn) {
        barCloseBtn.addEventListener('click', openCloseTransmissionModal);
    }

    if (closeConfirmBtn) {
        closeConfirmBtn.addEventListener('click', function () {
            const modalEl = document.getElementById('cmCloseTransmissionModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) {
                    inst.hide();
                }
            }
            closeTransmission();
        });
    }

    document.addEventListener('fullscreenchange', function () {
        if (document.fullscreenElement === shell) {
            setFullscreenUi(true);
        } else if (!document.fullscreenElement && cssFullscreen && !bareLayout && !inHostedFrame) {
            setFullscreenUi(false);
        }
    });

    if (bareLayout || inHostedFrame) {
        syncHostedFullscreenUi();
        try {
            window.top.document.addEventListener('fullscreenchange', syncHostedFullscreenUi);
        } catch (e) {}
        // Start: host zwykle już jest / będzie w FS — pokaż pasek PNE gdy FS aktywny.
        if (topHasNativeFullscreen()) {
            setFullscreenUi(true);
        }
        return;
    }

    // Wejście top-level z ?fullscreen=1 — CSS + bramka na natywny FS.
    const params = new URLSearchParams(window.location.search);
    const wantFs = params.get('fullscreen') === '1'
        || sessionStorage.getItem('cm_embed_autofullscreen') === '1';
    if (wantFs) {
        try {
            sessionStorage.removeItem('cm_embed_autofullscreen');
        } catch (e) {}
        enterFullscreen();
        try {
            params.delete('fullscreen');
            const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', clean);
        } catch (e) {}

        if (!document.fullscreenElement) {
            showNativeFullscreenGate();
        }
    }

    function showNativeFullscreenGate() {
        if (document.getElementById('cmNativeFsGateModal')) {
            return;
        }
        const wrap = document.createElement('div');
        wrap.innerHTML = ''
            + '<div class="modal fade" id="cmNativeFsGateModal" tabindex="-1" aria-labelledby="cmNativeFsGateModalLabel" aria-hidden="true" data-bs-backdrop="static">'
            + '  <div class="modal-dialog modal-dialog-centered">'
            + '    <div class="modal-content">'
            + '      <div class="modal-header border-0 pb-0">'
            + '        <h2 class="modal-title fs-5" id="cmNativeFsGateModalLabel">Pełny ekran</h2>'
            + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>'
            + '      </div>'
            + '      <div class="modal-body pt-2">'
            + '        <p class="mb-0">Aby ukryć pasek Windows i pasek przeglądarki, włącz pełny ekran. Możesz później wyjść z FS bez zamykania pokoju.</p>'
            + '      </div>'
            + '      <div class="modal-footer border-0 pt-0">'
            + '        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zostań bez pełnego ekranu</button>'
            + '        <button type="button" class="btn btn-success" id="cmNativeFsGateConfirm">'
            + '          <i class="bi bi-fullscreen me-1" aria-hidden="true"></i>Włącz pełny ekran'
            + '        </button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';
        document.body.appendChild(wrap.firstElementChild);

        const modalEl = document.getElementById('cmNativeFsGateModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let confirmed = false;

        document.getElementById('cmNativeFsGateConfirm').addEventListener('click', function () {
            confirmed = true;
            const req = shell.requestFullscreen ? shell.requestFullscreen() : Promise.reject();
            Promise.resolve(req).then(function () {
                setFullscreenUi(true);
                modal.hide();
            }).catch(function () {
                confirmed = false;
                modal.hide();
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            // Anuluj / „bez FS” — zostajemy na stronie transmisji (pokój otwarty).
            if (!confirmed) {
                setFullscreenUi(false);
            }
            modalEl.remove();
        }, { once: true });

        modal.show();
    }
})();
</script>
@endsection
