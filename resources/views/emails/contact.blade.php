<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Nowa wiadomość z formularza kontaktowego</title>
</head>
<body>
    <h2>Nowa wiadomość z formularza kontaktowego</h2>
    <p><strong>Imię i nazwisko:</strong> {{ $data['name'] }}</p>
    <p><strong>Adres e-mail:</strong> {{ $data['email'] }}</p>
    <p><strong>Wiadomość:</strong></p>
    <p>{!! nl2br(e($data['message'])) !!}</p>
</body>
</html>