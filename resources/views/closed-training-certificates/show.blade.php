@extends('layouts.certificate-registration')

@section('title', 'Zaświadczenia – '.$course_title)

@section('content')
    <div class="container py-4" style="max-width: 960px;">
        <h1 class="h3 mb-2">Zaświadczenia szkolenia</h1>
        <p class="mb-1 fw-semibold">{{ $course_title }}</p>
        @if($schedule_label !== '')
            <p class="text-secondary mb-1">{{ $schedule_label }}</p>
        @endif
        <p class="text-secondary mb-3">Link jest ważny do {{ $valid_until }} r.</p>
        <p>Lista osób zapisanych na to szkolenie. Przy każdej widać, czy link do zaświadczenia został wysłany na jej e-mail oraz czy sama je pobrała. Plik można pobrać i wydrukować dla nauczyciela.</p>
        <p class="small text-secondary">Pobranie przez szkołę nie zmienia informacji, czy uczestnik sam pobrał zaświadczenie.</p>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(! $download_enabled)
            <div class="alert alert-warning">Pobieranie zaświadczeń tego szkolenia nie jest jeszcze włączone. Lista poniżej zostaje.</div>
        @endif

        @if($participants === [])
            <p class="mb-0">Na tym szkoleniu nie ma jeszcze zapisanych uczestników.</p>
        @else
            <div class="table-responsive bg-white border rounded">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Lp</th>
                            <th scope="col">Uczestnik</th>
                            <th scope="col">Link do zaświadczenia</th>
                            <th scope="col">Pobranie przez uczestnika</th>
                            <th scope="col">Plik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($participants as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $row['name'] }}
                                    @if($row['email'] !== '')
                                        <div class="small text-secondary">{{ $row['email'] }}</div>
                                    @endif
                                    @if($row['birth_missing'])
                                        <div class="small text-secondary">Bez daty i miejsca urodzenia — uczestnik ich nie podał.</div>
                                    @endif
                                </td>
                                <td @class(['text-primary' => $row['link_sent']])>{{ $row['link_sent'] ? 'wysłany na e-mail' : 'nie wysłany' }}</td>
                                <td @class(['text-primary' => $row['downloaded']])>{{ $row['downloaded'] ? 'pobrane' : 'nie pobrane' }}</td>
                                <td>
                                    @if($download_enabled)
                                        <a class="btn btn-primary btn-sm" href="{{ route('closed-training-certificates.download', ['token' => $token, 'participant' => $row['id']]) }}">Pobierz PDF</a>
                                    @else
                                        <span class="text-secondary small">Jeszcze niedostępne</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
