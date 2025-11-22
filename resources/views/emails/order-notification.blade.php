<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje zamówienie #{{ $order->id }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <p>Dzień dobry,</p>
    
    <p>serdecznie dziękujemy za zakup.</p>
    
    <p><strong>SZKOLENIE:</strong> {{ str_replace('&nbsp;', ' ', strip_tags($order->product_name)) }}</p>
    
    @if($course && $course->start_date)
    <p><strong>Data szkolenia:</strong> {{ date('d.m.Y H:i', strtotime($course->start_date)) }}</p>
    @endif
    
    <p>W załączniku znajdziesz swój dokument zamówienia #{{ $order->id }}.</p>
    
    @php
        $isCoursePassed = false;
        if ($course && $course->start_date) {
            $courseDate = \Carbon\Carbon::parse($course->start_date);
            $isCoursePassed = $courseDate->isPast();
        }
    @endphp
    
    @if($isCoursePassed)
        <p>Wkrótce prześlemy wszystkie dane dostępowe oraz fakturę z odroczonym terminem płatności. W razie pytań proszę o kontakt: kontakt@nowoczesna-edukacja.pl; tel. 501 654 274.</p>
    @else
        <p>Dzień przed terminem szkolenia prześlemy wszystkie dane dostępowe oraz fakturę z odroczonym terminem płatności. W razie pytań proszę o kontakt: kontakt@nowoczesna-edukacja.pl; tel. 501 654 274.</p>
    @endif
    
    <h3 style="margin-top: 30px; margin-bottom: 10px;">PRZESŁANE DANE</h3>
    
    <p><strong>NABYWCA:</strong></p>
    <p style="margin-left: 20px;">
        {{ $order->buyer_name }}<br>
        {{ $order->buyer_postal_code }} {{ $order->buyer_city }}<br>
        {{ $order->buyer_address }}<br>
        NIP: {{ $order->buyer_nip }}
    </p>
    
    @if($order->recipient_name)
    <p style="margin-top: 15px;"><strong>ODBIORCA:</strong></p>
    <p style="margin-left: 20px;">
        {{ $order->recipient_name }}<br>
        @if($order->recipient_postal_code && $order->recipient_city && $order->recipient_address)
            {{ $order->recipient_postal_code }} {{ $order->recipient_city }}<br>
            {{ $order->recipient_address }}<br>
        @endif
        @if($order->recipient_nip)
            NIP: {{ $order->recipient_nip }}
        @endif
    </p>
    @endif
    
    @if($order->invoice_notes)
    <p style="margin-top: 15px;"><strong>UWAGI DO FAKTURY:</strong></p>
    <p style="margin-left: 20px;">{{ $order->invoice_notes }}</p>
    @endif
    
    @if($order->invoice_payment_delay)
    <p style="margin-top: 15px; margin-left: 20px;"><strong>TERMIN PŁATNOŚCI:</strong> {{ $order->invoice_payment_delay }} dni</p>
    @endif
    
    <p style="margin-top: 15px;">Fakturę prześlemy na:<br>
    <strong>{{ $order->orderer_email }}</strong></p>
    
    <p style="margin-top: 15px;"><strong>UCZESTNIK:</strong><br>
    (dane dostępowe do szkolenia, materiałów oraz zaświadczenia)</p>
    <p style="margin-left: 20px;">
        {{ $order->participant_name }}<br>
        {{ $order->participant_email }}
    </p>
    
    @if($course)
    <p style="margin-top: 30px;">Jeżeli powyższe dane zawierają błędy, możesz je poprawić pod poniższym linkiem:</p>
    <p style="margin-top: 10px;">
        <a href="{{ route('payment.deferred.edit', ['id' => $course->id, 'ident' => $order->ident]) }}" style="color: #0066cc; text-decoration: underline; font-size: 16px;">👉 Popraw przesłane dane</a>
    </p>
    @endif
    
    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
    
    <p style="margin-top: 20px;">Z wyrazami szacunku,<br></p>
    
    <p>
        Waldemar Grabowski<br>
        tel. 501 654 274<br>
        <strong>Akredytowany Niepubliczny Ośrodek Doskonalenia Nauczycieli</strong><br>
        "<strong>Platforma Nowoczesnej Edukacji</strong>"<br>
        <img src="https://pnedu.pl/grafika/NODN%20Platforma%20Nowoczesnej%20Edukacji%20-%20logo.png" alt="PNE - LOGO" style="max-width: 200px; margin-top: 10px;"><br>
        <a href="https://nowoczesna-edukacja.pl" style="color: #0066cc; text-decoration: underline;">nowoczesna-edukacja.pl</a>
    </p>

</body>
</html>