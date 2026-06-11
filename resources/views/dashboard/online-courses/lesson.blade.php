@extends('layouts.app')

@section('title', $lesson->title.' – '.$course->title.' – Platforma Nowoczesnej Edukacji')

@section('content')
@php
    $lessonNotesForSidebar = $lessonNotesForSidebar ?? [];
    $hasSavedLessonNote = $lessonNote !== null;
    $showStubInitially = ! $hasSavedLessonNote && ! $errors->has('lesson_note_body');
    $showViewInitially = $hasSavedLessonNote && ! $errors->has('lesson_note_body');
    $showEditInitially = ! $showStubInitially && ! $showViewInitially;
@endphp
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-4 order-1 order-lg-1 mb-4 mb-lg-0">
            <nav>@include('dashboard.partials.sidebar-nav-menu')</nav>
            <div class="d-none d-lg-block mt-3">
                @include('dashboard.online-courses.partials.lesson-toc')
            </div>
        </div>
        <div class="col-12 col-lg-8 order-2 order-lg-2">
            <div class="mb-3">
                <a href="{{ route('dashboard.online-courses.index') }}" class="btn btn-link text-decoration-none p-0 small">← Lista kursów online</a>
            </div>
            @if(($lessonProgress['total'] ?? 0) > 0)
                @php($pct = min(100, (int) round(100 * (int) $lessonProgress['completed'] / (int) $lessonProgress['total'])))
                <form method="post"
                      action="{{ route('dashboard.online-courses.lesson-completion.toggle', [$enrollment, $lesson]) }}"
                      class="online-lesson-progress-head mb-3"
                      data-oc-current-lesson-id="{{ $lesson->id }}"
                      data-label-if-completed="Ukończone — kliknij, aby cofnąć"
                      data-label-if-incomplete="Oznacz jako ukończoną">
                    @csrf
                    <div id="lesson-progress-summary" class="d-flex justify-content-between small text-muted mb-1">
                        <span>Postęp w kursie</span>
                        <span>{{ (int) $lessonProgress['completed'] }} / {{ (int) $lessonProgress['total'] }} lekcji</span>
                    </div>
                    <div class="progress mb-2" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                    </div>
                    <div class="d-flex align-items-center gap-2 lesson-complete-control">
                        <input type="checkbox"
                               class="form-check-input rounded-0 lesson-complete-checkbox flex-shrink-0"
                               id="lesson-complete-checkbox"
                               @checked($currentLessonCompleted ?? false)
                               onchange="this.form.submit()"
                               autocomplete="off"
                               aria-describedby="lesson-progress-summary">
                        <label for="lesson-complete-checkbox" class="form-check-label small fw-medium mb-0 user-select-none cursor-pointer lh-sm">
                            @if($currentLessonCompleted ?? false)
                                Ukończone — kliknij, aby cofnąć
                            @else
                                Oznacz jako ukończoną
                            @endif
                        </label>
                    </div>
                </form>
            @endif
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-4">
                    <h1 class="h4 mb-3">{{ $lesson->title }}</h1>
                    @include('dashboard.online-courses.partials.lesson-adjacent-nav')

                    @foreach($lesson->embeds as $embed)
                        <div class="online-lesson-embed-slot mb-4" data-oc-embed-index="{{ $loop->index }}">
                        @if(in_array($embed->platform, ['youtube', 'vimeo'], true))
                            @php($embedSrc = $embed->getEmbedUrl(true, rtrim(request()->getSchemeAndHttpHost(), '/')))
                            <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                                <iframe src="{{ $embedSrc }}"
                                        id="oc-lesson-embed-{{ $loop->index }}"
                                        title="{{ $embed->title ?: $lesson->title }}"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        data-oc-video-platform="{{ $embed->platform }}"></iframe>
                            </div>
                        @else
                            <a href="{{ $embed->video_url }}" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">{{ $embed->title ?: 'Otwórz materiał wideo / zewnętrzny odtwarzacz' }}</a>
                        @endif
                        </div>
                    @endforeach

                    @if($lesson->body_html)
                        <div class="online-lesson-body mb-4">
                            {!! $lesson->body_html !!}
                        </div>
                    @endif

                    @if($lesson->resourceLinks->isNotEmpty())
                        <h6 class="mb-3">Materiały i linki</h6>
                        <ul class="list-group list-group-flush">
                            @foreach($lesson->resourceLinks as $link)
                                <li class="list-group-item px-0">
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">{{ $link->title ?: $link->url }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <section id="online-lesson-notes-root"
                             class="online-lesson-notes mt-4 pt-4 border-top"
                             aria-labelledby="lesson-notes-heading"
                             data-lesson-id="{{ (int) $lesson->id }}"
                             data-initial-has-saved="{{ $hasSavedLessonNote ? '1' : '0' }}"
                             data-save-url="{{ route('dashboard.online-courses.lesson-note.save', [$enrollment, $lesson]) }}">

                        <h2 id="lesson-notes-heading" class="h6 mb-3">Twoje notatki</h2>

                        <div role="status" aria-live="polite" class="small js-lesson-note-feedback mb-2 d-none"></div>

                        <div class="js-lesson-note-stub {{ $showStubInitially ? '' : 'd-none' }}">
                            <button type="button" class="btn btn-outline-primary btn-sm js-lesson-note-btn-add">Dodaj notatkę</button>
                        </div>

                        <div class="js-lesson-note-view {{ $showViewInitially ? '' : 'd-none' }}">
                            <div class="lesson-note-readonly border rounded-3 bg-body-secondary p-3 small text-break js-lesson-note-body-display" style="white-space: pre-wrap;">{{ $lessonNote->body ?? '' }}</div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button" class="btn btn-outline-primary btn-sm js-lesson-note-btn-edit">Edytuj notatkę</button>
                                <button type="button" class="btn btn-outline-danger btn-sm js-lesson-note-btn-delete">Usuń notatkę</button>
                            </div>
                        </div>

                        <div class="js-lesson-note-edit {{ $showEditInitially ? '' : 'd-none' }}">
                            <form class="js-lesson-note-form" method="post" action="{{ route('dashboard.online-courses.lesson-note.save', [$enrollment, $lesson]) }}">
                                @csrf
                                <label class="form-label visually-hidden" for="lesson_note_body">Treść notatki do lekcji</label>
                                <textarea id="lesson_note_body"
                                          name="lesson_note_body"
                                          class="form-control small @error('lesson_note_body') is-invalid @enderror"
                                          rows="8"
                                          maxlength="65535"
                                          placeholder="Np. przypomnienia, pojęcia do powtórki, linki…">{{ old('lesson_note_body', $lessonNote->body ?? '') }}</textarea>
                                <div class="invalid-feedback js-lesson-note-validation @error('lesson_note_body') d-block @else d-none @enderror">
                                    @error('lesson_note_body'){{ $message }}@enderror
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3 align-items-center">
                                    <button type="submit" class="btn btn-primary btn-sm">Zapisz notatkę</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm js-lesson-note-btn-cancel @if(!$hasSavedLessonNote && !$errors->has('lesson_note_body')) d-none @endif">Anuluj</button>
                                </div>
                                <p class="small text-muted mt-2 mb-0 js-lesson-note-edit-hint @if($hasSavedLessonNote) d-none @endif">Zapis pustej treści anuluje dodawanie notatki.</p>
                            </form>
                        </div>
                    </section>

                    @include('dashboard.online-courses.partials.linked-course-live-recordings', [
                        'linkedCourseLiveVideos' => $linkedCourseLiveVideos ?? collect(),
                    ])
                    @include('dashboard.online-courses.partials.certificate-registration-cta')

                </div>
            </div>
            <div class="d-lg-none mt-3">
                @include('dashboard.online-courses.partials.lesson-toc')
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="oc-lesson-notes-json">@json($lessonNotesForSidebar ?? [])</script>
@endsection

@push('styles')
@include('dashboard.partials.minimal-sidebar-css')
<style>
    .online-lesson-progress-head .lesson-complete-checkbox {
        width: 1.35rem;
        height: 1.35rem;
        margin-top: 0;
        cursor: pointer;
        accent-color: var(--bs-success);
    }
    .online-lesson-progress-head .lesson-complete-control label {
        cursor: pointer;
    }
    /* Popover Bootstrap: cała treść notatki, przewijanie przy długim tekście */
    .popover.oc-lesson-note-popover .popover-body {
        white-space: pre-wrap;
        max-height: min(320px, 55vh);
        overflow-y: auto;
        font-size: 0.8125rem;
        text-align: start;
    }
    /* Spis treści wideo (legacy z NE.pl: ul.no-bullets) */
    .online-lesson-body ul.no-bullets,
    .card-body ul.no-bullets {
        list-style-type: none;
        padding-left: 0;
    }
    /* Legacy: iframe w treści HTML (stara NE.pl — .video-container) */
    .online-lesson-body .video-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .online-lesson-body .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }
</style>
@endpush

@if(($lessonProgress['total'] ?? 0) > 0)
@push('scripts')
<script>
(function () {
    var form = document.querySelector('form.online-lesson-progress-head');
    var cb = document.getElementById('lesson-complete-checkbox');
    if (!form || !cb) return;

    cb.removeAttribute('onchange');

    var labelDone = form.getAttribute('data-label-if-completed') || '';
    var labelTodo = form.getAttribute('data-label-if-incomplete') || '';
    var lessonId = parseInt(form.getAttribute('data-oc-current-lesson-id') || '0', 10);
    var labelEl = form.querySelector('label[for="lesson-complete-checkbox"]');
    var progressWrap = form.querySelector('.progress');
    var progressBar = form.querySelector('.progress-bar');
    var summaryFraction = form.querySelector('#lesson-progress-summary span:last-child');

    function applySidebarIcon(done) {
        if (!lessonId) return;
        document.querySelectorAll('[data-oc-lesson-id="' + lessonId + '"]').forEach(function (row) {
            var icon = row.querySelector('.js-oc-lesson-status-icon');
            if (!icon) return;
            icon.className = 'bi flex-shrink-0 mt-1 js-oc-lesson-status-icon' + (done
                ? ' bi-check-circle-fill text-success'
                : ' bi-circle text-secondary');
            var hidden = row.querySelector('.visually-hidden');
            if (hidden) hidden.textContent = done ? 'Ukończone: ' : 'Do zrobienia: ';
        });
    }

    function applyLabel(done) {
        if (labelEl) labelEl.textContent = done ? labelDone : labelTodo;
    }

    function applyProgress(p) {
        var total = parseInt(p.total, 10) || 0;
        var completed = parseInt(p.completed, 10) || 0;
        var pct = total > 0 ? Math.min(100, Math.round(100 * completed / total)) : 0;
        if (summaryFraction) summaryFraction.textContent = completed + ' / ' + total + ' lekcji';
        if (progressBar) progressBar.style.width = pct + '%';
        if (progressWrap) progressWrap.setAttribute('aria-valuenow', String(pct));
    }

    cb.addEventListener('change', function () {
        var previousChecked = !cb.checked;
        cb.disabled = true;
        var body = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: body,
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
            if (!res.ok) throw new Error('toggle failed');
            return res.json();
        }).then(function (data) {
            if (!data) return;
            cb.checked = !!data.lesson_completed;
            applyLabel(data.lesson_completed);
            if (data.progress) applyProgress(data.progress);
            applySidebarIcon(data.lesson_completed);
        }).catch(function () {
            cb.checked = previousChecked;
        }).finally(function () {
            cb.disabled = false;
        });
    });
})();
</script>
@endpush
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scriptEl = document.getElementById('oc-lesson-notes-json');
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
            customClass: 'oc-lesson-note-popover'
        });
    }

    function syncLessonNotesJsonFile() {
        if (scriptEl) scriptEl.textContent = JSON.stringify(notesMap);
    }

    function syncSidebarNoteButton(lessonIdStr, bodyText) {
        var rows = document.querySelectorAll('[data-oc-lesson-id="' + lessonIdStr + '"]');
        if (!rows.length) return;

        var hasText = typeof bodyText === 'string' && bodyText.length > 0;

        if (!hasText) {
            delete notesMap[lessonIdStr];
            syncLessonNotesJsonFile();
        } else {
            notesMap[lessonIdStr] = bodyText;
            syncLessonNotesJsonFile();
        }

        rows.forEach(function (row) {
            var flexDiv = row.querySelector('div.d-flex.align-items-start');
            if (!flexDiv) return;

            var btn = flexDiv.querySelector('.js-oc-lesson-note-popover');

            if (!hasText) {
                disposePopover(btn);
                if (btn) btn.remove();
                return;
            }

            if (!btn) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-link p-0 border-0 lh-1 mt-1 text-primary flex-shrink-0 js-oc-lesson-note-popover';
                btn.setAttribute('data-oc-note-lesson-id', lessonIdStr);
                btn.setAttribute('aria-label', 'Podgląd notatki do lekcji');
                btn.innerHTML = '<i class="bi bi-journal-text" aria-hidden="true"></i>';
                var link = flexDiv.querySelector('a');
                if (link) link.insertAdjacentElement('afterend', btn);
                else flexDiv.appendChild(btn);
            }
            attachPopover(btn, bodyText);
        });
    }

    function initSidebarLessonNotePopovers() {
        document.querySelectorAll('.js-oc-lesson-note-popover').forEach(function (btn) {
            var id = String(btn.getAttribute('data-oc-note-lesson-id') || '');
            var text = notesMap[id];
            if (text != null && text !== '') attachPopover(btn, text);
            else disposePopover(btn);
        });
    }

    initSidebarLessonNotePopovers();

    var root = document.getElementById('online-lesson-notes-root');
    if (!root) return;

    var saveUrl = root.getAttribute('data-save-url') || '';
    var lessonIdStr = String(root.getAttribute('data-lesson-id') || '');

    var elStub = root.querySelector('.js-lesson-note-stub');
    var elView = root.querySelector('.js-lesson-note-view');
    var elEdit = root.querySelector('.js-lesson-note-edit');
    var displayEl = root.querySelector('.js-lesson-note-body-display');
    var form = root.querySelector('.js-lesson-note-form');
    var ta = document.getElementById('lesson_note_body');
    var btnEdit = root.querySelector('.js-lesson-note-btn-edit');
    var btnDelete = root.querySelector('.js-lesson-note-btn-delete');
    var btnCancel = root.querySelector('.js-lesson-note-btn-cancel');
    var feedback = root.querySelector('.js-lesson-note-feedback');
    var hint = root.querySelector('.js-lesson-note-edit-hint');

    var hasSaved = root.getAttribute('data-initial-has-saved') === '1';
    var savedBody = (hasSaved && displayEl) ? displayEl.textContent.replace(/\u00a0/g, ' ') : '';

    function hideValidation() {
        var el = root.querySelector('.js-lesson-note-validation');
        if (el) {
            el.textContent = '';
            el.classList.remove('d-block');
            el.classList.add('d-none');
        }
        if (ta) ta.classList.remove('is-invalid');
    }

    function showValidation(msg) {
        var el = root.querySelector('.js-lesson-note-validation');
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

    function postLessonNote(noteVal) {
        if (!form || !saveUrl) {
            return Promise.resolve(null);
        }

        var fd = new FormData(form);
        fd.set('lesson_note_body', noteVal);

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
                    var msg = (payload.errors && payload.errors.lesson_note_body && payload.errors.lesson_note_body[0])
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

    var btnAdd = root.querySelector('.js-lesson-note-btn-add');

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
        btnDelete.addEventListener('click', function () {
            if (!confirm('Usunąć notatkę do tej lekcji?')) return;

            postLessonNote('').then(function (data) {
                if (!data || !Object.prototype.hasOwnProperty.call(data, 'deleted')) return;
                if (data.deleted) {
                    hasSaved = false;
                    savedBody = '';
                    if (displayEl) displayEl.textContent = '';
                    if (ta) ta.value = '';
                    syncSidebarNoteButton(lessonIdStr, '');
                    if (hint) hint.classList.remove('d-none');
                    setMode(false);
                }
                showFeedback(data.message || '', false);
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            postLessonNote(ta ? ta.value : '').then(function (data) {
                if (!data) return;

                if (data.saved && typeof data.body === 'string') {
                    savedBody = data.body;
                    hasSaved = true;
                    if (displayEl) displayEl.textContent = data.body;
                    syncSidebarNoteButton(lessonIdStr, data.body);
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
                    syncSidebarNoteButton(lessonIdStr, '');
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

@push('scripts')
<script>
(function () {
    /* Nie querySelector('.card-body') — pierwszy card-body to sidebar (spis lekcji), bez linków data-time. */
    var cardAnchor = document.querySelector('.online-lesson-embed-slot')
        || document.querySelector('.online-lesson-body');
    var card = cardAnchor ? cardAnchor.closest('.card-body') : null;
    if (!card) {
        return;
    }

    var links = card.querySelectorAll('a[data-time]');
    if (!links.length) {
        return;
    }

    function isYouTubeIframe(ifr) {
        var src = (ifr.getAttribute('src') || '').toLowerCase();
        return src.indexOf('youtube.com/embed') !== -1 || src.indexOf('youtube-nocookie.com/embed') !== -1;
    }

    function isVimeoIframe(ifr) {
        var src = (ifr.getAttribute('src') || '').toLowerCase();
        return src.indexOf('player.vimeo.com/video') !== -1;
    }

    function detectPlatform(ifr) {
        var platform = ifr.getAttribute('data-oc-video-platform') || '';
        if (platform) {
            return platform;
        }
        if (isYouTubeIframe(ifr)) {
            return 'youtube';
        }
        if (isVimeoIframe(ifr)) {
            return 'vimeo';
        }

        return '';
    }

    function ensureIframeId(ifr) {
        if (ifr.id) {
            return ifr.id;
        }
        var id = 'oc-legacy-embed-' + Math.random().toString(36).slice(2, 9);
        ifr.id = id;

        return id;
    }

    /* Legacy NE.pl: iframe w body_html (#ytplayer lub youtube.com/embed) bez rekordu w „Wideo osadzone”. */
    function prepareLegacyBodyIframes() {
        card.querySelectorAll('.online-lesson-body iframe').forEach(function (ifr, idx) {
            var platform = detectPlatform(ifr);
            if (!platform) {
                return;
            }
            ifr.setAttribute('data-oc-video-platform', platform);
            ifr.setAttribute('data-oc-legacy-embed-index', String(idx));
            ensureIframeId(ifr);
        });
    }
    prepareLegacyBodyIframes();

    /* YouTube JS API wymaga, żeby ?origin= było IDENTYCZNE z origin strony — na prod PHP często myli host (www, proxy).
       Nadpisanie przy starcie rozwiązuje seek/spis treści bez zmian konfiguracji serwera. */
    function syncYouTubeIframeParamsForJsApi() {
        var pageOrigin = window.location && window.location.origin ? window.location.origin : '';
        if (!pageOrigin) return;
        card.querySelectorAll('iframe').forEach(function (ifr) {
            if (detectPlatform(ifr) !== 'youtube') {
                return;
            }
            try {
                var raw = ifr.getAttribute('src');
                if (!raw) return;
                var u = new URL(raw, window.location.href);
                var needsUpdate = u.searchParams.get('enablejsapi') !== '1'
                    || u.searchParams.get('origin') !== pageOrigin;
                if (!needsUpdate) {
                    return;
                }
                u.searchParams.set('enablejsapi', '1');
                u.searchParams.set('origin', pageOrigin);
                ifr.src = u.toString();
            } catch (ignore) {}
        });
    }
    syncYouTubeIframeParamsForJsApi();

    card.querySelectorAll('iframe').forEach(function (ifr) {
        if (detectPlatform(ifr) === 'youtube') {
            ifr.addEventListener('load', function () {
                ensureYouTubeListening(ifr);
            });
            if (ifr.contentDocument || ifr.contentWindow) {
                ensureYouTubeListening(ifr);
            }
        }
    });

    function youtubePostCommand(iframe, func, args) {
        if (!iframe || !iframe.contentWindow) {
            return;
        }
        var payload = JSON.stringify({
            event: 'command',
            func: func,
            args: args || [],
        });
        try {
            iframe.contentWindow.postMessage(payload, 'https://www.youtube.com');
        } catch (ignore) {}
        try {
            iframe.contentWindow.postMessage(payload, '*');
        } catch (ignore2) {}
    }

    function ensureYouTubeListening(iframe) {
        if (!iframe || iframe.getAttribute('data-oc-yt-listening') === '1') {
            return;
        }
        if (!iframe.contentWindow) {
            return;
        }
        var payload = JSON.stringify({ event: 'listening', id: iframe.id || 'oc-lesson-embed', channel: 'widget' });
        try {
            iframe.contentWindow.postMessage(payload, 'https://www.youtube.com');
        } catch (ignore) {}
        try {
            iframe.contentWindow.postMessage(payload, '*');
        } catch (ignore2) {}
        iframe.setAttribute('data-oc-yt-listening', '1');
    }

    function seekYouTubeEmbed(iframe, seconds) {
        ensureYouTubeListening(iframe);
        youtubePostCommand(iframe, 'seekTo', [seconds, true]);
        youtubePostCommand(iframe, 'playVideo', []);
    }

    function iframeInSlot(ix) {
        var slot = document.querySelector('.online-lesson-embed-slot[data-oc-embed-index="' + ix + '"]');
        if (!slot) return null;
        return slot.querySelector('iframe[data-oc-video-platform]');
    }

    function legacyIframeInBody(ix) {
        var legacy = card.querySelectorAll('.online-lesson-body iframe[data-oc-video-platform]');
        if (legacy[ix]) {
            return legacy[ix];
        }
        if (ix === 0) {
            var ytplayer = document.getElementById('ytplayer');
            if (ytplayer && ytplayer.tagName === 'IFRAME') {
                var platform = detectPlatform(ytplayer);
                if (platform) {
                    ytplayer.setAttribute('data-oc-video-platform', platform);
                    ensureIframeId(ytplayer);
                    return ytplayer;
                }
            }
        }

        return null;
    }

    function iframeForEmbedIndex(ix) {
        return iframeInSlot(ix) || legacyIframeInBody(ix);
    }

    function parseTimeToSeconds(raw) {
        if (raw == null) return 0;
        var str = String(raw).trim();
        if (!str) return 0;
        if (str.indexOf(':') === -1) {
            var nf = parseFloat(str);
            return isNaN(nf) ? 0 : nf;
        }
        var parts = str.split(':').map(function (p) { return parseInt(p, 10) || 0; });
        if (parts.length === 3) return parts[0] * 3600 + parts[1] * 60 + parts[2];
        if (parts.length === 2) return parts[0] * 60 + parts[1];
        return parts[0] || 0;
    }

    var vimeoApiPromise = null;
    function ensureVimeoApi() {
        if (window.Vimeo && window.Vimeo.Player) return Promise.resolve();
        if (vimeoApiPromise) return vimeoApiPromise;
        vimeoApiPromise = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://player.vimeo.com/api/player.js';
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('vimeo player.js')); };
            document.head.appendChild(s);
        }).catch(function (err) {
            vimeoApiPromise = null;
            return Promise.reject(err);
        });
        return vimeoApiPromise;
    }

    var vimeoPlayerByIframe = new WeakMap();
    function getVimeoPlayer(iframeEl) {
        if (vimeoPlayerByIframe.has(iframeEl)) return vimeoPlayerByIframe.get(iframeEl);
        var p = ensureVimeoApi().then(function () {
            /* global Vimeo */
            return new Vimeo.Player(iframeEl);
        });
        vimeoPlayerByIframe.set(iframeEl, p);
        return p;
    }

    function seekByPlatform(iframe, seconds) {
        var platform = detectPlatform(iframe);
        if (platform === 'youtube') {
            seekYouTubeEmbed(iframe, seconds);
            return Promise.resolve();
        }
        if (platform === 'vimeo') {
            return getVimeoPlayer(iframe).then(function (player) {
                return player.setCurrentTime(seconds).then(function () {
                    try { player.play(); } catch (ignore) {}
                });
            }).catch(function () {});
        }
        return Promise.resolve();
    }

    links.forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var ix = parseInt(a.getAttribute('data-embed-index') || '0', 10);
            var iframe = iframeForEmbedIndex(ix);
            if (!iframe || !detectPlatform(iframe)) {
                return;
            }
            var t = parseTimeToSeconds(a.getAttribute('data-time'));
            seekByPlatform(iframe, t).catch(function () {});
        });
    });
})();
</script>
@endpush

