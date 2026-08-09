@extends('layouts.survey')

@section('title', ($survey->title ?: 'Ankieta po szkoleniu').' — Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Ankieta ewaluacyjna po szkoleniu NODN Platforma Nowoczesnej Edukacji.')

@push('styles')
<style>
    .survey-page {
        --survey-ink: #1a2332;
        --survey-muted: #5c6b7a;
        --survey-line: #d9e2ec;
        --survey-soft: #eef4fa;
        --survey-accent: #0d6efd;
        --survey-accent-soft: #dbeafe;
        --survey-ok: #198754;
        background:
            radial-gradient(1200px 480px at 10% -10%, #cfe2ff 0%, transparent 55%),
            radial-gradient(900px 420px at 100% 0%, #e7f1ff 0%, transparent 50%),
            linear-gradient(180deg, #f5f8fc 0%, #eef2f6 100%);
        min-height: 70vh;
        padding: 2.5rem 0 4rem;
        color: var(--survey-ink);
    }

    .survey-shell {
        max-width: 760px;
        margin: 0 auto;
    }

    .survey-hero {
        background: linear-gradient(135deg, #0b5ed7 0%, #0d6efd 45%, #3d8bfd 100%);
        color: #fff;
        border-radius: 1.25rem;
        padding: 1.75rem 1.75rem 1.5rem;
        box-shadow: 0 16px 40px rgba(13, 110, 253, .22);
        position: relative;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }

    .survey-hero::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -40px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .12);
    }

    .survey-hero::before {
        content: "";
        position: absolute;
        right: 40px;
        bottom: -60px;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .08);
    }

    .survey-kicker {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        font-weight: 700;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 999px;
        padding: .35rem .75rem;
        margin-bottom: .85rem;
    }

    .survey-hero h1 {
        font-size: clamp(1.35rem, 2.5vw, 1.75rem);
        font-weight: 700;
        line-height: 1.25;
        margin: 0 0 .65rem;
        position: relative;
        z-index: 1;
    }

    .survey-hero .course-line {
        opacity: .92;
        font-size: .95rem;
        margin: 0;
        position: relative;
        z-index: 1;
        max-width: 52ch;
    }

    .survey-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: 1rem;
        position: relative;
        z-index: 1;
    }

    .survey-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 999px;
        padding: .3rem .7rem;
        font-size: .82rem;
    }

    .survey-progress-wrap {
        background: #fff;
        border: 1px solid var(--survey-line);
        border-radius: 999px;
        padding: .45rem .7rem .45rem .55rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 16px rgba(26, 35, 50, .04);
    }

    .survey-progress-bar {
        flex: 1;
        height: .45rem;
        background: var(--survey-soft);
        border-radius: 999px;
        overflow: hidden;
    }

    .survey-progress-bar > span {
        display: block;
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, #0d6efd, #3d8bfd);
        border-radius: 999px;
        transition: width .35s ease;
    }

    .survey-progress-label {
        font-size: .8rem;
        color: var(--survey-muted);
        white-space: nowrap;
        min-width: 4.5rem;
        text-align: right;
    }

    .survey-card {
        background: #fff;
        border: 1px solid var(--survey-line);
        border-radius: 1rem;
        padding: 1.35rem 1.35rem 1.15rem;
        margin-bottom: 1rem;
        box-shadow: 0 8px 24px rgba(26, 35, 50, .045);
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .survey-card:focus-within {
        border-color: #9ec5fe;
        box-shadow: 0 10px 28px rgba(13, 110, 253, .1);
    }

    .survey-card-label {
        font-weight: 700;
        font-size: 1.02rem;
        margin-bottom: .35rem;
        line-height: 1.35;
    }

    .survey-card-help {
        color: var(--survey-muted);
        font-size: .88rem;
        margin-bottom: 1rem;
    }

    .survey-section-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--survey-accent);
        margin: 1.5rem 0 .75rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .survey-section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--survey-line);
    }

    .survey-rating {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .5rem;
    }

    .survey-rating input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .survey-rating label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .15rem;
        min-height: 3.4rem;
        border: 1.5px solid var(--survey-line);
        border-radius: .75rem;
        background: #fff;
        cursor: pointer;
        font-weight: 700;
        color: var(--survey-ink);
        transition: transform .15s ease, border-color .15s ease, background .15s ease, color .15s ease, box-shadow .15s ease;
        user-select: none;
    }

    .survey-rating label small {
        font-size: .65rem;
        font-weight: 600;
        color: var(--survey-muted);
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .survey-rating label:hover {
        border-color: #9ec5fe;
        background: var(--survey-accent-soft);
        transform: translateY(-1px);
    }

    .survey-rating input:checked + label {
        border-color: var(--survey-accent);
        background: var(--survey-accent);
        color: #fff;
        box-shadow: 0 8px 18px rgba(13, 110, 253, .28);
    }

    .survey-rating input:checked + label small {
        color: rgba(255, 255, 255, .85);
    }

    .survey-rating input:focus-visible + label {
        outline: 2px solid #9ec5fe;
        outline-offset: 2px;
    }

    .survey-choice {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .survey-choice input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .survey-choice label {
        display: flex;
        align-items: center;
        gap: .75rem;
        border: 1.5px solid var(--survey-line);
        border-radius: .85rem;
        padding: .85rem 1rem;
        cursor: pointer;
        background: #fff;
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        margin: 0;
    }

    .survey-choice label::before {
        content: "";
        width: 1.1rem;
        height: 1.1rem;
        border: 2px solid #adb5bd;
        border-radius: 50%;
        flex-shrink: 0;
        background: #fff;
        box-shadow: inset 0 0 0 0 var(--survey-accent);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .survey-choice.multi label::before {
        border-radius: .3rem;
    }

    .survey-choice label:hover {
        border-color: #9ec5fe;
        background: #f8fbff;
    }

    .survey-choice input:checked + label {
        border-color: var(--survey-accent);
        background: var(--survey-accent-soft);
        box-shadow: 0 0 0 1px rgba(13, 110, 253, .15);
    }

    .survey-choice input:checked + label::before {
        border-color: var(--survey-accent);
        box-shadow: inset 0 0 0 .28rem var(--survey-accent);
    }

    .survey-page .form-control,
    .survey-page .form-select {
        border-radius: .75rem;
        border-color: var(--survey-line);
        padding: .7rem .9rem;
    }

    .survey-page .form-control:focus {
        border-color: #9ec5fe;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .12);
    }

    .survey-avail {
        overflow: auto;
        border: 1px solid var(--survey-line);
        border-radius: .85rem;
    }

    .survey-avail table {
        margin: 0;
        min-width: 560px;
    }

    .survey-avail th,
    .survey-avail td {
        text-align: center;
        vertical-align: middle;
        font-size: .82rem;
        padding: .65rem .4rem;
    }

    .survey-avail thead th {
        background: var(--survey-soft);
        font-weight: 700;
        color: var(--survey-muted);
    }

    .survey-avail tbody th {
        text-align: left;
        padding-left: .85rem;
        font-weight: 600;
        white-space: nowrap;
        background: #fff;
    }

    .survey-avail .form-check-input {
        width: 1.15rem;
        height: 1.15rem;
        margin: 0;
        cursor: pointer;
    }

    .survey-stars {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: .35rem;
    }

    .survey-stars input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .survey-stars label {
        font-size: 1.65rem;
        line-height: 1;
        color: #ced4da;
        cursor: pointer;
        transition: color .15s ease, transform .15s ease;
        margin: 0;
    }

    .survey-stars label:hover,
    .survey-stars label:hover ~ label,
    .survey-stars input:checked ~ label {
        color: #ffc107;
    }

    .survey-stars label:hover {
        transform: scale(1.08);
    }

    .survey-submit-bar {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(8px);
        border: 1px solid var(--survey-line);
        border-radius: 1rem;
        padding: 1rem;
        box-shadow: 0 -8px 28px rgba(26, 35, 50, .08);
        margin-top: 1.25rem;
        z-index: 5;
    }

    .survey-submit-bar .btn-primary {
        border-radius: .8rem;
        padding: .85rem 1.25rem;
        font-weight: 700;
        box-shadow: 0 10px 22px rgba(13, 110, 253, .25);
    }

    .survey-alert {
        border-radius: .9rem;
        border: 0;
    }

    .survey-grid-item {
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px dashed var(--survey-line);
    }

    .survey-grid-item:last-child {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: 0;
    }

    .survey-grid-item .survey-card-label {
        font-size: .95rem;
        font-weight: 600;
        margin-bottom: .65rem;
    }

    @media (max-width: 575.98px) {
        .survey-page { padding-top: 1.25rem; }
        .survey-hero { padding: 1.25rem; border-radius: 1rem; }
        .survey-card { padding: 1.1rem; }
        .survey-rating label { min-height: 3rem; font-size: .95rem; }
        .survey-rating label small { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $courseTitle = $course?->title ? strip_tags(html_entity_decode($course->title, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : null;
    $ratingHints = [5 => 'Tak', 4 => '', 3 => 'Średnio', 2 => '', 1 => 'Nie'];

    $parseGrid = static function (string $text): array {
        if (preg_match('/^(.*?)\s*\[(.+)\]\s*$/u', $text, $m)) {
            return ['main' => trim($m[1]), 'sub' => trim($m[2]), 'is_grid' => true];
        }

        return ['main' => $text, 'sub' => null, 'is_grid' => false];
    };

    $groups = [];
    $buffer = null;
    foreach ($questions as $question) {
        $parsed = $parseGrid($question->question_text);
        if ($question->question_type === 'rating' && $parsed['is_grid']) {
            if ($buffer && $buffer['main'] === $parsed['main']) {
                $buffer['items'][] = ['question' => $question, 'sub' => $parsed['sub']];
            } else {
                if ($buffer) {
                    $groups[] = $buffer;
                }
                $buffer = [
                    'type' => 'rating_grid',
                    'main' => $parsed['main'],
                    'items' => [['question' => $question, 'sub' => $parsed['sub']]],
                ];
            }
            continue;
        }
        if ($buffer) {
            $groups[] = $buffer;
            $buffer = null;
        }
        $groups[] = [
            'type' => 'single',
            'question' => $question,
            'parsed' => $parsed,
        ];
    }
    if ($buffer) {
        $groups[] = $buffer;
    }

    $usesAccountEmail = $usesAccountEmail ?? false;
    $interactiveCount = $questions->count() + (($isAnonymous || $usesAccountEmail) ? 0 : 1);
@endphp

<div class="survey-page">
    <div class="container survey-shell">
        <header class="survey-hero" data-aos="fade-up">
            <div class="survey-kicker">
                <i class="bi bi-clipboard2-check"></i>
                Ankieta po szkoleniu
            </div>
            <h1>{{ $survey->title }}</h1>
            @if($courseTitle)
                <p class="course-line">{{ $courseTitle }}</p>
            @elseif($survey->description)
                <p class="course-line">{{ $survey->description }}</p>
            @endif
            <div class="survey-meta">
                @if($isAnonymous)
                    <span class="survey-chip"><i class="bi bi-shield-lock"></i> Anonimowa</span>
                @else
                    <span class="survey-chip"><i class="bi bi-person-check"></i> Powiązana z udziałem</span>
                @endif
                <span class="survey-chip"><i class="bi bi-clock"></i> ok. 2–3 min</span>
                <span class="survey-chip"><i class="bi bi-stars"></i> Twoja opinia ma znaczenie</span>
            </div>
        </header>

        <div class="survey-progress-wrap" data-aos="fade-up" data-aos-delay="50">
            <div class="survey-progress-bar" aria-hidden="true"><span id="surveyProgressFill"></span></div>
            <div class="survey-progress-label"><span id="surveyProgressPct">0</span>%</div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger survey-alert shadow-sm" data-aos="fade-up">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Sprawdź formularz</div>
                <ul class="mb-0 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('survey.gate.submit', ['token' => $token]) }}" id="surveyNativeForm"
              class="needs-validation" novalidate>
            @csrf
            <div class="d-none" aria-hidden="true">
                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            @unless($isAnonymous || $usesAccountEmail)
                <div class="survey-card" data-aos="fade-up">
                    <div class="survey-card-label">Twój adres e-mail <span class="text-danger">*</span></div>
                    <p class="survey-card-help mb-2">Potrzebny, aby powiązać odpowiedź z Twoim udziałem w szkoleniu.</p>
                    <input type="email" class="form-control" id="respondent_email" name="respondent_email"
                           value="{{ old('respondent_email', $prefillEmail) }}" required placeholder="jan.kowalski@szkola.pl">
                </div>
            @endunless

            @foreach($groups as $group)
                @if($group['type'] === 'rating_grid')
                    <div class="survey-section-title" data-aos="fade-up">
                        <i class="bi bi-bar-chart-steps"></i> {{ $group['main'] }}
                    </div>
                    <div class="survey-card" data-aos="fade-up">
                        <p class="survey-card-help">Oceń w skali 1–5 (5 = zdecydowanie tak / bardzo dobrze).</p>
                        @foreach($group['items'] as $item)
                            @php
                                $question = $item['question'];
                                $qid = $question->id;
                                $name = 'answers['.$qid.']';
                                $old = old('answers.'.$qid);
                            @endphp
                            <div class="survey-grid-item" data-survey-field>
                                <div class="survey-card-label">
                                    {{ $item['sub'] }}
                                    <span class="text-danger">*</span>
                                </div>
                                <div class="survey-rating" role="radiogroup" aria-label="{{ $item['sub'] }}">
                                    @for($i = 5; $i >= 1; $i--)
                                        <div>
                                            <input type="radio" name="{{ $name }}" id="q{{ $qid }}_{{ $i }}" value="{{ $i }}"
                                                @checked((string) $old === (string) $i) required>
                                            <label for="q{{ $qid }}_{{ $i }}">
                                                {{ $i }}
                                                @if(!empty($ratingHints[$i]))
                                                    <small>{{ $ratingHints[$i] }}</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @php
                        $question = $group['question'];
                        $parsed = $group['parsed'];
                        $qid = $question->id;
                        $name = 'answers['.$qid.']';
                        $old = old('answers.'.$qid);
                        $label = $parsed['sub'] ?: $parsed['main'];
                    @endphp

                    @if(in_array($question->question_type, ['text', 'availability'], true))
                        <div class="survey-section-title" data-aos="fade-up">
                            <i class="bi bi-{{ $question->question_type === 'availability' ? 'calendar-week' : 'chat-left-text' }}"></i>
                            {{ \Illuminate\Support\Str::limit($label, 48) }}
                        </div>
                    @endif

                    <div class="survey-card" data-aos="fade-up" data-survey-field>
                        <div class="survey-card-label">
                            {{ $label }}
                            @if($question->question_type === 'rating')
                                <span class="text-danger">*</span>
                            @endif
                        </div>

                        @if($question->question_type === 'rating')
                            <p class="survey-card-help">Skala 1–5 (5 = zdecydowanie tak / bardzo dobrze).</p>
                            <div class="survey-rating" role="radiogroup" aria-label="{{ $label }}">
                                @for($i = 5; $i >= 1; $i--)
                                    <div>
                                        <input type="radio" name="{{ $name }}" id="q{{ $qid }}_{{ $i }}" value="{{ $i }}"
                                            @checked((string) $old === (string) $i) required>
                                        <label for="q{{ $qid }}_{{ $i }}">
                                            {{ $i }}
                                            @if(!empty($ratingHints[$i]))
                                                <small>{{ $ratingHints[$i] }}</small>
                                            @endif
                                        </label>
                                    </div>
                                @endfor
                            </div>
                        @elseif($question->question_type === 'text')
                            <textarea class="form-control" name="{{ $name }}" rows="3" placeholder="Twoja odpowiedź…">{{ $old }}</textarea>
                        @elseif($question->question_type === 'single_choice')
                            <div class="survey-choice">
                                @foreach(($question->options ?? []) as $opt)
                                    <div>
                                        <input type="radio" name="{{ $name }}" id="q{{ $qid }}_{{ md5($opt) }}" value="{{ $opt }}"
                                            @checked($old === $opt)>
                                        <label for="q{{ $qid }}_{{ md5($opt) }}">{{ $opt }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($question->question_type === 'multiple_choice')
                            <div class="survey-choice multi">
                                @foreach(($question->options ?? []) as $opt)
                                    <div>
                                        <input type="checkbox" name="{{ $name }}[]" id="q{{ $qid }}_{{ md5($opt) }}" value="{{ $opt }}"
                                            @checked(is_array($old) && in_array($opt, $old, true))>
                                        <label for="q{{ $qid }}_{{ md5($opt) }}">{{ $opt }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($question->question_type === 'availability')
                            @php
                                $days = $question->options['days'] ?? [];
                                $slots = $question->options['slots'] ?? [];
                                $oldArr = is_array($old) ? $old : [];
                            @endphp
                            <p class="survey-card-help">Zaznacz dogodne dni i pory — możesz wybrać wiele pól.</p>
                            <div class="survey-avail">
                                <table class="table table-borderless mb-0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            @foreach($slots as $slot)
                                                <th>{{ $slot }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($days as $day)
                                            <tr>
                                                <th>{{ $day }}</th>
                                                @foreach($slots as $slot)
                                                    @php $val = $day.'|'.$slot; @endphp
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" name="{{ $name }}[]" value="{{ $val }}"
                                                            @checked(in_array($val, $oldArr, true))
                                                            aria-label="{{ $day }}, {{ $slot }}">
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <input type="text" class="form-control" name="{{ $name }}" value="{{ $old }}">
                        @endif
                    </div>
                @endif
            @endforeach

            <div class="survey-submit-bar" data-aos="fade-up">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                    <div class="small text-muted flex-grow-1">
                        <i class="bi bi-info-circle me-1"></i>
                        Wypełnienie zajmie chwilę — dziękujemy za feedback dla NODN.
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send me-1"></i>
                        Wyślij ankietę
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('surveyNativeForm');
    const fill = document.getElementById('surveyProgressFill');
    const pctEl = document.getElementById('surveyProgressPct');
    if (!form || !fill || !pctEl) return;

    const fields = Array.from(form.querySelectorAll('[data-survey-field]'));

    function fieldFilled(el) {
        const file = el.querySelector('input[type="file"]');
        if (file && file.files && file.files.length) return true;
        const radios = el.querySelectorAll('input[type="radio"]');
        if (radios.length) {
            return Array.from(radios).some(r => r.checked);
        }
        const checks = el.querySelectorAll('input[type="checkbox"]');
        if (checks.length) {
            return Array.from(checks).some(c => c.checked);
        }
        const inputs = el.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]), textarea, select');
        return Array.from(inputs).some(i => String(i.value || '').trim() !== '');
    }

    function updateProgress() {
        const total = Math.max(fields.length, 1);
        const done = fields.filter(fieldFilled).length;
        const pct = Math.round((done / total) * 100);
        fill.style.width = pct + '%';
        pctEl.textContent = String(pct);
    }

    form.addEventListener('change', updateProgress);
    form.addEventListener('input', updateProgress);
    updateProgress();
})();
</script>
@endpush
