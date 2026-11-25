@extends('layouts.trainer')

@section('title', 'Kalendarz')

@section('content')
    @php
        $months = [1 => 'sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paź', 'lis', 'gru'];
        $monthNames = [1 => 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
        $grouped = $courses->groupBy(function ($course) {
            return $course->start_date?->timezone('Europe/Warsaw')->format('Y-m') ?? 'brak';
        });
    @endphp
    <section class="trainer-sheet">
        <header class="trainer-sheet__head">
            <div>
                <h1 class="h4 mb-1">Kalendarz</h1>
                <p class="text-muted mb-0">Zaplanowane szkolenia, które jeszcze się nie skończyły.</p>
            </div>
            <span class="trainer-sheet__count">{{ $courses->count() }}</span>
        </header>
        @if($courses->isEmpty())
            <div class="trainer-empty">
                <i class="bi bi-calendar-x" aria-hidden="true"></i>
                Nie masz zaplanowanych szkoleń.
            </div>
        @else
            @foreach($grouped as $coursesInMonth)
                @php
                    $monthStart = $coursesInMonth->first()->start_date?->timezone('Europe/Warsaw');
                @endphp
                <div class="trainer-month">
                    {{ $monthStart ? $monthNames[(int) $monthStart->format('n')].' '.$monthStart->format('Y') : 'Bez terminu' }}
                </div>
                @foreach($coursesInMonth as $course)
                    @php
                        $start = $course->start_date?->timezone('Europe/Warsaw');
                        $end = $course->end_date?->timezone('Europe/Warsaw');
                        $when = $start?->format('H:i') ?? '';
                        if ($start && $end) {
                            $when .= '–'.$end->format($start->isSameDay($end) ? 'H:i' : 'd.m.Y H:i');
                        }
                        $place = $locations->get($course->id);
                        $placeLabel = trim(implode(', ', array_filter([
                            $place->location_name ?? null,
                            $place->post_office ?? null,
                        ])));
                    @endphp
                    <div class="trainer-row">
                        <span class="trainer-date">
                            <strong>{{ $start?->format('d') ?? '—' }}</strong>
                            <small>{{ $start ? $months[(int) $start->format('n')] : '' }}</small>
                        </span>
                        <span class="trainer-row__body">
                            <span class="trainer-row__title">{{ $course->title }}</span>
                            <span class="small text-muted">
                                {{ $when }}
                                @if($course->type !== 'online' && $placeLabel !== '')
                                    · {{ $placeLabel }}
                                @endif
                            </span>
                        </span>
                        <span class="d-flex flex-column align-items-end gap-1">
                            <span class="badge text-bg-light border">{{ $course->type === 'online' ? 'Online' : 'Stacjonarnie' }}</span>
                            <span class="badge {{ $course->category === 'closed' ? 'text-bg-success' : 'text-bg-primary' }}">{{ $course->category === 'closed' ? 'Zamknięte' : 'Otwarte' }}</span>
                        </span>
                    </div>
                @endforeach
            @endforeach
        @endif
    </section>
@endsection
