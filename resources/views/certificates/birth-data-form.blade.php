@extends('layouts.app')

@section('title', 'Dane do zaświadczenia – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">{{ $optional ?? false ? 'Data i miejsce urodzenia na zaświadczeniu (opcjonalnie)' : 'Uzupełnij dane do zaświadczenia' }}</h1>
                    <p class="text-muted mb-3">
                        Szkolenie: <strong>{{ $course->title }}</strong>
                        @if(!empty($course->start_date))
                            <br>Data: {{ \Carbon\Carbon::parse($course->start_date)->locale('pl')->translatedFormat('d.m.Y H:i (l)') }}
                        @endif
                        @if(!empty($course->trainer) && $course->trainer !== 'Brak trenera')
                            <br>Prowadzący: {{ $course->trainer }}
                        @endif
                    </p>
                    <div class="small text-muted mb-4">
                        @if($optional ?? false)
                            <p class="mb-0">
                                Opcjonalnie możesz podać data i miejsce urodzenia – wtedy znajdą się one na zaświadczeniu. Możesz też pobrać zaświadczenie bez tych danych.
                            </p>
                        @else
                            <p class="mb-2">
                                Działając na podstawie §&nbsp;26 ust.&nbsp;1 w związku z §&nbsp;23 ust.&nbsp;1 Rozporządzenia Ministra Edukacji Narodowej z dnia 28&nbsp;maja 2019&nbsp;r. w sprawie placówek doskonalenia nauczycieli (Dz.U. z&nbsp;2019&nbsp;r. poz.&nbsp;1045 z&nbsp;późn.&nbsp;zm.), nasza placówka zobowiązana jest do prowadzenia rejestru wydanych zaświadczeń.
                            </p>
                            <p class="mb-2">
                                Abyśmy mogli dopełnić tego obowiązku brakuje nam następujących informacji:
                            </p>
                            <ul class="mb-2">
                                <li>Data urodzenia</li>
                                <li>Miejsce urodzenia</li>
                            </ul>
                            <p class="mb-0">
                                Aby uzupełnić dane, wypełnij poniższy formularz i kliknij przycisk „Zapisz i przejdź dalej”:
                            </p>
                        @endif
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="post" action="{{ route('certificates.submit-birth-data', ['token' => $token, 'course' => $course->id]) }}">
                        @csrf
                        @if($optional ?? false)
                            <input type="hidden" name="optional" value="1">
                        @endif
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Data urodzenia @if(!($optional ?? false))<span class="text-danger">*</span>@endif</label>
                            <input type="date" name="birth_date" id="birth_date"
                                   class="form-control @error('birth_date') is-invalid @enderror"
                                   value="{{ old('birth_date', $participant->birth_date ? $participant->birth_date->format('Y-m-d') : '') }}"
                                   @if(!($optional ?? false)) required @endif>
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="birth_place" class="form-label">Miejsce urodzenia @if(!($optional ?? false))<span class="text-danger">*</span>@endif</label>
                            <input type="text" name="birth_place" id="birth_place"
                                   class="form-control @error('birth_place') is-invalid @enderror"
                                   value="{{ old('birth_place', $participant->birth_place) }}"
                                   maxlength="255" @if(!($optional ?? false)) required @endif>
                            @error('birth_place')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ ($optional ?? false) ? 'Zapisz' : 'Zapisz i przejdź dalej' }}</button>
                            @if($optional ?? false)
                                <a href="{{ route('certificates.show-by-token', ['token' => $token, 'course' => $course->id]) }}" class="btn btn-outline-secondary">Wróć do podglądu</a>
                            @else
                                <a href="{{ route('certificates.list-by-token', ['token' => $token]) }}" class="btn btn-outline-secondary">Anuluj</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted mt-3 small">
                <a href="{{ route('home') }}" class="text-decoration-none">← Powrót na stronę główną</a>
            </p>
        </div>
    </div>
</div>
@endsection
