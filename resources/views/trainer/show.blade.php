@extends('layouts.trainer')

@section('title', 'Zapytanie')

@section('content')
    @php
        $format = match ($case->preferred_format) {
            'online' => 'Online',
            'onsite' => 'Stacjonarnie',
            'to_discuss' => 'Do ustalenia',
            default => 'Nie podano',
        };
        $startValue = $case->start_date
            ? $case->start_date->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
            : '';
    @endphp

    <p class="mb-3"><a href="{{ route('trainer.index') }}">Wróć do listy</a></p>
    <h1 class="h3">{{ trim((string) $case->topic) !== '' ? $case->topic : 'Temat nie podany' }}</h1>

    <div class="card mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Data zapytania</dt>
                <dd class="col-sm-9">
                    @php
                        $inquiredAt = $case->inquired_at ?? $case->created_at;
                    @endphp
                    {{ $inquiredAt?->timezone('Europe/Warsaw')->format('d.m.Y') ?? 'Nie podano' }}
                </dd>
                <dt class="col-sm-3">Forma</dt>
                <dd class="col-sm-9">{{ $format }}</dd>
                <dt class="col-sm-3">Placówka</dt>
                <dd class="col-sm-9">{{ $case->institution ?: 'Nie podano' }}</dd>
                <dt class="col-sm-3">Rodzaj placówki</dt>
                <dd class="col-sm-9">{{ \App\Models\ClosedTrainingCase::formatInstitutionType($case->institution_type, $case->institution_type_other) }}</dd>
                <dt class="col-sm-3">Osoba</dt>
                <dd class="col-sm-9">{{ $case->contact_name }}</dd>
                <dt class="col-sm-3">E-mail</dt>
                <dd class="col-sm-9">{{ $case->contact_email ?: 'Nie podano' }}</dd>
                <dt class="col-sm-3">Telefon</dt>
                <dd class="col-sm-9">
                    @php
                        $phoneLabel = \App\Support\PhoneDisplay::format($case->contact_phone);
                        $phoneHref = \App\Support\PhoneDisplay::telHref($case->contact_phone);
                    @endphp
                    @if($phoneLabel !== '' && $phoneHref)
                        <a href="{{ $phoneHref }}">{{ $phoneLabel }}</a>
                    @else
                        Nie podano
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    @if(filled($case->trainer_panel_message))
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning-subtle">Wiadomość od administratora</div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;">{{ $case->trainer_panel_message }}</p>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Treść zapytania</div>
        <div class="card-body">
            <p class="mb-0" style="white-space: pre-wrap;">{{ $case->message }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Termin szkolenia</div>
        <div class="card-body">
            @if($locked)
                <p class="mb-0">Termin tej sprawy jest już przy szkoleniu. Zmianę robi administrator.</p>
                @if($case->start_date)
                    <p class="mb-0 mt-2">Zapisany początek: {{ $case->start_date->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
                    @if($case->trainer_schedule_is_test)
                        <div class="fw-semibold" style="color: #dc3545;">DATA TESTOWA</div>
                    @endif
                @endif
                @if(filled($case->trainer_note))
                    <p class="mb-1 mt-3 fw-semibold">Notatka</p>
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $case->trainer_note }}</p>
                @endif
            @else
                @php
                    $scheduleEstablished = $case->start_date !== null;
                    $scheduleFormOpen = ! $scheduleEstablished || $errors->has('start_at') || $errors->has('trainer_note');
                    $establishedLabel = $scheduleEstablished
                        ? $case->start_date->timezone(config('app.timezone'))->format($case->schedule_time_known ? 'd.m.Y H:i' : 'd.m.Y')
                        : null;
                @endphp
                @if($scheduleEstablished)
                    <div id="trainer-schedule-summary" class="{{ $scheduleFormOpen ? 'd-none' : '' }}">
                        <p class="mb-2">Termin <strong>{{ $establishedLabel }}</strong> ustalony.</p>
                        @if($case->trainer_schedule_is_test)
                            <div class="fw-semibold mb-2" style="color: #dc3545;">DATA TESTOWA</div>
                        @endif
                        @if(filled($case->trainer_note))
                            <p class="mb-1 fw-semibold">Notatka</p>
                            <p class="mb-3" style="white-space: pre-wrap;">{{ $case->trainer_note }}</p>
                        @endif
                        <button type="button" class="btn btn-outline-primary btn-sm" id="trainer-schedule-change">Zmień</button>
                    </div>
                @endif
                <form method="POST" action="{{ route('trainer.cases.schedule', $case) }}" id="trainer-schedule-form" class="{{ $scheduleFormOpen ? '' : 'd-none' }}">
                    @csrf
                    @if(! $scheduleEstablished)
                        <p>Po rozmowie z zamawiającym wpisz datę i godzinę. Zaznaczony test zapisuje termin tak samo. W historii sprawy i pod datą w ustaleniach widać czerwony dopisek DATA TESTOWA.</p>
                    @else
                        <p>Wpisz nowy termin. Zapis wyśle zmianę do administratora.</p>
                    @endif
                    <div class="form-check mb-3">
                        <input type="hidden" name="schedule_test" value="0">
                        <input class="form-check-input" type="checkbox" id="schedule_test" name="schedule_test" value="1" @checked(old('schedule_test', (! $scheduleEstablished || $case->trainer_schedule_is_test) ? '1' : '0') == '1')>
                        <label class="form-check-label" for="schedule_test">Test ustalania szkoleń dla rad pedagogicznych</label>
                    </div>
                    <div class="mb-3">
                        <label for="start_at" class="form-label">Początek</label>
                        <input type="datetime-local" class="form-control @error('start_at') is-invalid @enderror" id="start_at" name="start_at" value="{{ old('start_at', $startValue) }}" required>
                        @error('start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="trainer_note" class="form-label">Notatka</label>
                        <textarea class="form-control @error('trainer_note') is-invalid @enderror" id="trainer_note" name="trainer_note" rows="4" maxlength="3000">{{ old('trainer_note', $case->trainer_note) }}</textarea>
                        @error('trainer_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Ustalenia z rozmowy. Widzi je administrator przy terminie.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ $scheduleEstablished ? 'Zapisz zmianę' : 'Zapisz termin' }}</button>
                    @if($scheduleEstablished)
                        <button type="button" class="btn btn-link" id="trainer-schedule-cancel">Anuluj</button>
                    @endif
                </form>
                <script>
                    document.getElementById('trainer-schedule-change')?.addEventListener('click', function () {
                        document.getElementById('trainer-schedule-summary')?.classList.add('d-none');
                        document.getElementById('trainer-schedule-form')?.classList.remove('d-none');
                    });
                    document.getElementById('trainer-schedule-cancel')?.addEventListener('click', function () {
                        document.getElementById('trainer-schedule-form')?.classList.add('d-none');
                        document.getElementById('trainer-schedule-summary')?.classList.remove('d-none');
                    });
                </script>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Prowadzenie szkolenia</div>
        <div class="card-body">
            @php
                $declined = (string) old('trainer_declined', $case->trainer_declined_at ? '1' : '') === '1';
            @endphp
            <form method="POST" action="{{ route('trainer.cases.decline', $case) }}" id="trainer-decline-form">
                @csrf
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="trainer_declined" name="trainer_declined" value="1" @checked($declined)>
                    <label class="form-check-label" for="trainer_declined">Nie chcę lub nie mogę poprowadzić tego szkolenia</label>
                </div>
                <div class="mb-3 {{ $declined ? '' : 'd-none' }}" id="trainer-decline-reason">
                    <label for="trainer_decline_reason" class="form-label">Dlaczego rezygnujesz</label>
                    <textarea class="form-control @error('trainer_decline_reason') is-invalid @enderror" id="trainer_decline_reason" name="trainer_decline_reason" rows="3" maxlength="500">{{ old('trainer_decline_reason', $case->trainer_decline_reason) }}</textarea>
                    @error('trainer_decline_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Krótko. Widzi to administrator.</div>
                </div>
                <button type="submit" class="btn btn-outline-danger">Zapisz</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('trainer_declined')?.addEventListener('change', function () {
            document.getElementById('trainer-decline-reason')?.classList.toggle('d-none', !this.checked);
        });
    </script>
@endsection
