@extends('layouts.survey')

@section('title', 'Podziel się rekomendacją — Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Opcjonalna rekomendacja po ankiecie szkoleniowej NODN.')

@push('styles')
<style>
    :root {
        --rec-ink: #1a2332;
        --rec-muted: #5c6b7a;
        --rec-line: #d9e2ec;
        --rec-accent: #0d6efd;
        --rec-accent-soft: #e8f1ff;
    }

    .rec-page {
        background:
            radial-gradient(900px 420px at 12% -8%, #cfe2ff 0%, transparent 55%),
            radial-gradient(700px 380px at 90% 10%, #d8f3e7 0%, transparent 50%),
            linear-gradient(180deg, #f5f8fc 0%, #eef2f6 100%);
        min-height: calc(100vh - 72px);
        padding: 1.75rem 1rem 3rem;
    }

    .rec-shell { max-width: 720px; margin: 0 auto; }

    .rec-hero {
        text-align: center;
        padding: 1.5rem 0 1.25rem;
    }

    .rec-hero .badge-soft {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: #e7f6ee;
        color: #146c43;
        border-radius: 999px;
        padding: .4rem .9rem;
        font-size: .82rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .rec-hero h1 {
        font-size: clamp(1.55rem, 3.5vw, 2rem);
        font-weight: 800;
        color: var(--rec-ink);
        margin-bottom: .75rem;
        letter-spacing: -.02em;
    }

    .rec-hero .lead {
        color: var(--rec-muted);
        font-size: 1.05rem;
        max-width: 36rem;
        margin: 0 auto 1rem;
        line-height: 1.55;
    }

    .rec-hero .course-line {
        color: var(--rec-ink);
        font-weight: 600;
        font-size: .95rem;
    }

    .rec-card {
        background: #fff;
        border: 1px solid var(--rec-line);
        border-radius: 1.15rem;
        padding: 1.35rem 1.25rem;
        box-shadow: 0 12px 32px rgba(26, 35, 50, .06);
        margin-bottom: 1rem;
    }

    .rec-card-label {
        font-weight: 700;
        color: var(--rec-ink);
        margin-bottom: .35rem;
    }

    .rec-help {
        color: var(--rec-muted);
        font-size: .9rem;
        margin-bottom: .85rem;
    }

    .rec-stars {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: .2rem;
    }

    .rec-stars input { position: absolute; opacity: 0; pointer-events: none; }
    .rec-stars label {
        font-size: 1.65rem;
        color: #ced4da;
        cursor: pointer;
        transition: color .15s ease, transform .15s ease;
        margin: 0;
    }
    .rec-stars label:hover,
    .rec-stars label:hover ~ label,
    .rec-stars input:checked ~ label { color: #ffc107; }
    .rec-stars label:hover { transform: scale(1.08); }

    .survey-avatar-group-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--rec-muted);
        margin: .85rem 0 .5rem;
    }

    .survey-avatar-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
    }

    @media (min-width: 768px) {
        .survey-avatar-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    }

    @media (max-width: 575.98px) {
        .survey-avatar-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .survey-avatar-grid input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .survey-avatar-grid label {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        cursor: pointer;
        border: 2px solid var(--rec-line);
        border-radius: .85rem;
        padding: .45rem;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .survey-avatar-grid label img,
    .survey-avatar-grid label .survey-avatar-none-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        background: #eef2f6;
    }

    .survey-avatar-grid label .survey-avatar-none-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #6c757d;
        color: #fff;
        font-size: .95rem;
        font-weight: 700;
        letter-spacing: .02em;
        line-height: 1;
        user-select: none;
    }

    .survey-avatar-grid input:checked + label .survey-avatar-none-icon {
        background: var(--rec-accent);
        color: #fff;
    }

    .survey-avatar-grid label:hover {
        border-color: #9ec5fe;
        transform: translateY(-1px);
    }

    .survey-avatar-grid input:checked + label {
        border-color: var(--rec-accent);
        box-shadow: 0 0 0 2px rgba(13, 110, 253, .18);
        background: var(--rec-accent-soft);
    }

    .survey-avatar-preview {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--rec-line);
        background: #eef2f6;
        flex-shrink: 0;
    }

    .survey-avatar-upload-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: nowrap;
    }

    @media (max-width: 575.98px) {
        .survey-avatar-upload-row {
            flex-wrap: wrap;
        }
    }

    .survey-avatar-upload-row .survey-avatar-upload-fields {
        flex: 1 1 auto;
        min-width: 0;
    }

    .survey-avatar-preview-empty {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-style: dashed;
        color: #adb5bd;
        font-size: 1.6rem;
        background: #f8f9fa;
        cursor: pointer;
        padding: 0;
        margin: 0;
        appearance: none;
        -webkit-appearance: none;
    }

    .survey-avatar-preview-empty:hover {
        border-color: #9ec5fe;
        color: var(--rec-accent);
        background: var(--rec-accent-soft);
    }

    .survey-avatar-preview[role="button"] {
        cursor: pointer;
    }

    .survey-avatar-preview-empty.is-hidden,
    .survey-avatar-preview.is-hidden {
        display: none !important;
    }

    .survey-avatar-upload-block {
        margin-bottom: 1.1rem;
        padding-bottom: 1.1rem;
        border-bottom: 1px dashed var(--rec-line);
    }

    .survey-avatar-upload-block .survey-avatar-upload-label {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--rec-muted);
        margin: 0 0 .55rem;
    }

    .rec-actions {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        margin-top: 1.25rem;
    }

    @media (min-width: 576px) {
        .rec-actions {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .rec-actions .btn-primary {
        border-radius: .85rem;
        font-weight: 700;
        padding: .85rem 1.35rem;
        box-shadow: 0 10px 22px rgba(13, 110, 253, .25);
    }

    .rec-skip {
        color: var(--rec-muted);
        font-weight: 600;
        text-decoration: none;
    }

    .rec-skip:hover { color: var(--rec-ink); text-decoration: underline; }
</style>
@endpush

@section('content')
@php
    $avatarPreset = old('avatar_preset', 'none');
    $groupedAvatars = $avatarPresetsByGroup ?? [];
@endphp

<div class="rec-page">
    <div class="rec-shell">
        <header class="rec-hero" data-aos="fade-up">
            <div class="badge-soft">
                <i class="bi bi-check2-circle"></i>
                Ankieta zapisana
            </div>
            <h1>Chcesz zostawić krótką rekomendację?</h1>
            <p class="lead">
                To opcjonalny krok. Twoja opinia — po akceptacji — może pomóc innym nauczycielom
                na stronie głównej pnedu.pl.
            </p>
            @if(!empty($courseTitle))
                <p class="course-line">{{ $courseTitle }}</p>
            @endif
        </header>

        @if($errors->any())
            <div class="alert alert-danger shadow-sm" data-aos="fade-up">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Sprawdź formularz</div>
                <ul class="mb-0 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('survey.gate.recommend.submit', ['token' => $token]) }}"
              id="surveyRecommendationForm" enctype="multipart/form-data" data-aos="fade-up">
            @csrf
            <div class="d-none" aria-hidden="true">
                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="rec-card">
                <div class="rec-card-label">Ocena ogólna <span class="text-danger">*</span></div>
                <p class="rec-help">Ile gwiazdek wystawiasz szkoleniu?</p>
                <div class="rec-stars" role="radiogroup" aria-label="Ocena ogólna" aria-required="true">
                    @for($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" id="t_rating_{{ $i }}" value="{{ $i }}" required
                            @checked((string) old('rating') === (string) $i)>
                        <label for="t_rating_{{ $i }}" title="{{ $i }}/5"><i class="bi bi-star-fill"></i></label>
                    @endfor
                </div>
            </div>

            <div class="rec-card">
                <div class="rec-card-label">Treść rekomendacji <span class="text-danger">*</span></div>
                <p class="rec-help">2–4 zdania wystarczą. Napisz, co było najbardziej wartościowe.</p>
                <textarea class="form-control" name="quote" id="quote" rows="4" maxlength="1000" required
                          placeholder="Np. Szkolenie było bardzo profesjonalne i konkretne…">{{ old('quote') }}</textarea>
            </div>

            <div class="rec-card">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="author_name">Imię i nazwisko <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="author_name" name="author_name" required maxlength="120"
                               value="{{ old('author_name') }}" placeholder="Anna Nowak" autocomplete="name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="author_role">Stanowisko / rola</label>
                        <input type="text" class="form-control" id="author_role" name="author_role" maxlength="120"
                               value="{{ old('author_role') }}" placeholder="Nauczycielka">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="author_city">Miasto</label>
                        <input type="text" class="form-control" id="author_city" name="author_city" maxlength="80"
                               value="{{ old('author_city') }}" placeholder="Kraków">
                    </div>
                </div>
            </div>

            <div class="rec-card">
                <div class="rec-card-label">Zdjęcie / awatar <span class="text-muted fw-normal">(opcjonalnie)</span></div>
                <p class="rec-help">
                    Opcjonalnie dodaj własne zdjęcie. Jeśli go nie wgrasz, możesz skorzystać z gotowego awatara poniżej —
                    albo nic nie wybierać (przy opinii pokażemy same inicjały).
                </p>

                <div class="survey-avatar-upload-block">
                    <div class="survey-avatar-upload-label">Własne zdjęcie</div>
                    <div class="survey-avatar-upload-row">
                        <button type="button" class="survey-avatar-preview survey-avatar-preview-empty"
                                id="avatarUploadPlaceholder" title="Wybierz zdjęcie" aria-label="Wybierz zdjęcie">
                            <i class="bi bi-camera" aria-hidden="true"></i>
                        </button>
                        <img src="" alt="Podgląd wybranego zdjęcia — kliknij, aby zmienić" class="survey-avatar-preview is-hidden"
                             id="avatarUploadPreview" role="button" tabindex="0" title="Zmień zdjęcie">
                        <div class="survey-avatar-upload-fields">
                            <input type="file" class="form-control" name="avatar" id="testimonial_avatar"
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text mb-0">JPG, PNG lub WebP, max 2&nbsp;MB. Najlepiej kwadratowe zdjęcie twarzy.</div>
                        </div>
                    </div>
                </div>

                {{-- Ukryte „brak awatara” — wybór kasowany drugim kliknięciem w awatar --}}
                <input type="radio" name="avatar_preset" id="avatar_preset_none" value="none"
                       class="visually-hidden" tabindex="-1" aria-hidden="true"
                       @checked($avatarPreset === 'none' || $avatarPreset === '')>

                <p class="rec-help mb-2">
                    Nie chcesz własnego zdjęcia? Wybierz jeden z gotowych awatarów poniżej.
                    Ponowne kliknięcie w wybrany awatar go odznacza.
                </p>
                @foreach($groupedAvatars as $groupName => $presets)
                    <div class="survey-avatar-group-title">{{ $groupName }}</div>
                    <div class="survey-avatar-grid mb-2">
                        @foreach($presets as $preset)
                            <div>
                                <input type="radio" name="avatar_preset" id="avatar_preset_{{ $preset['key'] }}"
                                       value="{{ $preset['key'] }}" class="js-avatar-preset"
                                       @checked($avatarPreset === $preset['key'])>
                                <label for="avatar_preset_{{ $preset['key'] }}" title="{{ $preset['label'] }}">
                                    <img src="{{ $preset['url'] }}" alt="{{ $preset['label'] }}">
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                <p class="form-text mb-0">
                    Awatary: styl <a href="https://www.dicebear.com/styles/avataaars/" target="_blank" rel="noopener">DiceBear Avataaars</a>
                    (Pablo Stanley) — darmowe do użytku.
                </p>
            </div>

            <p class="rec-help text-center mb-3" data-aos="fade-up">
                Wysyłając rekomendację, wyrażasz zgodę na przetwarzanie podanych danych
                (treść opinii, ocena, imię i nazwisko, stanowisko, miasto oraz — jeśli je dodasz — zdjęcie lub awatar)
                i na publikację tej opinii na stronie pnedu.pl po akceptacji organizatora.
                Zgodę możesz wycofać, pisząc na
                <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>.
                Szczegóły:
                <a href="{{ route('rodo') }}" target="_blank" rel="noopener">klauzula RODO</a>
                oraz
                <a href="{{ route('polityka-prywatnosci') }}" target="_blank" rel="noopener">Polityka prywatności</a>.
            </p>

            <div class="rec-actions">
                <a href="{{ route('survey.gate.recommend.skip', ['token' => $token]) }}" class="rec-skip">
                    Pomiń — dziękuję, wystarczy ankieta
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-chat-quote me-1"></i>
                    Wyślij rekomendację
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('surveyRecommendationForm');
    if (!form) return;

    const fileInput = document.getElementById('testimonial_avatar');
    const preview = document.getElementById('avatarUploadPreview');
    const placeholder = document.getElementById('avatarUploadPlaceholder');
    const noneRadio = document.getElementById('avatar_preset_none');

    function resetUploadPreview() {
        if (preview) {
            if (preview.src && preview.src.indexOf('blob:') === 0) {
                URL.revokeObjectURL(preview.src);
            }
            preview.removeAttribute('src');
            preview.classList.add('is-hidden');
        }
        if (placeholder) {
            placeholder.classList.remove('is-hidden');
        }
    }

    function clearFileSelection() {
        if (fileInput) {
            fileInput.value = '';
        }
        resetUploadPreview();
    }

    function selectNonePreset() {
        if (noneRadio) {
            noneRadio.checked = true;
        }
    }

    // Ponowne kliknięcie wybranego awatara → odznaczenie (same inicjały przy opinii).
    form.querySelectorAll('.js-avatar-preset').forEach(function (radio) {
        const label = form.querySelector('label[for="' + radio.id + '"]');
        if (!label) return;

        label.addEventListener('click', function (e) {
            if (!radio.checked) {
                clearFileSelection();
                return;
            }
            e.preventDefault();
            radio.checked = false;
            selectNonePreset();
            clearFileSelection();
        });
    });

    form.querySelectorAll('.js-avatar-preset').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (radio.checked) {
                clearFileSelection();
            }
        });
    });

    function openFilePicker() {
        if (fileInput) fileInput.click();
    }

    if (placeholder) {
        placeholder.addEventListener('click', openFilePicker);
    }
    if (preview) {
        preview.addEventListener('click', openFilePicker);
        preview.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openFilePicker();
            }
        });
    }

    if (fileInput && preview) {
        fileInput.addEventListener('change', function () {
            const file = fileInput.files && fileInput.files[0];
            if (!file) {
                resetUploadPreview();
                return;
            }
            if (preview.src && preview.src.indexOf('blob:') === 0) {
                URL.revokeObjectURL(preview.src);
            }
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('is-hidden');
            if (placeholder) placeholder.classList.add('is-hidden');
            selectNonePreset();
        });
    }
})();
</script>
@endpush
