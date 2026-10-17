<div class="mb-3">
    <label for="inquiry_institution_type" class="form-label">Rodzaj placówki <span class="text-danger">*</span></label>
    <select name="institution_type" id="inquiry_institution_type" class="form-select @error('institution_type') is-invalid @enderror" required>
        <option value="">Wybierz rodzaj placówki…</option>
        @foreach(\App\Models\ClosedTrainingCase::institutionTypeOptions() as $value => $label)
            <option value="{{ $value }}" @selected(old('institution_type') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('institution_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3" id="inquiry_institution_type_other_wrap" @if(old('institution_type') !== \App\Models\ClosedTrainingCase::INSTITUTION_OTHER) hidden @endif>
    <label for="inquiry_institution_type_other" class="form-label">Jaki rodzaj placówki? <span class="text-danger">*</span></label>
    <input type="text" name="institution_type_other" id="inquiry_institution_type_other" class="form-control @error('institution_type_other') is-invalid @enderror" value="{{ old('institution_type_other') }}" placeholder="np. poradnia psychologiczno-pedagogiczna" @disabled(old('institution_type') !== \App\Models\ClosedTrainingCase::INSTITUTION_OTHER)>
    @error('institution_type_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<script>
    (function () {
        const select = document.getElementById('inquiry_institution_type');
        const wrap = document.getElementById('inquiry_institution_type_other_wrap');
        const input = document.getElementById('inquiry_institution_type_other');
        if (!select || !wrap || !input) {
            return;
        }

        const sync = function () {
            const other = select.value === 'other';
            wrap.hidden = !other;
            input.required = other;
            input.disabled = !other;
        };

        select.addEventListener('change', function () {
            if (select.value !== 'other') {
                input.value = '';
            }
            sync();
        });
        sync();
    })();
</script>
