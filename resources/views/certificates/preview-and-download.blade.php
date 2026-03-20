@extends('layouts.app')

@section('title', 'Zaświadczenie – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Zaświadczenie – podgląd danych</h1>
                    <p class="text-muted mb-4">
                        Szkolenie:
                        <strong>
                            {{
                                str_replace(
                                    '&nbsp;',
                                    ' ',
                                    html_entity_decode((string) $course->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                                )
                            }}
                        </strong>
                        @if(!empty($course->start_date))
                            <br>Data: {{ \Carbon\Carbon::parse($course->start_date)->locale('pl')->translatedFormat('d.m.Y H:i (l)') }}
                        @endif
                        @if(!empty($course->trainer) && $course->trainer !== 'Brak trenera')
                            <br>Prowadzący: {{ $course->trainer }}
                        @endif
                    </p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <p class="mb-2">Sprawdź dane, które znajdą się na zaświadczeniu. Jeśli coś jest nieprawidłowe, użyj przycisku „Popraw dane”.</p>

                    <dl class="row mb-4">
                        <dt class="col-sm-4 text-muted">Imię i nazwisko</dt>
                        <dd class="col-sm-8">{{ $participant->first_name }} {{ $participant->last_name }}</dd>

                        <dt class="col-sm-4 text-muted">Data urodzenia</dt>
                        <dd class="col-sm-8">{{ $participant->birth_date ? $participant->birth_date->format('d.m.Y') : '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Miejsce urodzenia</dt>
                        <dd class="col-sm-8">{{ $participant->birth_place ?: '—' }}</dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('certificates.download-with-redirect', ['token' => $token, 'course' => $course->id]) }}"
                           class="btn btn-primary">
                            <i class="bi bi-download me-1"></i> Pobierz zaświadczenie (PDF)
                        </a>
                        @if($course->is_paid)
                            <a href="{{ route('certificates.show-by-token', ['token' => $token, 'course' => $course->id]) }}?edit=1"
                               class="btn btn-outline-secondary">
                                Popraw dane
                            </a>
                        @else
                            <a href="{{ route('certificates.show-by-token', ['token' => $token, 'course' => $course->id]) }}?edit=1"
                               class="btn btn-outline-secondary">
                                Uzupełnij data i miejsce urodzenia (opcjonalnie)
                            </a>
                        @endif
                        <a href="{{ route('certificates.list-by-token', ['token' => $token]) }}" class="btn btn-link text-muted">
                            ← Lista zaświadczeń
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-center text-muted mt-3 small">
                <a href="{{ route('home') }}" class="text-decoration-none">← Powrót na stronę główną</a>
            </p>
        </div>
    </div>
</div>
@endsection
