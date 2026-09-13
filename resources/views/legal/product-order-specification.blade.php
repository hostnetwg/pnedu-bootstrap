<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Specyfikacja zamówienia {{ $order->ident }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; color: #222; }
        h1 { font-size: 16px; }
        h2 { font-size: 12px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; vertical-align: top; padding: 4px 0; }
        .muted { color: #555; }
    </style>
</head>
<body>
    <h1>Specyfikacja zamówienia kursu</h1>
    <p class="muted">
        Platforma Nowoczesnej Edukacji Waldemar Grabowski,
        ul. Andrzeja Zamoyskiego 30/14, 09-320 Bieżuń,
        NIP 7392137630, kontakt@pnedu.pl, +48 501 654 274
    </p>
    <table>
        <tr><th>Numer zamówienia</th><td>{{ $order->id }} ({{ $order->ident }})</td></tr>
        <tr><th>Data zawarcia umowy</th><td>{{ optional($order->contract_concluded_at ?: $order->order_date)->timezone(config('app.timezone'))?->format('d.m.Y H:i') }}</td></tr>
        <tr><th>Regulamin</th><td>wersja {{ $order->terms_version ?: 'brak danych historycznych' }}</td></tr>
        <tr><th>Kurs</th><td>{{ $item?->product_name ?? $order->product_name }}</td></tr>
        <tr><th>Wariant</th><td>{{ $item?->commercial_terms['variant_name'] ?? $item?->metadata['price_name'] ?? '—' }}</td></tr>
        <tr><th>Zakres</th><td>Dostęp do nagrań, lekcji i materiałów kursu na pnedu.pl zgodnie z wariantem.</td></tr>
        <tr><th>Liczba dostępów</th><td>{{ $item?->quantity ?? 1 }}</td></tr>
        <tr><th>Cena jednostkowa</th><td>{{ number_format((float) ($item?->unit_price ?? 0), 2, ',', ' ') }} {{ $item?->currency ?? 'PLN' }}</td></tr>
        <tr><th>Cena łączna</th><td>{{ number_format((float) ($item?->line_total ?? $order->product_price), 2, ',', ' ') }} {{ $item?->currency ?? 'PLN' }}</td></tr>
        <tr><th>Płatność</th><td>
            @if($order->payment_mode === \App\Models\FormOrder::PAYMENT_MODE_DEFERRED_INVOICE)
                Faktura z terminem {{ $order->invoice_payment_delay ?? $order->ptw }} dni. Złożenie zamówienia nie pobiera płatności od razu.
            @else
                Płatność online (PayU lub PayNow).
            @endif
        </td></tr>
        <tr><th>Termin rozpoczęcia</th><td>
            @if($item?->access_starts_at)
                {{ $item->access_starts_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
            @else
                Zgodnie z nadaniem dostępu (reguła z chwili zakupu: {{ $item?->commercial_terms['access_starts_rule'] ?? 'from_grant_or_existing_end' }})
            @endif
        </td></tr>
        <tr><th>Okres dostępu</th><td>{{ $item?->commercial_terms['access_label'] ?? 'zgodnie z wariantem' }}</td></tr>
        <tr><th>Rodzaj zakupu</th><td>{{ $item?->is_extension ? 'Przedłużenie istniejącego dostępu' : 'Nowy dostęp' }}</td></tr>
        @if($item?->is_extension && $item->previous_access_expires_at)
            <tr><th>Dotychczasowy koniec</th><td>{{ $item->previous_access_expires_at->timezone(config('app.timezone'))->format('d.m.Y') }}</td></tr>
        @endif
        <tr><th>Gwarancja satysfakcji z tego zakupu</th><td>
            @if((int) ($item?->satisfaction_guarantee_days ?? 0) > 0)
                {{ (int) $item->satisfaction_guarantee_days }} dni od faktycznego rozpoczęcia dostępu, nie od daty zamówienia.
                Zgłoszenia e-mail / telefon. Szczegóły dodatkowej gwarancji wymagają zatwierdzenia w Regulaminie.
            @else
                Brak dodatkowej gwarancji satysfakcji w tym zamówieniu.
            @endif
        </td></tr>
        <tr><th>Wymagania techniczne</th><td>{{ $item?->commercial_terms['technical_requirements'] ?? 'Urządzenie z internetem, przeglądarka, e-mail, odtwarzanie audio-wideo.' }}</td></tr>
    </table>

    <h2>Oświadczenie o wcześniejszym rozpoczęciu</h2>
    @if($order->early_performance_accepted_at && filled($order->early_performance_statement_text))
        <p>{{ $order->early_performance_statement_text }}</p>
        <p class="muted">
            Złożone {{ $order->early_performance_accepted_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
            (wersja {{ $order->early_performance_statement_version }}).
        </p>
    @else
        <p>Nie złożono oświadczenia o wcześniejszym rozpoczęciu świadczenia.</p>
    @endif
</body>
</html>
