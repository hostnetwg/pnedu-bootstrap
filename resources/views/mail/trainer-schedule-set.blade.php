Trener podał termin szkolenia zamkniętego.

Sprawa: {{ $case->id }}
Temat: {{ trim((string) $case->topic) !== '' ? $case->topic : 'Nie podano' }}
Trener: {{ $trainerName }}
Termin: {{ $whenLabel }}
@if(trim($note) !== '')

Notatka:
{{ $note }}
@endif

{{ $caseUrl }}
