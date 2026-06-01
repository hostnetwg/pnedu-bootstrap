<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje zamówienie #{{ $order->id }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #333; background-color: #f5f5f5;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
<tr><td style="padding: 32px 40px;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
<tr><td style="padding: 40px 48px;">
    
    <p style="margin: 0 0 16px;">Dzień dobry,</p>
    <p style="margin: 0 0 16px;">serdecznie dziękujemy za zakup.</p>
    <p style="margin: 0 0 8px; font-size: 17px; font-weight: bold; color: #1a1a1a;">{{ str_replace('&nbsp;', ' ', strip_tags($order->product_name)) }}</p>
    @if($course && $course->start_date)
        @php
            $courseDateTime = \Carbon\Carbon::parse($course->start_date)->locale('pl');
        @endphp
        <p style="margin: 0 0 24px; font-size: 15px; color: #555;">{{ $courseDateTime->format('d.m.Y') }}, {{ $courseDateTime->format('H:i') }} – {{ $courseDateTime->translatedFormat('l') }}</p>
    @endif
    <p style="margin: 0 0 24px;">W załączniku znajdziesz swój dokument zamówienia <strong>#{{ $order->id }}</strong>.</p>
    
    @php
        $isCoursePassed = false;
        if ($course && $course->start_date) {
            $courseDate = \Carbon\Carbon::parse($course->start_date);
            $isCoursePassed = $courseDate->isPast();
        }
    @endphp
    
    @if($isCoursePassed)
        <p style="margin: 0 0 24px;">Wkrótce prześlemy wszystkie dane dostępowe oraz fakturę z odroczonym terminem płatności. W razie pytań proszę o kontakt: kontakt@pnedu.pl; tel. 501 654 274.</p>
    @else
        <p style="margin: 0 0 24px;">Dzień przed terminem szkolenia prześlemy wszystkie dane dostępowe oraz fakturę z odroczonym terminem płatności. W razie pytań proszę o kontakt: kontakt@pnedu.pl; tel. 501 654 274.</p>
    @endif

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 28px 0; border: 1px solid #e0e0e0; border-radius: 6px; background-color: #fafafa;">
    <tr><td style="padding: 20px 24px;">
        <p style="margin: 0 0 16px; font-size: 13px; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">Przesłane dane</p>

        <p style="margin: 0 0 4px; font-weight: bold; font-size: 14px;">Nabywca</p>
        <p style="margin: 0 0 16px; padding-left: 12px; color: #444;">
            {{ $order->buyer_name }}<br>
            {{ $order->buyer_postal_code }} {{ $order->buyer_city }}<br>
            {{ $order->buyer_address }}<br>
            @if($order->buyer_nip)NIP: {{ $order->buyer_nip }}@endif
        </p>

        @if($order->recipient_name)
        <p style="margin: 0 0 4px; font-weight: bold; font-size: 14px;">Odbiorca</p>
        <p style="margin: 0 0 16px; padding-left: 12px; color: #444;">
            {{ $order->recipient_name }}<br>
            @if($order->recipient_postal_code && $order->recipient_city){{ $order->recipient_postal_code }} {{ $order->recipient_city }}<br>@endif
            @if($order->recipient_address){{ $order->recipient_address }}<br>@endif
            @if($order->recipient_nip)NIP: {{ $order->recipient_nip }}@endif
        </p>
        @endif

        <p style="margin: 0 0 4px; font-weight: bold; font-size: 14px;">Fakturę prześlemy na</p>
        <p style="margin: 0 0 16px; padding-left: 12px; color: #444;"><strong>{{ $order->orderer_email }}</strong></p>

        <p style="margin: 0 0 4px; font-weight: bold; font-size: 14px;">Uczestnik</p>
        <p style="margin: 0 0 4px; padding-left: 12px; font-size: 13px; color: #666;">(dane dostępowe do szkolenia, materiałów oraz zaświadczenia)</p>
        <p style="margin: 0; padding-left: 12px; color: #444;">
            <strong style="font-size: 16px; color: #1a1a1a;">{{ $order->display_participant_name }}</strong><br>
            {{ $order->display_participant_email }}
        </p>
    </td></tr>
    </table>
    
    @if($course)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 24px 0; background-color: #f0f7ff; border-radius: 6px; border: 1px solid #cce5ff;">
    <tr><td style="padding: 20px 24px;">
        <p style="margin: 0 0 12px;">Jeżeli powyższe dane zawierają błędy, możesz je poprawić pod poniższym linkiem:</p>
        <p style="margin: 0;">
            <a href="{{ url(route('payment.order-form.edit', ['id' => $course->id, 'ident' => $order->ident])) }}" style="display: inline-block; padding: 12px 20px; background-color: #0066cc; color: #ffffff !important; text-decoration: none; font-weight: bold; font-size: 15px; border-radius: 6px;">Popraw przesłane dane</a>
        </p>
    </td></tr>
    </table>
    @endif

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
