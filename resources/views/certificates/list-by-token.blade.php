@extends('layouts.app')

@section('title', 'Twoje zaświadczenia – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Twoje zaświadczenia</h1>
                    <p class="text-muted mb-4">
                        Poniżej lista szkoleń, w których brałeś/aś udział. Zaświadczenie można pobrać, gdy administrator udostępni je dla danego szkolenia.
                    </p>

                    @if(empty($items))
                        <p class="text-muted mb-0">Nie znaleziono żadnych szkoleń powiązanych z tym linkiem.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($items as $item)
                                <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-0 border-0 border-bottom">
                                    <div class="me-3">
                                        <strong class="d-block">{{ $item['course']->title }}</strong>
                                        <small class="text-muted d-block">
                                            @if($item['course']->start_date)
                                                {{ \Carbon\Carbon::parse($item['course']->start_date)->locale('pl')->translatedFormat('d.m.Y H:i (l)') }}
                                            @else
                                                —
                                            @endif
                                        </small>
                                        @if(!empty($item['course']->trainer) && $item['course']->trainer !== 'Brak trenera')
                                            <small class="text-muted d-block">Prowadzący: {{ $item['course']->trainer }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        @php
                                            $courseEnded = $item['course']->end_date && \Carbon\Carbon::parse($item['course']->end_date)->isPast();
                                        @endphp
                                        @if(!$courseEnded)
                                            <span class="badge bg-info text-dark" title="Zaświadczenie zostanie udostępnione po zakończeniu szkolenia">Po zakończeniu szkolenia</span>
                                        @elseif($item['can_download'])
                                            <a href="{{ route('certificates.show-by-token', ['token' => $token, 'course' => $item['course']->id]) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-download me-1"></i> Pobierz zaświadczenie
                                            </a>
                                        @elseif(($item['status_key'] ?? '') === 'no_certificate')
                                            <span class="badge bg-dark">Brak zaświadczenia</span>
                                        @else
                                            <span class="badge bg-secondary">W przygotowaniu</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <p class="text-center text-muted mt-3 small">
                <a href="{{ route('home') }}" class="text-decoration-none">← Powrót na stronę główną</a>
            </p>
        </div>
    </div>
</div>
@endsection
