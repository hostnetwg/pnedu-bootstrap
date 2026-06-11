@php
    $hasSavedVideoNote = $videoNote !== null;
    $showStubInitially = ! $hasSavedVideoNote && ! $errors->has('training_video_note_body');
    $showViewInitially = $hasSavedVideoNote && ! $errors->has('training_video_note_body');
    $showEditInitially = ! $showStubInitially && ! $showViewInitially;
@endphp

<section id="training-video-notes-root"
         class="training-video-notes mt-3 pt-3 mb-4 border-top"
         aria-labelledby="training-video-notes-heading"
         data-video-id="{{ (int) $selectedVideo->id }}"
         data-initial-has-saved="{{ $hasSavedVideoNote ? '1' : '0' }}"
         data-save-url="{{ route('dashboard.szkolenia.wideo-note.save', [$participant, $selectedVideo]) }}">

    <h2 id="training-video-notes-heading" class="h6 mb-3 fw-normal">
        <span class="fw-semibold">Twoje notatki</span>
        <span class="text-muted"> (zapisz notatkę ze szkolenia — notatki pozostają dostępne także po wygaśnięciu dostępu do nagrania)</span>
    </h2>

    <div role="status" aria-live="polite" class="small js-training-video-note-feedback mb-2 d-none"></div>

    <div class="js-training-video-note-stub {{ $showStubInitially ? '' : 'd-none' }}">
        <button type="button" class="btn btn-outline-primary btn-sm js-training-video-note-btn-add">Dodaj notatkę</button>
    </div>

    <div class="js-training-video-note-view {{ $showViewInitially ? '' : 'd-none' }}">
        <div class="training-video-note-readonly border rounded-3 bg-body-secondary p-3 small text-break js-training-video-note-body-display" style="white-space: pre-wrap;">{{ $videoNote->body ?? '' }}</div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-outline-primary btn-sm js-training-video-note-btn-edit">Edytuj notatkę</button>
            <button type="button" class="btn btn-outline-danger btn-sm js-training-video-note-btn-delete">Usuń notatkę</button>
        </div>
    </div>

    <div class="js-training-video-note-edit {{ $showEditInitially ? '' : 'd-none' }}">
        <form class="js-training-video-note-form" method="post" action="{{ route('dashboard.szkolenia.wideo-note.save', [$participant, $selectedVideo]) }}">
            @csrf
            <label class="form-label visually-hidden" for="training_video_note_body">Treść notatki do nagrania</label>
            <textarea id="training_video_note_body"
                      name="training_video_note_body"
                      class="form-control small @error('training_video_note_body') is-invalid @enderror"
                      rows="8"
                      maxlength="65535"
                      placeholder="Np. przypomnienia, pojęcia do powtórki, linki…">{{ old('training_video_note_body', $videoNote->body ?? '') }}</textarea>
            <div class="invalid-feedback js-training-video-note-validation @error('training_video_note_body') d-block @else d-none @enderror">
                @error('training_video_note_body'){{ $message }}@enderror
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 align-items-center">
                <button type="submit" class="btn btn-primary btn-sm">Zapisz notatkę</button>
                <button type="button" class="btn btn-outline-secondary btn-sm js-training-video-note-btn-cancel @if(!$hasSavedVideoNote && !$errors->has('training_video_note_body')) d-none @endif">Anuluj</button>
            </div>
            <p class="small text-muted mt-2 mb-0 js-training-video-note-edit-hint @if($hasSavedVideoNote) d-none @endif">Zapis pustej treści anuluje dodawanie notatki.</p>
        </form>
    </div>

    <div class="modal fade js-training-video-note-delete-modal"
         tabindex="-1"
         aria-labelledby="training-video-note-delete-modal-label"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="training-video-note-delete-modal-label">Usunąć notatkę?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Notatka do tego nagrania zostanie trwale usunięta. Tej operacji nie można cofnąć.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-danger btn-sm js-training-video-note-delete-confirm">Usuń notatkę</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="application/json" id="training-video-notes-json">@json($videoNotesForList ?? [])</script>

@push('styles')
<style>
    .popover.training-video-note-popover .popover-body {
        white-space: pre-wrap;
        max-height: min(320px, 55vh);
        overflow-y: auto;
        font-size: 0.8125rem;
        text-align: start;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scriptEl = document.getElementById('training-video-notes-json');
    var notesMap = {};
    if (scriptEl) {
        try {
            notesMap = JSON.parse(scriptEl.textContent || '{}');
        } catch (e) {
            notesMap = {};
        }
    }

    function popoverPlacement() {
        return window.matchMedia('(min-width: 576px)').matches ? 'right' : 'top';
    }

    function disposePopover(btn) {
        if (!btn || typeof bootstrap === 'undefined') return;
        var inst = bootstrap.Popover.getInstance(btn);
        if (inst) inst.dispose();
    }

    function attachPopover(btn, text) {
        if (!btn || typeof bootstrap.Popover === 'undefined') return;
        disposePopover(btn);
        new bootstrap.Popover(btn, {
            title: 'Twoja notatka',
            content: text,
            html: false,
            sanitize: true,
            placement: popoverPlacement(),
            trigger: 'hover focus',
            container: 'body',
            customClass: 'training-video-note-popover'
        });
    }

    function syncNotesJsonFile() {
        if (scriptEl) scriptEl.textContent = JSON.stringify(notesMap);
    }

    function syncListNoteButton(videoIdStr, bodyText) {
        var rows = document.querySelectorAll('[data-training-video-id="' + videoIdStr + '"]');
        if (!rows.length) return;

        var hasText = typeof bodyText === 'string' && bodyText.length > 0;

        if (!hasText) {
            delete notesMap[videoIdStr];
            syncNotesJsonFile();
        } else {
            notesMap[videoIdStr] = bodyText;
            syncNotesJsonFile();
        }

        rows.forEach(function (row) {
            var btn = row.querySelector('.js-training-video-note-popover');

            if (!hasText) {
                disposePopover(btn);
                if (btn) btn.remove();
                return;
            }

            if (!btn) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-link p-0 border-0 lh-1 text-primary flex-shrink-0 js-training-video-note-popover';
                btn.setAttribute('data-training-note-video-id', videoIdStr);
                btn.setAttribute('aria-label', 'Podgląd notatki do nagrania');
                btn.innerHTML = '<i class="bi bi-journal-text" aria-hidden="true"></i>';
                row.appendChild(btn);
            }
            attachPopover(btn, bodyText);
        });
    }

    function initListNotePopovers() {
        document.querySelectorAll('.js-training-video-note-popover').forEach(function (btn) {
            var id = String(btn.getAttribute('data-training-note-video-id') || '');
            var text = notesMap[id];
            if (text != null && text !== '') attachPopover(btn, text);
            else disposePopover(btn);
        });
    }

    initListNotePopovers();

    var root = document.getElementById('training-video-notes-root');
    if (!root) return;

    var saveUrl = root.getAttribute('data-save-url') || '';
    var videoIdStr = String(root.getAttribute('data-video-id') || '');

    var elStub = root.querySelector('.js-training-video-note-stub');
    var elView = root.querySelector('.js-training-video-note-view');
    var elEdit = root.querySelector('.js-training-video-note-edit');
    var displayEl = root.querySelector('.js-training-video-note-body-display');
    var form = root.querySelector('.js-training-video-note-form');
    var ta = document.getElementById('training_video_note_body');
    var btnEdit = root.querySelector('.js-training-video-note-btn-edit');
    var btnDelete = root.querySelector('.js-training-video-note-btn-delete');
    var btnCancel = root.querySelector('.js-training-video-note-btn-cancel');
    var feedback = root.querySelector('.js-training-video-note-feedback');
    var hint = root.querySelector('.js-training-video-note-edit-hint');

    var hasSaved = root.getAttribute('data-initial-has-saved') === '1';
    var savedBody = (hasSaved && displayEl) ? displayEl.textContent.replace(/\u00a0/g, ' ') : '';

    function hideValidation() {
        var el = root.querySelector('.js-training-video-note-validation');
        if (el) {
            el.textContent = '';
            el.classList.remove('d-block');
            el.classList.add('d-none');
        }
        if (ta) ta.classList.remove('is-invalid');
    }

    function showValidation(msg) {
        var el = root.querySelector('.js-training-video-note-validation');
        if (el) {
            el.textContent = msg || '';
            if (msg) {
                el.classList.remove('d-none');
                el.classList.add('d-block');
            } else {
                el.classList.remove('d-block');
                el.classList.add('d-none');
            }
        }
        if (ta) ta.classList.add('is-invalid');
    }

    function showFeedback(msg, isErr) {
        if (!feedback) return;
        clearTimeout(feedback._tHide);
        if (!msg) {
            feedback.classList.add('d-none');
            feedback.textContent = '';
            return;
        }
        feedback.textContent = msg;
        feedback.classList.remove('d-none');
        feedback.classList.remove('text-success', 'text-danger', 'text-dark');
        feedback.classList.add(isErr ? 'text-danger' : 'text-success');
        feedback._tHide = setTimeout(function () {
            feedback.classList.add('d-none');
            feedback.textContent = '';
        }, 4500);
    }

    function setMode(editMode) {
        if (!elEdit) return;

        if (!hasSaved) {
            if (elView) elView.classList.add('d-none');

            if (editMode) {
                if (elStub) elStub.classList.add('d-none');
                elEdit.classList.remove('d-none');
                if (btnCancel) btnCancel.classList.remove('d-none');
                if (hint) hint.classList.remove('d-none');
                if (ta) setTimeout(function () { ta.focus(); }, 50);
            } else {
                elEdit.classList.add('d-none');
                if (elStub) elStub.classList.remove('d-none');
                if (btnCancel) btnCancel.classList.add('d-none');
                if (ta) ta.value = '';
            }
            return;
        }

        if (elStub) elStub.classList.add('d-none');

        if (editMode) {
            if (elView) elView.classList.add('d-none');
            elEdit.classList.remove('d-none');
            if (hint) hint.classList.add('d-none');
            if (btnCancel) btnCancel.classList.remove('d-none');
            if (ta) {
                ta.value = savedBody;
                setTimeout(function () { ta.focus(); }, 50);
            }
        } else {
            elEdit.classList.add('d-none');
            if (elView) elView.classList.remove('d-none');
        }
    }

    function postTrainingVideoNote(noteVal) {
        if (!form || !saveUrl) {
            return Promise.resolve(null);
        }

        var fd = new FormData(form);
        fd.set('training_video_note_body', noteVal);

        hideValidation();

        return fetch(saveUrl, {
            method: 'POST',
            body: fd,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function (res) {
            if (res.status === 419) {
                window.location.reload();
                return null;
            }

            var jsonPr = function () {
                return res.json().catch(function () { return {}; });
            };

            if (res.status === 422) {
                return jsonPr().then(function (payload) {
                    var msg = (payload.errors && payload.errors.training_video_note_body && payload.errors.training_video_note_body[0])
                        || payload.message
                        || 'Sprawdź treść notatki.';
                    showValidation(msg);
                    return null;
                });
            }

            if (!res.ok) {
                return jsonPr().then(function () {
                    showFeedback('Nie zapisano. Spróbuj ponownie.', true);
                    return null;
                });
            }

            return jsonPr();
        }).then(function (data) {
            return data || null;
        });
    }

    var btnAdd = root.querySelector('.js-training-video-note-btn-add');

    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            setMode(true);
        });
    }

    if (btnEdit) {
        btnEdit.addEventListener('click', function () {
            setMode(true);
        });
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            if (ta && hasSaved) ta.value = savedBody;
            else if (ta) ta.value = '';
            hideValidation();
            setMode(false);
        });
    }

    if (btnDelete) {
        var deleteModalEl = root.querySelector('.js-training-video-note-delete-modal');
        var deleteModal = (deleteModalEl && typeof bootstrap !== 'undefined')
            ? bootstrap.Modal.getOrCreateInstance(deleteModalEl)
            : null;
        var btnDeleteConfirm = root.querySelector('.js-training-video-note-delete-confirm');

        function performDeleteNote() {
            postTrainingVideoNote('').then(function (data) {
                if (!data || !Object.prototype.hasOwnProperty.call(data, 'deleted')) return;
                if (data.deleted) {
                    hasSaved = false;
                    savedBody = '';
                    if (displayEl) displayEl.textContent = '';
                    if (ta) ta.value = '';
                    syncListNoteButton(videoIdStr, '');
                    if (hint) hint.classList.remove('d-none');
                    setMode(false);
                }
                showFeedback(data.message || '', false);
            });
        }

        btnDelete.addEventListener('click', function () {
            if (deleteModal) {
                deleteModal.show();
                return;
            }

            if (!window.confirm('Usunąć notatkę do tego nagrania?')) return;
            performDeleteNote();
        });

        if (btnDeleteConfirm) {
            btnDeleteConfirm.addEventListener('click', function () {
                if (deleteModal) deleteModal.hide();
                performDeleteNote();
            });
        }
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            postTrainingVideoNote(ta ? ta.value : '').then(function (data) {
                if (!data) return;

                if (data.saved && typeof data.body === 'string') {
                    savedBody = data.body;
                    hasSaved = true;
                    if (displayEl) displayEl.textContent = data.body;
                    syncListNoteButton(videoIdStr, data.body);
                    if (hint) hint.classList.add('d-none');
                    hideValidation();
                    setMode(false);
                    showFeedback(data.message || '', false);
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(data, 'deleted') && data.deleted) {
                    hasSaved = false;
                    savedBody = '';
                    if (displayEl) displayEl.textContent = '';
                    if (ta) ta.value = '';
                    syncListNoteButton(videoIdStr, '');
                    if (hint) hint.classList.remove('d-none');
                    setMode(false);
                    showFeedback(data.message || '', false);
                    return;
                }

                if (data.message) {
                    showFeedback(data.message, false);
                }
            });
        });
    }
});
</script>
@endpush
