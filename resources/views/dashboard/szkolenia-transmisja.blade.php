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
            <div class="cm-live-resource-links" data-live-resource-links hidden></div>
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
        {{-- Oferta: modal zamiast złotej belki (wewnątrz shella = widoczny też w fullscreen) --}}

        <div class="transmisja-toolbar" id="cm-page-toolbar">
            <div class="transmisja-toolbar__brand">
                @include('dashboard.partials.transmisja-pne-brand')
            </div>
            <div class="cm-live-resource-links" data-live-resource-links hidden></div>
            <div class="transmisja-toolbar__actions">
                @if(! empty($rejoinUrl))
                <a href="{{ $rejoinUrl }}"
                   class="btn btn-sm btn-light transmisja-toolbar__btn"
                   title="Wejdź ponownie (gdy ClickMeeting zgłasza wykorzystany token)"
                   aria-label="Wejdź ponownie">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    <span class="transmisja-toolbar__btn-label">Wejdź ponownie</span>
                </a>
                @endif
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

        {{-- Modal musi być wewnątrz shella — natywny Fullscreen API pokazuje tylko elementy w fullscreenElement --}}
        @include('dashboard.partials.transmisja-live-offer-modal')
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
    .cm-live-resource-links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        min-width: 0;
        flex: 1 1 auto;
        justify-content: center;
    }
    .cm-live-resource-links[hidden] {
        display: none !important;
    }
    .cm-live-resource-link {
        white-space: nowrap;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .cm-live-resource-link__icon {
        font-size: 0.95em;
        line-height: 1;
        display: inline-flex;
        transform-origin: center;
    }
    .cm-live-resource-link__icon--diploma {
        width: 1.1em;
        height: 1.1em;
        flex-shrink: 0;
    }
    .cm-live-resource-link.is-attention .cm-live-resource-link__icon {
        animation: cm-live-icon-nudge 10s ease-in-out infinite;
    }
    @keyframes cm-live-icon-nudge {
        0%, 76%, 100% { opacity: 1; transform: scale(1); }
        80% { opacity: 0.4; transform: scale(1.18); }
        84% { opacity: 1; transform: scale(1); }
        88% { opacity: 0.4; transform: scale(1.18); }
        92% { opacity: 1; transform: scale(1); }
    }
    @media (prefers-reduced-motion: reduce) {
        .cm-live-resource-link.is-attention .cm-live-resource-link__icon {
            animation: none;
        }
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
        grid-template-columns: minmax(0, auto) minmax(0, 1fr) auto;
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
    /* Modal + backdrop: w FS muszą być WEWNĄTRZ shella i modal NAD backdropem.
       Oferta: BEZ przyciemnienia — tło (czat CM) zostaje klikalne. */
    #cm-embed-shell {
        position: relative;
    }
    #cm-embed-shell > .modal-backdrop {
        position: absolute !important;
        inset: 0 !important;
        width: auto !important;
        height: auto !important;
        z-index: 1 !important;
    }
    #cm-embed-shell > .modal {
        position: absolute !important;
        inset: 0 !important;
        width: auto !important;
        height: auto !important;
        z-index: 2 !important;
        pointer-events: none;
    }
    #cm-embed-shell > .modal .modal-dialog {
        pointer-events: auto;
    }
    /* Oferta: przezroczysta warstwa, kliknięcia poza kartą idą do iframe/czatu. */
    #cmLiveOfferModal,
    #cm-embed-shell > #cmLiveOfferModal,
    body > #cmLiveOfferModal {
        pointer-events: none !important;
        background: transparent !important;
    }
    #cmLiveOfferModal .modal-dialog,
    #cmLiveOfferModal .cm-live-offer-modal__dialog {
        pointer-events: auto !important;
        /* Lekko w lewo — więcej miejsca na czat CM po prawej. */
        margin-left: clamp(0.75rem, 5vw, 2.5rem);
        margin-right: clamp(0.75rem, 22vw, 18rem);
        max-width: min(32rem, calc(100vw - 2rem));
    }
    #cmLiveOfferModal .modal-content {
        box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.35);
    }
    /* Poza shellem — standard Bootstrap dla innych modali (np. zamknięcie transmisji). */
    body > .modal-backdrop {
        z-index: 1050;
    }
    body > .modal {
        z-index: 1055;
    }
    .transmisja-page.is-browser-fullscreen #cm-page-toolbar {
        display: none;
    }
    .cm-live-offer-modal__image-wrap {
        overflow: hidden;
        border-radius: 0.5rem;
        background: #f1f3f5;
        text-align: center;
    }
    .cm-live-offer-modal__image {
        display: block;
        width: 100%;
        height: auto;
        max-height: min(52vh, 420px);
        object-fit: contain;
        margin: 0 auto;
    }
    .cm-live-offer-modal__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0;
    }
    .cm-live-offer-modal__description {
        white-space: pre-wrap;
        line-height: 1.45;
    }
    .cm-live-offer-modal__price-current {
        font-size: 1.15rem;
    }
    .cm-live-offer-modal__price-current.is-promo {
        color: #b02a37;
    }
    @media (min-width: 576px) {
        .w-sm-auto {
            width: auto !important;
        }
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

    function beat() {
        postPresence(heartbeatUrl, false);
    }

    function resumeLiveSignals() {
        beat();
        if (typeof pollMeetingEnded === 'function') {
            pollMeetingEnded();
        }
        if (typeof startMeetingStatusPoll === 'function') {
            startMeetingStatusPoll();
        }
    }

    if (heartbeatUrl && heartbeatMs > 0) {
        beat();
        setInterval(beat, heartbeatMs);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                resumeLiveSignals();
            }
        });
        window.addEventListener('pageshow', resumeLiveSignals);
        window.addEventListener('focus', resumeLiveSignals);
    }

    function releasePresence(event) {
        if (event && event.persisted) {
            return;
        }
        postPresence(leaveUrl, true);
    }

    window.addEventListener('pagehide', releasePresence);

    // Pełny ekran wymaga shell + btn; belka/poll/obecność działają i bez tego.
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
        if (!btn) {
            return;
        }
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

        const host = liveOfferModalHost();
        if (modalEl.parentElement !== host) {
            host.appendChild(modalEl);
        }

        function relocateCloseBackdrop() {
            placeLiveOfferModalLayer(modalEl);
        }

        modalEl.addEventListener('show.bs.modal', function () {
            queueMicrotask(relocateCloseBackdrop);
            setTimeout(relocateCloseBackdrop, 0);
        }, { once: true });
        modalEl.addEventListener('shown.bs.modal', relocateCloseBackdrop, { once: true });
        modalEl.addEventListener('hidden.bs.modal', function () {
            cleanupLiveOfferChrome();
            if (shell && modalEl.parentElement !== shell) {
                shell.appendChild(modalEl);
            }
        }, { once: true });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        relocateCloseBackdrop();
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
    // Ten sam poll dociąga belkę zasobów (lista / materiały / ankieta) bez odświeżania strony.
    const meetingStatusUrl = @json($meetingStatusUrl ?? null);
    const meetingStatusPollMs = {{ (int) ($meetingStatusPollMs ?? 5000) }};
    const meetingStatusFirstMs = {{ (int) ($meetingStatusFirstMs ?? 400) }};
    let meetingEndedHandled = false;
    let lastMeetingStatusOkAt = 0;
    let authRecoverTried = false;

    const liveBarCourseId = {{ (int) ($course?->id ?? 0) }};
    const liveBarSeenCookie = 'pne_live_bar_seen_' + liveBarCourseId;
    const liveBarSeenMaxAge = 60 * 60 * 18;
    let lastResourceSignature = null;
    let lastOfferSignature = null;
    let dismissedOfferSignature = null;
    let liveOfferModalBound = false;
    let liveOfferAutoHideTimer = null;

    function liveBarIconClass(key) {
        const k = typeof key === 'string' ? key : '';
        if (k === 'attendance' || k.indexOf('attendance') === 0) {
            return 'bi bi-person-check-fill';
        }
        if (k.indexOf('material') === 0) {
            return 'bi bi-file-earmark-text-fill';
        }
        if (k.indexOf('survey') === 0) {
            return 'bi bi-clipboard-data-fill';
        }
        return 'bi bi-box-arrow-up-right';
    }

    function liveBarIconElement(key) {
        const k = typeof key === 'string' ? key : '';
        if (k === 'certificate' || k.indexOf('certificate') === 0) {
            const ns = 'http://www.w3.org/2000/svg';
            const svg = document.createElementNS(ns, 'svg');
            svg.setAttribute('viewBox', '0 0 16 16');
            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('focusable', 'false');
            svg.classList.add('cm-live-resource-link__icon', 'cm-live-resource-link__icon--diploma');

            const paperFill = document.createElementNS(ns, 'rect');
            paperFill.setAttribute('x', '1.2');
            paperFill.setAttribute('y', '2.6');
            paperFill.setAttribute('width', '13.6');
            paperFill.setAttribute('height', '10.8');
            paperFill.setAttribute('rx', '1.2');
            paperFill.setAttribute('fill', 'currentColor');
            paperFill.setAttribute('opacity', '0.18');

            const paper = document.createElementNS(ns, 'rect');
            paper.setAttribute('x', '1.2');
            paper.setAttribute('y', '2.6');
            paper.setAttribute('width', '13.6');
            paper.setAttribute('height', '10.8');
            paper.setAttribute('rx', '1.2');
            paper.setAttribute('fill', 'none');
            paper.setAttribute('stroke', 'currentColor');
            paper.setAttribute('stroke-width', '1.25');

            const lines = document.createElementNS(ns, 'path');
            lines.setAttribute('d', 'M3.6 5.4h8.8M3.6 7.5h8.8M3.6 9.6h5.2');
            lines.setAttribute('fill', 'none');
            lines.setAttribute('stroke', 'currentColor');
            lines.setAttribute('stroke-width', '1.15');
            lines.setAttribute('stroke-linecap', 'round');

            const seal = document.createElementNS(ns, 'circle');
            seal.setAttribute('cx', '11.7');
            seal.setAttribute('cy', '10.7');
            seal.setAttribute('r', '1.85');
            seal.setAttribute('fill', 'currentColor');

            const ribbon = document.createElementNS(ns, 'path');
            ribbon.setAttribute('d', 'M11.7 12.4l-.55 1.7.55-.35.55.35z');
            ribbon.setAttribute('fill', 'currentColor');

            svg.appendChild(paperFill);
            svg.appendChild(paper);
            svg.appendChild(lines);
            svg.appendChild(seal);
            svg.appendChild(ribbon);
            return svg;
        }
        const icon = document.createElement('i');
        icon.className = liveBarIconClass(key) + ' cm-live-resource-link__icon';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function readLiveBarSeenKeys() {
        const prefix = liveBarSeenCookie + '=';
        const parts = document.cookie.split(';');
        for (let i = 0; i < parts.length; i++) {
            const part = parts[i].trim();
            if (part.indexOf(prefix) !== 0) {
                continue;
            }
            try {
                return decodeURIComponent(part.slice(prefix.length))
                    .split(',')
                    .map(function (k) { return k.trim(); })
                    .filter(Boolean)
                    .reduce(function (acc, k) {
                        acc[k] = true;
                        return acc;
                    }, {});
            } catch (e) {
                return {};
            }
        }
        return {};
    }

    function markLiveBarLinkSeen(key) {
        if (!key || liveBarCourseId < 1) {
            return;
        }
        const seen = readLiveBarSeenKeys();
        seen[key] = true;
        document.cookie = liveBarSeenCookie + '=' + encodeURIComponent(Object.keys(seen).join(','))
            + '; path=/; max-age=' + liveBarSeenMaxAge + '; SameSite=Lax';
        document.querySelectorAll('[data-live-resource-key="' + key + '"]').forEach(function (el) {
            el.classList.remove('is-attention');
        });
    }

    function resourceLinksSignature(links) {
        return links.map(function (link) {
            return [link.key || '', link.url || '', link.label || ''].join('\t');
        }).join('\n');
    }

    function renderLiveResourceLinks(links) {
        const slots = document.querySelectorAll('[data-live-resource-links]');
        const items = Array.isArray(links) ? links : [];
        const signature = resourceLinksSignature(items);
        if (signature === lastResourceSignature) {
            return;
        }
        lastResourceSignature = signature;
        const seen = readLiveBarSeenKeys();
        slots.forEach(function (slot) {
            slot.replaceChildren();
            items.forEach(function (link) {
                if (!link || typeof link.url !== 'string' || link.url === '') {
                    return;
                }
                const key = typeof link.key === 'string' ? link.key : '';
                const a = document.createElement('a');
                a.className = 'btn btn-sm btn-warning cm-live-resource-link';
                a.href = link.url;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                if (key) {
                    a.setAttribute('data-live-resource-key', key);
                }
                if (key && !seen[key]) {
                    a.classList.add('is-attention');
                }
                const icon = liveBarIconElement(key);
                const label = document.createElement('span');
                label.textContent = typeof link.label === 'string' && link.label !== '' ? link.label : 'Link';
                a.appendChild(icon);
                a.appendChild(label);
                a.addEventListener('click', function () {
                    if (key) {
                        markLiveBarLinkSeen(key);
                    }
                    exitNativeFullscreenOnly();
                });
                slot.appendChild(a);
            });
            if (items.length === 0) {
                slot.setAttribute('hidden', 'hidden');
            } else {
                slot.removeAttribute('hidden');
            }
        });
    }

    function liveOfferSignature(offer) {
        if (!offer || typeof offer !== 'object') {
            return '';
        }
        const price = offer.price && typeof offer.price === 'object' ? offer.price : {};
        return [
            offer.course_id || '',
            offer.enabled_at || '',
            offer.expires_at || '',
            offer.title || '',
            offer.start_date || '',
            offer.instructor || '',
            offer.image_url || '',
            offer.description || '',
            offer.order_url || '',
            offer.description_url || '',
            price.amount_label || '',
            price.original_amount_label || '',
            price.is_promotion ? '1' : '0',
            price.promotion_end_label || '',
        ].join('\t');
    }

    function fillLiveOfferModal(modalEl, item) {
        const titleEl = modalEl.querySelector('[data-live-offer-title]');
        const dateWrap = modalEl.querySelector('[data-live-offer-date-wrap]');
        const dateEl = modalEl.querySelector('[data-live-offer-date]');
        const instructorWrap = modalEl.querySelector('[data-live-offer-instructor-wrap]');
        const instructorEl = modalEl.querySelector('[data-live-offer-instructor]');
        const imageWrap = modalEl.querySelector('[data-live-offer-image-wrap]');
        const imageEl = modalEl.querySelector('[data-live-offer-image]');
        const descriptionEl = modalEl.querySelector('[data-live-offer-description]');
        const descLinkWrap = modalEl.querySelector('[data-live-offer-desc-link-wrap]');
        const descLink = modalEl.querySelector('[data-live-offer-desc-link]');
        const priceWrap = modalEl.querySelector('[data-live-offer-price-wrap]');
        const priceCurrent = modalEl.querySelector('[data-live-offer-price-current]');
        const priceOriginal = modalEl.querySelector('[data-live-offer-price-original]');
        const promoBadge = modalEl.querySelector('[data-live-offer-promo-badge]');
        const promoEnd = modalEl.querySelector('[data-live-offer-promo-end]');
        const omnibusEl = modalEl.querySelector('[data-live-offer-omnibus]');
        const cta = modalEl.querySelector('[data-live-offer-cta]');

        if (titleEl) {
            titleEl.textContent = item.title || 'Szkolenie';
        }
        if (dateWrap && dateEl) {
            if (item.start_date) {
                dateEl.textContent = item.start_date;
                dateWrap.removeAttribute('hidden');
            } else {
                dateEl.textContent = '';
                dateWrap.setAttribute('hidden', 'hidden');
            }
        }
        if (instructorWrap && instructorEl) {
            if (item.instructor) {
                instructorEl.textContent = item.instructor;
                instructorWrap.removeAttribute('hidden');
            } else {
                instructorEl.textContent = '';
                instructorWrap.setAttribute('hidden', 'hidden');
            }
        }
        if (imageWrap && imageEl) {
            if (item.image_url) {
                imageEl.src = item.image_url;
                imageEl.alt = item.title || 'Grafika szkolenia';
                imageWrap.removeAttribute('hidden');
            } else {
                imageEl.removeAttribute('src');
                imageEl.alt = '';
                imageWrap.setAttribute('hidden', 'hidden');
            }
        }
        if (descriptionEl) {
            if (item.description) {
                descriptionEl.textContent = item.description;
                descriptionEl.removeAttribute('hidden');
            } else {
                descriptionEl.textContent = '';
                descriptionEl.setAttribute('hidden', 'hidden');
            }
        }
        if (descLinkWrap && descLink) {
            if (item.description_url) {
                descLink.href = item.description_url;
                descLinkWrap.removeAttribute('hidden');
                if (!descLink.dataset.offerBound) {
                    descLink.dataset.offerBound = '1';
                    descLink.addEventListener('click', function () {
                        exitNativeFullscreenOnly();
                    });
                }
            } else {
                descLink.removeAttribute('href');
                descLinkWrap.setAttribute('hidden', 'hidden');
            }
        }
        if (priceWrap && priceCurrent) {
            const price = item.price && typeof item.price === 'object' ? item.price : null;
            if (price && price.amount_label) {
                priceCurrent.textContent = price.amount_label;
                priceCurrent.classList.toggle('is-promo', !!price.is_promotion && !price.is_free);
                if (priceOriginal) {
                    if (price.original_amount_label) {
                        priceOriginal.textContent = price.original_amount_label;
                        priceOriginal.removeAttribute('hidden');
                    } else {
                        priceOriginal.textContent = '';
                        priceOriginal.setAttribute('hidden', 'hidden');
                    }
                }
                if (promoBadge) {
                    if (price.is_promotion && !price.is_free) {
                        promoBadge.removeAttribute('hidden');
                    } else {
                        promoBadge.setAttribute('hidden', 'hidden');
                    }
                }
                if (promoEnd) {
                    if (price.promotion_end_label) {
                        promoEnd.textContent = 'Promocja do ' + price.promotion_end_label;
                        promoEnd.removeAttribute('hidden');
                    } else {
                        promoEnd.textContent = '';
                        promoEnd.setAttribute('hidden', 'hidden');
                    }
                }
                if (omnibusEl) {
                    if (price.omnibus_lowest_label) {
                        omnibusEl.textContent = price.omnibus_lowest_label;
                        omnibusEl.removeAttribute('hidden');
                    } else {
                        omnibusEl.textContent = '';
                        omnibusEl.setAttribute('hidden', 'hidden');
                    }
                }
                priceWrap.removeAttribute('hidden');
            } else {
                priceWrap.setAttribute('hidden', 'hidden');
            }
        }
        if (cta) {
            cta.href = item.order_url;
            if (!cta.dataset.offerBound) {
                cta.dataset.offerBound = '1';
                cta.addEventListener('click', function () {
                    exitNativeFullscreenOnly();
                });
            }
        }
    }

    function clearLiveOfferAutoHide() {
        if (liveOfferAutoHideTimer) {
            clearTimeout(liveOfferAutoHideTimer);
            liveOfferAutoHideTimer = null;
        }
    }

    function liveOfferModalHost() {
        // Natywny FS albo CSS FS (is-fullscreen) — host = shell/FS root.
        // Poza FS — body (normalny Bootstrap, bez konfliktu z-index).
        if (document.fullscreenElement) {
            return document.fullscreenElement;
        }
        if (shell && shell.classList.contains('is-fullscreen')) {
            return shell;
        }
        return document.body;
    }

    function placeLiveOfferModalLayer(modalEl) {
        if (!modalEl) {
            return;
        }
        const host = liveOfferModalHost();
        if (modalEl.parentElement !== host) {
            host.appendChild(modalEl);
        }

        const backdrops = Array.prototype.slice.call(document.querySelectorAll('.modal-backdrop'));
        if (backdrops.length === 0) {
            return;
        }
        // Jeden backdrop — reszta to orphan po poprzednich show/hide.
        backdrops.slice(0, -1).forEach(function (el) {
            el.remove();
        });
        const backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            return;
        }

        if (host === document.body) {
            if (backdrop.parentElement !== document.body) {
                document.body.appendChild(backdrop);
            }
            // Modal po backdropie w DOM.
            document.body.appendChild(modalEl);
            modalEl.style.removeProperty('position');
            modalEl.style.removeProperty('inset');
            modalEl.style.removeProperty('z-index');
            modalEl.style.removeProperty('width');
            modalEl.style.removeProperty('height');
            modalEl.style.removeProperty('pointer-events');
            backdrop.style.removeProperty('position');
            backdrop.style.removeProperty('inset');
            backdrop.style.removeProperty('z-index');
            backdrop.style.removeProperty('width');
            backdrop.style.removeProperty('height');
            return;
        }

        // W shell/FS: najpierw backdrop, potem modal (wyższy stacking + kolejność DOM).
        if (backdrop.parentElement !== host) {
            host.insertBefore(backdrop, modalEl);
        } else if (backdrop.nextElementSibling !== modalEl) {
            host.insertBefore(backdrop, modalEl);
        }
        if (modalEl.parentElement !== host) {
            host.appendChild(modalEl);
        } else if (backdrop.compareDocumentPosition(modalEl) & Node.DOCUMENT_POSITION_PRECEDING) {
            host.appendChild(modalEl);
        }
    }

    function cleanupLiveOfferChrome() {
        // Zostający .modal-backdrop / modal-open blokuje kliknięcia w iframe CM (przyciemnienie).
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length > 0) {
            return;
        }
        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
            el.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        if (shell) {
            shell.querySelectorAll(':scope > .modal-backdrop').forEach(function (el) {
                el.remove();
            });
        }
        const offerModal = document.getElementById('cmLiveOfferModal');
        if (offerModal) {
            offerModal.style.removeProperty('position');
            offerModal.style.removeProperty('inset');
            offerModal.style.removeProperty('z-index');
            offerModal.style.removeProperty('width');
            offerModal.style.removeProperty('height');
            offerModal.style.removeProperty('pointer-events');
            // Wróć do shella (kanoniczne miejsce w DOM), żeby kolejny show miał stabilny parent.
            if (shell && offerModal.parentElement !== shell) {
                shell.appendChild(offerModal);
            }
        }
    }

    function relocateLiveOfferBackdrop(modalEl) {
        placeLiveOfferModalLayer(modalEl);
    }

    function scheduleLiveOfferAutoHide(offer) {
        clearLiveOfferAutoHide();
        if (!offer || typeof offer !== 'object') {
            return;
        }
        if (offer.auto_hide === false) {
            return;
        }
        let delayMs = null;
        if (typeof offer.expires_at === 'string' && offer.expires_at !== '') {
            const end = Date.parse(offer.expires_at);
            if (Number.isFinite(end)) {
                delayMs = end - Date.now();
                if (delayMs <= 0) {
                    hideLiveOfferModal(true);
                    return;
                }
            }
        }
        if (delayMs === null) {
            if (!offer.expires_at && offer.auto_hide === false) {
                return;
            }
            // Bez expires_at przy auto_hide: nie wymuszaj sztucznego 120s od teraz
            // (serwer i tak ustawi expires_at). Brak daty = brak lokalnego timera.
            return;
        }
        delayMs = Math.max(250, delayMs);
        liveOfferAutoHideTimer = setTimeout(function () {
            liveOfferAutoHideTimer = null;
            hideLiveOfferModal(true);
        }, delayMs);
    }

    function hideLiveOfferModal(markDismissed) {
        clearLiveOfferAutoHide();
        const modalEl = document.getElementById('cmLiveOfferModal');
        if (!modalEl || typeof bootstrap === 'undefined') {
            cleanupLiveOfferChrome();
            return;
        }
        if (markDismissed && lastOfferSignature) {
            dismissedOfferSignature = lastOfferSignature;
        }
        const instance = bootstrap.Modal.getInstance(modalEl) || ensureLiveOfferModalInstance(modalEl);
        instance.hide();
        // Na wypadek, gdy Bootstrap nie zdążył zdjąć backdropu (FS / przeniesiony parent).
        setTimeout(cleanupLiveOfferChrome, 50);
        setTimeout(cleanupLiveOfferChrome, 350);
    }

    function ensureLiveOfferModalInstance(modalEl) {
        let instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) {
            return instance;
        }
        return bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: false,
            keyboard: true,
            focus: false,
        });
    }

    function renderLiveOffer(offer) {
        const modalEl = document.getElementById('cmLiveOfferModal');
        if (!modalEl || typeof bootstrap === 'undefined') {
            return;
        }

        const item = offer && typeof offer === 'object' && typeof offer.order_url === 'string' && offer.order_url !== ''
            ? offer
            : null;
        const signature = liveOfferSignature(item);
        const instance = ensureLiveOfferModalInstance(modalEl);

        if (!liveOfferModalBound) {
            liveOfferModalBound = true;
            modalEl.addEventListener('hide.bs.modal', function () {
                if (lastOfferSignature) {
                    dismissedOfferSignature = lastOfferSignature;
                }
                clearLiveOfferAutoHide();
            });
            modalEl.addEventListener('hidden.bs.modal', function () {
                cleanupLiveOfferChrome();
            });
            modalEl.addEventListener('shown.bs.modal', function () {
                // Oferta bez backdropu — upewnij się, że nic nie zasłania iframe.
                document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                    el.remove();
                });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                placeLiveOfferModalLayer(modalEl);
                queueMicrotask(function () {
                    placeLiveOfferModalLayer(modalEl);
                    document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                        el.remove();
                    });
                });
            });
        }

        if (signature === lastOfferSignature) {
            if (item && signature !== dismissedOfferSignature) {
                scheduleLiveOfferAutoHide(item);
            }
            return;
        }
        lastOfferSignature = signature;

        if (!item) {
            dismissedOfferSignature = null;
            hideLiveOfferModal(false);
            const cta = modalEl.querySelector('[data-live-offer-cta]');
            if (cta) {
                cta.removeAttribute('href');
            }
            return;
        }

        fillLiveOfferModal(modalEl, item);

        if (signature === dismissedOfferSignature) {
            clearLiveOfferAutoHide();
            return;
        }

        placeLiveOfferModalLayer(modalEl);
        instance.show();
        placeLiveOfferModalLayer(modalEl);
        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
            el.remove();
        });
        document.body.classList.remove('modal-open');
        scheduleLiveOfferAutoHide(item);
    }

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
            if (res.status === 401 || res.status === 419) {
                // Sesja wygasła przy długiej nieobecności — bez F5 belka i obecność giną.
                if (!authRecoverTried && document.visibilityState === 'visible') {
                    authRecoverTried = true;
                    window.location.reload();
                }
                return null;
            }
            if (!res.ok) {
                return null;
            }
            lastMeetingStatusOkAt = Date.now();
            return res.json();
        }).then(function (data) {
            if (data && Array.isArray(data.resource_links)) {
                renderLiveResourceLinks(data.resource_links);
            }
            if (data && Object.prototype.hasOwnProperty.call(data, 'live_offer')) {
                renderLiveOffer(data.live_offer);
            }
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
    let meetingStatusTimer = null;
    let meetingStatusInterval = null;
    let meetingStatusWatchdog = null;

    function stopMeetingStatusPoll() {
        if (meetingStatusTimer) {
            clearTimeout(meetingStatusTimer);
            meetingStatusTimer = null;
        }
        if (meetingStatusInterval) {
            clearInterval(meetingStatusInterval);
            meetingStatusInterval = null;
        }
    }

    function startMeetingStatusPoll() {
        stopMeetingStatusPoll();
        if (!meetingStatusUrl) {
            return;
        }
        meetingStatusTimer = setTimeout(pollMeetingEnded, meetingStatusFirstMs);
        meetingStatusInterval = setInterval(pollMeetingEnded, meetingStatusPollMs);
    }

    function ensureMeetingStatusPollAlive() {
        if (!meetingStatusUrl || meetingEndedHandled) {
            return;
        }
        const staleMs = Math.max(meetingStatusPollMs * 3, 15000);
        if (!meetingStatusInterval || (lastMeetingStatusOkAt > 0 && (Date.now() - lastMeetingStatusOkAt) > staleMs)) {
            startMeetingStatusPoll();
            pollMeetingEnded();
        }
    }

    // Nie zatrzymujemy pollu przy ukrytej karcie — Chrome i tak go zwalnia,
    // a stop+brak wznowienia psuje belkę i „Teraz na live” po powrocie bez F5.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            resumeLiveSignals();
            ensureMeetingStatusPollAlive();
        }
    });
    window.addEventListener('pageshow', function () {
        resumeLiveSignals();
        ensureMeetingStatusPollAlive();
    });
    window.addEventListener('focus', function () {
        resumeLiveSignals();
        ensureMeetingStatusPollAlive();
    });

    startMeetingStatusPoll();
    if (!meetingStatusWatchdog) {
        meetingStatusWatchdog = setInterval(ensureMeetingStatusPollAlive, 15000);
    }

    if (btn) {
        btn.addEventListener('click', toggleFullscreen);
    }
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
