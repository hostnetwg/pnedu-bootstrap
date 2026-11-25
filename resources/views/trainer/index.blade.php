@extends('layouts.trainer')

@section('title', 'Zapytania')

@section('content')
    @php
        $months = [1 => 'sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paź', 'lis', 'gru'];
    @endphp
    <section class="trainer-sheet">
        <header class="trainer-sheet__head">
            <div>
                <h1 class="h4 mb-1">Zapytania</h1>
                <p class="text-muted mb-0">Szkolenia przekazane do ustalenia.</p>
            </div>
            <span class="trainer-sheet__count">{{ $cases->count() }}</span>
        </header>
        @if($cases->isEmpty())
            <div class="trainer-empty">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                Nie masz udostępnionych zapytań.
            </div>
        @else
            @foreach($cases as $case)
                @php
                    $inquiredAt = ($case->inquired_at ?? $case->created_at)?->timezone('Europe/Warsaw');
                @endphp
                <a href="{{ route('trainer.cases.show', $case) }}" class="trainer-row">
                    <span class="trainer-date">
                        <strong>{{ $inquiredAt?->format('d') ?? '—' }}</strong>
                        <small>{{ $inquiredAt ? $months[(int) $inquiredAt->format('n')] : '' }}</small>
                    </span>
                    <span class="trainer-row__body">
                        <span class="trainer-row__title">{{ trim((string) $case->topic) !== '' ? $case->topic : 'Temat nie podany' }}</span>
                        <span class="small text-muted">{{ $case->institution ?: 'Placówka nie podana' }}</span>
                    </span>
                    @if($case->trainer_declined_at)
                        <span class="badge text-bg-warning">Rezygnacja</span>
                    @elseif($case->start_date)
                        <span class="badge text-bg-success">{{ $case->start_date->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</span>
                    @else
                        <span class="badge text-bg-light border">Termin do ustalenia</span>
                    @endif
                </a>
            @endforeach
        @endif
    </section>
@endsection
