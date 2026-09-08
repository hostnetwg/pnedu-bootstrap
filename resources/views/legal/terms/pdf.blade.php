<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Regulamin pnedu.pl — {{ $version }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; color: #222; }
        h1 { font-size: 18px; }
        h2 { font-size: 13px; margin-top: 18px; }
        li { margin-bottom: 4px; }
        a { color: #222; }
        .meta { color: #555; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Regulamin pnedu.pl</h1>
    <p class="meta">Wersja {{ $version }}, obowiązuje od {{ $effectiveAt }}.</p>
    @include($viewName)
</body>
</html>
