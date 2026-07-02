<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Powiadomienie o płatności</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1976d2;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .section {
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #1976d2;
        }
        .section-title {
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .info-value {
            display: inline-block;
        }
        .footer {
            background-color: #f0f0f0;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-radius: 0 0 5px 5px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Powiadomienie o nowej płatności online</h2>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="section-title">📋 Informacje o zamówieniu</div>
            <div class="info-row">
                <span class="info-label">Numer zamówienia:</span>
                <span class="info-value"><strong>{{ $order->ident }}</strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Data i godzina:</span>
                <span class="info-value">{{ $order->created_at->format('d.m.Y H:i:s') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    @if($order->status === 'paid')
                        <strong style="color: green;">{{ $order->statusLabel() }}</strong>
                    @else
                        {{ $order->statusLabel() }}
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Bramka płatności:</span>
                <span class="info-value">
                    <strong>{{ $order->paymentGatewayLabel() }}</strong>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Kwota:</span>
                <span class="info-value"><strong>{{ number_format($order->total_amount, 2, ',', ' ') }} {{ $order->currency }}</strong></span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">👤 Dane zamawiającego</div>
            <div class="info-row">
                <span class="info-label">Imię:</span>
                <span class="info-value">{{ $order->first_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Nazwisko:</span>
                <span class="info-value">{{ $order->last_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">E-mail:</span>
                <span class="info-value">{{ $order->email }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Telefon:</span>
                <span class="info-value">{{ $order->phone ?? 'Nie podano' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Typ zamawiającego:</span>
                <span class="info-value">{{ $order->buyerTypeLabel() }}</span>
            </div>
        </div>

        @if($order->address_data && is_array($order->address_data))
        <div class="section">
            <div class="section-title">📮 Dane do faktury</div>
            @if($order->buyer_type === 'person' && isset($order->address_data['full_name']))
                <div class="info-row">
                    <span class="info-label">Imię i nazwisko:</span>
                    <span class="info-value">{{ $order->address_data['full_name'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adres:</span>
                    <span class="info-value">
                        @if(!empty($order->address_data['street']) || !empty($order->address_data['building_no']))
                            {{ $order->address_data['street'] ?? '' }}
                            @if(isset($order->address_data['building_no']))
                                {{ $order->address_data['building_no'] }}
                            @endif
                            @if(isset($order->address_data['flat_no']))
                                /{{ $order->address_data['flat_no'] }}
                            @endif
                        @else
                            Nie podano
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kod pocztowy:</span>
                    <span class="info-value">{{ $order->address_data['postcode'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Miasto:</span>
                    <span class="info-value">{{ $order->address_data['city'] ?? 'Nie podano' }}</span>
                </div>
            @elseif($order->buyer_type === 'company')
                <div class="info-row">
                    <span class="info-label">Nazwa firmy:</span>
                    <span class="info-value">{{ $order->address_data['name'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">NIP:</span>
                    <span class="info-value">{{ $order->address_data['nip'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ulica:</span>
                    <span class="info-value">
                        {{ $order->address_data['street'] ?? '' }}
                        @if(isset($order->address_data['building_no']))
                            {{ $order->address_data['building_no'] }}
                        @endif
                        @if(isset($order->address_data['flat_no']))
                            /{{ $order->address_data['flat_no'] }}
                        @endif
                        @if(empty($order->address_data['street']) && empty($order->address_data['building_no']))
                            Nie podano
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kod pocztowy:</span>
                    <span class="info-value">{{ $order->address_data['postcode'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Miasto:</span>
                    <span class="info-value">{{ $order->address_data['city'] ?? 'Nie podano' }}</span>
                </div>
            @elseif($order->buyer_type === 'organisation')
                @php
                    $buyer = $order->address_data['buyer'] ?? [];
                    $recipient = $order->address_data['recipient'] ?? [];
                @endphp
                <div class="info-row">
                    <span class="info-label">Nazwa instytucji:</span>
                    <span class="info-value">{{ $buyer['name'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">NIP:</span>
                    <span class="info-value">{{ $buyer['nip'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ulica:</span>
                    <span class="info-value">
                        {{ $buyer['street'] ?? '' }}
                        @if(isset($buyer['building_no']))
                            {{ $buyer['building_no'] }}
                        @endif
                        @if(isset($buyer['flat_no']))
                            /{{ $buyer['flat_no'] }}
                        @endif
                        @if(empty($buyer['street']) && empty($buyer['building_no']))
                            Nie podano
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kod pocztowy:</span>
                    <span class="info-value">{{ $buyer['postcode'] ?? 'Nie podano' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Miasto:</span>
                    <span class="info-value">{{ $buyer['city'] ?? 'Nie podano' }}</span>
                </div>
                @if(!empty($recipient) && isset($recipient['name']))
                <div class="highlight">
                    <strong>Dane odbiorcy:</strong><br>
                    {{ $recipient['name'] ?? '' }}<br>
                    @if(isset($recipient['street']))
                        {{ $recipient['street'] }}
                        @if(isset($recipient['building_no']))
                            {{ $recipient['building_no'] }}
                        @endif
                        @if(isset($recipient['flat_no']))
                            /{{ $recipient['flat_no'] }}
                        @endif
                        <br>
                    @endif
                    @if(isset($recipient['postcode']) || isset($recipient['city']))
                        {{ $recipient['postcode'] ?? '' }} {{ $recipient['city'] ?? '' }}<br>
                    @endif
                    @if(isset($recipient['nip']))
                        NIP: {{ $recipient['nip'] }}
                    @endif
                </div>
                @endif
            @endif
        </div>
        @endif

        <div class="section">
            <div class="section-title">🎓 Informacje o szkoleniu</div>
            <div class="info-row">
                <span class="info-label">Nazwa szkolenia:</span>
                <span class="info-value"><strong>{{ $course ? strip_tags($course->title) : 'Nieznane szkolenie' }}</strong></span>
            </div>
            @if($course && $course->start_date)
            <div class="info-row">
                <span class="info-label">Data szkolenia:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y H:i') }}</span>
            </div>
            @endif
        </div>

        @if($order->order_comment)
        <div class="section">
            <div class="section-title">💬 Komentarz do zamówienia</div>
            <div>{{ $order->order_comment }}</div>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>To jest automatyczne powiadomienie o nowej płatności online.</p>
        <p>Platforma Nowoczesnej Edukacji</p>
    </div>
</body>
</html>
