<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zapytanie o szkolenie rady pedagogicznej</title>
</head>
<body>
    <h2>Zapytanie o szkolenie rady pedagogicznej</h2>

    <h3>Oferta / temat</h3>
    <p><strong>Tytuł / temat:</strong> {{ $data['offer_title'] }}</p>
    @if(!empty($data['offer_url']))
        <p><strong>Link do oferty:</strong> <a href="{{ $data['offer_url'] }}">{{ $data['offer_url'] }}</a></p>
    @endif

    @if(!empty($data['offer_topic']))
        <p><strong>Temat zaproponowany przez użytkownika:</strong> {{ $data['offer_topic'] }}</p>
    @endif

    <h3>Dane kontaktowe</h3>
    <p><strong>Imię i nazwisko:</strong> {{ $data['name'] }}</p>
    <p><strong>Adres e-mail:</strong> {{ $data['email'] }}</p>
    <p><strong>Telefon:</strong> {{ $data['phone'] ?: 'Nie podano' }}</p>
    <p><strong>Placówka:</strong> {{ $data['institution'] ?: 'Nie podano' }}</p>
    <p><strong>Preferowana forma:</strong> {{ [
        'online' => 'Online',
        'onsite' => 'Stacjonarnie',
        'to_discuss' => 'Do ustalenia',
        '' => 'Nie wybrano',
        null => 'Nie wybrano',
    ][$data['preferred_format'] ?? null] ?? 'Nie wybrano' }}</p>

    <h3>Wiadomość</h3>
    <p>{!! nl2br(e($data['message'])) !!}</p>
</body>
</html>
