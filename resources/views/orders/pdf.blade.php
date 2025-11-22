<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamówienie {{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 40px;
            padding-bottom: 120px;
            position: relative;
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            padding: 10px;
            vertical-align: top;
            width: 50%;
        }
        .header-left {
            text-align: left;
        }
        .header-right {
            text-align: right;
        }
        .location-date {
            text-align: center;
            font-size: 10px;
            margin-bottom: 10px;
        }
        .location-date-dots {
            margin-bottom: 5px;
            letter-spacing: 2px;
        }
        .header-table h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .header-table h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header-table p {
            font-size: 10px;
            margin-bottom: 3px;
            line-height: 1.4;
        }
        .divider {
            border-top: 1px solid #000;
            margin: 20px 0;
        }
        .order-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.order-table {
            border: 1px solid #000;
            margin-bottom: 0;
        }
        table.order-table th,
        table.order-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        table.order-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        table.order-table td:nth-child(1) {
            width: 8%;
            text-align: center;
        }
        table.order-table td:nth-child(2) {
            width: 70%;
        }
        table.order-table td:nth-child(3) {
            width: 22%;
            text-align: center;
        }
        table.invoice-table {
            margin-top: 10px;
            margin-bottom: 0;
            border: 1px solid #000;
        }
        table.invoice-table th,
        table.invoice-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        table.invoice-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            width: 50%;
        }
        table.invoice-table td {
            text-align: center;
        }
        table.access-table {
            margin-top: 10px;
            margin-bottom: 0;
            border: 1px solid #000;
        }
        table.access-table th,
        table.access-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        table.access-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }
        .vat-info {
            font-style: italic;
            font-size: 10px;
            margin-top: -5px;
            margin-bottom: 5px;
        }
        .info-text {
            font-size: 10px;
            margin: 2px 0;
            line-height: 1.2;
        }
        .info-text-compact {
            font-size: 10px;
            margin: 0;
            line-height: 1.2;
        }
        .info-text strong {
            font-weight: bold;
        }
        a {
            color: #0066cc;
            text-decoration: underline;
        }
        a:visited {
            color: #0066cc;
        }
        .footer {
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
            margin-top: 0;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 9px;
            color: #333;
            text-align: left;
            line-height: 1.4;
        }
        .footer-info {
            margin: 0;
            padding: 0;
        }
        .footer-text {
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .signature-section {
            margin-top: 80px;
            text-align: right;
            font-size: 10px;
            padding-bottom: 5px;
        }
        .signature-dots {
            margin-bottom: 5px;
            letter-spacing: 2px;
        }
        .signature-text {
            padding-right: 20px;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
    <!-- Nagłówek - tabela z 2 kolumnami -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                <h1>{{ $order->orderer_name }}</h1>
                <p>tel. {{ $order->orderer_phone }}</p>
                <p>e-mail: {{ $order->orderer_email }}</p>
            </td>
            <td class="header-right">
                <div class="location-date">
                    <div class="location-date-dots">...............................................................</div>
                    <div>                   miejscowość, data</div>
                </div>
                <h1>Platforma Nowoczesnej Edukacji</h1>
                <p>ul. A. Zamoyskiego 30/14, 09-320 Bieżuń</p>
                <p>RSPO: 481379 NIP: 7392137630</p>
                <p>NR KONTA: {{ substr('25114020040000300282222577', 0, 2) }} {{ substr('25114020040000300282222577', 2, 4) }} {{ substr('25114020040000300282222577', 6, 4) }} {{ substr('25114020040000300282222577', 10, 4) }} {{ substr('25114020040000300282222577', 14, 4) }} {{ substr('25114020040000300282222577', 18, 4) }} {{ substr('25114020040000300282222577', 22, 4) }}</p>
                <p>e-mail: kontakt@nowoczesna-edukacja.pl</p>
                <p>tel. +48501654274</p>
            </td>
        </tr>
    </table>

    <!-- Tytuł ZAMÓWIENIE -->
    <div class="order-title">ZAMÓWIENIE</div>

    <!-- Tabela z zamówieniem -->
    <table class="order-table">
        <thead>
            <tr>
                <th>L.p.</th>
                <th>Nazwa produktu</th>
                <th>Cena brutto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    SZKOLENIE: {{ str_replace('&nbsp;', ' ', strip_tags($order->product_name)) }}
                </td>
                <td>{{ number_format($order->product_price, 0, ',', ' ') }} PLN</td>
            </tr>
        </tbody>
    </table>
    <!-- Informacja o zwolnieniu VAT -->
    <div class="vat-info">
        * Platforma Nowoczesnej Edukacji Waldemar Grabowski - zwolnienie VAT, Art. 43 ust. 1 pkt 29 lit. b)
    </div>

    <!-- Dane na fakturze -->
    <div class="section-title">Dane na fakturze</div>
    
    <table class="invoice-table">
        <thead>
            <tr>
                <th>NABYWCA</th>
                <th>ODBIORCA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ $order->buyer_name }}<br>
                    {{ $order->buyer_postal_code }}, {{ $order->buyer_city }}, {{ $order->buyer_address }}<br>
                    NIP: {{ $order->buyer_nip }}
                </td>
                <td>
                    @if($order->recipient_name)
                        {{ $order->recipient_name }}<br>
                        @if($order->recipient_postal_code && $order->recipient_city && $order->recipient_address)
                            {{ $order->recipient_postal_code }}, {{ $order->recipient_city }}, {{ $order->recipient_address }}<br>
                        @endif
                        @if($order->recipient_nip)
                            NIP: {{ $order->recipient_nip }}
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Uwagi do faktury -->
    @if($order->invoice_notes)
    <div class="info-text-compact" style="margin-top: 0;">
        Dodatkowe uwagi do faktury: {{ $order->invoice_notes }}
    </div>
    @endif

    <!-- Termin płatności -->
    @if($order->invoice_payment_delay)
    <div class="info-text-compact">
        Proszę o wystawienie faktury z odroczonym terminem płatności: {{ $order->invoice_payment_delay }} dni.
    </div>
    @endif

    <!-- Informacja o przesłaniu faktury -->
    <div class="info-text-compact">
        Na podany w zamówieniu adres e-mail: <strong>{{ $order->orderer_email }}</strong> zostanie przesłana faktura.
    </div>

    <!-- DANE DOSTĘPOWE DO KURSU -->
    <div class="section-title">DANE DOSTĘPOWE DO KURSU</div>
    
    <div class="info-text" style="margin-bottom: 10px;">
        strona logowania: <a href="https://nowoczesna-edukacja.pl" target="_blank">nowoczesna-edukacja.pl</a>
    </div>

    <table class="access-table">
        <thead>
            <tr>
                <th>imię i nazwisko</th>
                <th>adres e-mail (proszę o bezbłędne wypełnienie)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $order->participant_name }}</td>
                <td>{{ $order->participant_email }}</td>
            </tr>
        </tbody>
    </table>

    <div class="info-text" style="margin-top: 0; font-style: italic;">
        * na powyższy e-mail zostaną przesłane dane dostępowe do kursu.
    </div>

    <!-- Sekcja z pieczątką i podpisem -->
    <div class="signature-section">
        <div class="signature-dots">...........................................................</div>
        <div class="signature-text">   pieczątka szkoły, podpis</div>
    </div>
    <!-- Stopka -->
    <div class="footer">
        <div class="footer-info">
            Zamówienie nr {{ $order->id }} | data zamówienia: {{ $order->order_date->format('Y-m-d H:i:s') }}
        </div>
        <div class="footer-text">
            Po złożeniu zamówienia prosimy o wydrukowanie wygenerowanego zamówienia w celu ewentualnej weryfikacji.
        </div>
        <div class="footer-text">
            Kontakt: kontakt@nowoczesna-edukacja.pl lub 501 654 274
        </div>
    </div>
    </div>
</body>
</html>
