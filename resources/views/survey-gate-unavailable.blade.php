<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ankieta niedostępna — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 2rem 1rem; background: #f8f9fa; color: #212529; }
        .card { max-width: 560px; margin: 3rem auto; background: #fff; padding: 1.75rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
        h1 { font-size: 1.35rem; margin: 0 0 .75rem; }
        p { margin: .5rem 0; line-height: 1.5; }
        .muted { color: #6c757d; font-size: .925rem; }
        a.home { display: inline-block; margin-top: 1rem; color: #0d6efd; }
    </style>
</head>
<body>
<div class="card">
    <h1>Ankieta jest obecnie niedostępna</h1>
    @if(!empty(trim((string)($surveyTitle ?? ''))))
        <p class="muted mb-2">{{ trim((string) $surveyTitle) }}</p>
    @endif

    @if(empty($active))
        <p>Organizator wyłączył tę ankietę dla tego szkolenia.</p>
    @elseif($opensAt && now()->lt($opensAt))
        <p>Okno ankietowy jeszcze się nie rozpoczęło.</p>
        <p class="muted">Udostępnienie planowane od: <strong>{{ $opensAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</strong></p>
        @if($closesAt)
            <p class="muted">planowane do: <strong>{{ $closesAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</strong></p>
        @endif
    @elseif($closesAt && now()->gt($closesAt))
        <p>Zapisy do ankiety zostały już zamknięte.</p>
        <p class="muted">Okno dostępu zakończyło się: <strong>{{ $closesAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</strong></p>
    @else
        <p>Ta ankieta nie jest w tej chwili dostępna. Skontaktuj się z organizatorem szkolenia.</p>
    @endif

    <p class="muted" style="margin-top: 1rem;">Masz dostęp do materiałów w panelu swojego konta na pnedu.pl po zalogowaniu.</p>
    <a class="home" href="{{ route('home') }}">Strona główna</a>
    <a class="home" href="{{ route('login') }}" style="margin-left: 1rem;">Zaloguj się</a>
</div>
</body>
</html>
