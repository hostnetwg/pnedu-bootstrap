<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przypomnienie o płatności — zamówienie #{{ $order->id }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #333; background-color: #f5f5f5;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
<tr><td style="padding: 32px 40px;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
<tr><td style="padding: 40px 48px;">

    <p style="margin: 0 0 16px;">Dzień dobry,</p>
    <p style="margin: 0 0 16px;">zauważyliśmy, że płatność online za szkolenie nie została dokończona. Twoje zamówienie nadal czeka — możesz dokończyć płatność lub wybrać fakturę z odroczonym terminem płatności.</p>
    <p style="margin: 0 0 8px; font-size: 17px; font-weight: bold; color: #1a1a1a;">{{ str_replace('&nbsp;', ' ', strip_tags($order->product_name)) }}</p>
    @if($course && $course->start_date)
        @php
            $courseDateTime = \Carbon\Carbon::parse($course->start_date)->locale('pl');
        @endphp
        <p style="margin: 0 0 24px; font-size: 15px; color: #555;">{{ $courseDateTime->format('d.m.Y') }}, {{ $courseDateTime->format('H:i') }} – {{ $courseDateTime->translatedFormat('l') }}</p>
    @endif

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 24px 0; border: 1px solid #e0e0e0; border-radius: 6px; background-color: #fafafa;">
    <tr><td style="padding: 20px 24px;">
        <p style="margin: 0 0 8px;"><strong>Numer zamówienia:</strong> {{ $order->ident }}</p>
        <p style="margin: 0;"><strong>Do zapłaty:</strong> {{ number_format((float) $onlinePaymentOrder->total_amount, 2, ',', ' ') }} PLN</p>
    </td></tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 24px 0; background-color: #f0f7ff; border-radius: 6px; border: 1px solid #cce5ff;">
    <tr><td style="padding: 20px 24px;">
        <p style="margin: 0 0 12px;">Wybierz dogodną opcję:</p>
        <p style="margin: 0 0 16px;">
            <a href="{{ $retryPaymentUrl }}" style="display: inline-block; padding: 12px 20px; background-color: #0066cc; color: #ffffff !important; text-decoration: none; font-weight: bold; font-size: 15px; border-radius: 6px;">Zapłać ponownie</a>
        </p>
        <p style="margin: 0;">
            <a href="{{ $deferredOrderFormUrl }}" style="color: #0066cc; text-decoration: underline;">Wolę fakturę z odroczonym terminem płatności</a>
        </p>
        <p style="margin: 16px 0 0; font-size: 13px; color: #666;">
            Status płatności: <a href="{{ $pendingPageUrl }}" style="color: #0066cc; text-decoration: underline;">strona oczekiwania</a>
        </p>
    </td></tr>
    </table>

    <p style="margin: 0 0 24px;">W razie pytań proszę o kontakt: kontakt@pnedu.pl; tel. 501 654 274.</p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 28px 0;">

    <p style="margin: 0 0 8px;">Z wyrazami szacunku,</p>
    <p style="margin: 0; font-size: 14px; color: #555;">
        Waldemar Grabowski<br>
        tel. 501 654 274<br>
        <strong>Akredytowany Niepubliczny Ośrodek Doskonalenia Nauczycieli</strong><br>
        „<strong>Platforma Nowoczesnej Edukacji</strong>”
    </p>
    <p style="margin: 20px 0 0;">
        <img src="https://pnedu.pl/grafika/NODN%20Platforma%20Nowoczesnej%20Edukacji%20-%20logo.png" alt="PNE - LOGO" width="180" style="display: block; max-width: 180px;">
    </p>
    <p style="margin: 16px 0 0;">
        <a href="{{ $brandPublicUrl }}" style="color: #0066cc; text-decoration: underline;">{{ $brandPublicLabel }}</a>
    </p>

</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
