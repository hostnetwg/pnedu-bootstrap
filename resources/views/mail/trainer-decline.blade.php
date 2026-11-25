@if($withdrawn)
Trener wycofał rezygnację ze szkolenia zamkniętego.
@else
Trener rezygnuje z prowadzenia szkolenia zamkniętego.
@endif

Sprawa: {{ $case->id }}
Temat: {{ trim((string) $case->topic) !== '' ? $case->topic : 'Nie podano' }}
Trener: {{ $trainerName }}
@if(! $withdrawn)

Wyjaśnienie:
{{ $reason }}
@endif

{{ $caseUrl }}
