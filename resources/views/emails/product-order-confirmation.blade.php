<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Potwierdzenie zamówienia</title>
</head>
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.6;">
    @php
        $item = $order->orderItems->first();
        $termsVersion = $order->terms_version ?: config('legal.terms.current_version');
        $concludedAt = $order->contract_concluded_at ?: $order->order_date;
    @endphp
    <h1 style="font-size: 24px;">Dziękujemy za zamówienie</h1>
    <p>
        Platforma Nowoczesnej Edukacji Waldemar Grabowski,
        ul. Andrzeja Zamoyskiego 30/14, 09-320 Bieżuń,
        NIP 7392137630, <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>, +48 501 654 274
    </p>
    <p>Numer zamówienia: <strong>{{ $order->id }}</strong> ({{ $order->ident }})</p>
    <p>Data zawarcia umowy: <strong>{{ optional($concludedAt)->timezone(config('app.timezone'))?->format('d.m.Y H:i') }}</strong></p>
    <p>
        <strong>{{ $item?->product_name ?? $order->product_name }}</strong><br>
        Wariant: {{ $item?->commercial_terms['variant_name'] ?? $item?->metadata['price_name'] ?? '—' }}<br>
        {{ $item?->quantity ?? 1 }} × {{ number_format((float) ($item?->unit_price ?? $order->product_price), 2, ',', ' ') }} PLN<br>
        Razem: <strong>{{ number_format((float) ($item?->line_total ?? $order->product_price), 2, ',', ' ') }} PLN</strong>
    </p>
    <p>{{ $item?->commercial_terms['access_label'] ?? '' }}</p>
    @if($item?->is_extension)
        <p>To zamówienie przedłuża istniejący dostęp
            @if($item->previous_access_expires_at)
                (dotychczasowy koniec: {{ $item->previous_access_expires_at->timezone(config('app.timezone'))->format('d.m.Y') }}).
            @endif
        </p>
    @endif

    <h2 style="font-size: 18px;">Uczestnicy</h2>
    <ul>
        @foreach($item?->recipients ?? [] as $recipient)
            <li>{{ $recipient->first_name }} {{ $recipient->last_name }} — {{ $recipient->email }}</li>
        @endforeach
    </ul>

    @if($order->payment_mode === \App\Models\FormOrder::PAYMENT_MODE_DEFERRED_INVOICE)
        <p>Zapłata nastąpi na podstawie wystawionej faktury, w terminie {{ $order->invoice_payment_delay ?? $order->ptw }} dni. Złożenie zamówienia nie powoduje natychmiastowego pobrania płatności. Wystawienie faktury i nadanie dostępów są obsługiwane oddzielnie.</p>
    @else
        <p>Wybrano płatność online. Po złożeniu zamówienia następuje przekierowanie do operatora płatności. Dostęp zostaje nadany po potwierdzeniu płatności przez bramkę.</p>
    @endif

    @if((int) ($item?->satisfaction_guarantee_days ?? 0) > 0)
        <p>{{ (int) $item->satisfaction_guarantee_days }} dni dodatkowej gwarancji satysfakcji od faktycznego rozpoczęcia dostępu, nie od daty zamówienia. Zgłoszenia: kontakt@pnedu.pl lub 501 654 274. Gwarancja nie zastępuje ustawowego odstąpienia.</p>
    @endif

    @if($order->early_performance_accepted_at && filled($order->early_performance_statement_text))
        <p><strong>Złożone oświadczenie:</strong> {{ $order->early_performance_statement_text }}</p>
        <p>Data oświadczenia: {{ $order->early_performance_accepted_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }} (wersja {{ $order->early_performance_statement_version }}).</p>
    @endif

    <p>
        Do tej wiadomości dołączono PDF: specyfikację tego zamówienia, Regulamin pnedu.pl w wersji {{ $termsVersion }} oraz wzór odstąpienia od umowy.
        Informacja o odstąpieniu: <a href="{{ route('withdrawal') }}">pnedu.pl/odstapienie-od-umowy</a>.
    </p>
    <p>
        Szczegóły zamówienia:
        <a href="{{ route('online-courses.checkout.summary', $order->ident) }}">
            {{ route('online-courses.checkout.summary', $order->ident) }}
        </a>
    </p>
</body>
</html>
