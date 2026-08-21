{{-- Błąd transmisji w iframe fullscreen — modal, po zamknięciu wyjście z maksymalizacji --}}
@extends('layouts.transmisja-bare')

@section('title', 'Transmisja — komunikat')

@section('content')
<div class="modal fade" id="cmTransmisjaErrorModal" tabindex="-1" aria-labelledby="cmTransmisjaErrorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-5" id="cmTransmisjaErrorModalLabel">Nie można otworzyć transmisji</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">{{ $message }}</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function notifyHostClose() {
        try {
            if (window.top && window.top !== window.self) {
                window.top.postMessage({ type: 'cm-embed-close' }, window.location.origin);
                return;
            }
        } catch (e) {}
        try {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            }
        } catch (e2) {}
        window.location.href = @json(route('dashboard.szkolenia'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('cmTransmisjaErrorModal');
        if (!el || typeof bootstrap === 'undefined') {
            notifyHostClose();
            return;
        }
        var modal = bootstrap.Modal.getOrCreateInstance(el);
        el.addEventListener('hidden.bs.modal', function () {
            notifyHostClose();
        });
        modal.show();
    });
})();
</script>
@endsection
