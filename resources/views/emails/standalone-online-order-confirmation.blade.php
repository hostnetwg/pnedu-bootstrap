<!doctype html>
<html lang="pl">
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <h1 style="font-size: 20px;">Potwierdzenie zamówienia</h1>
    <p>Dzień dobry,</p>
    <p>przyjęliśmy zamówienie dotyczące szkolenia:</p>
    <p><strong>{{ $course->plainTitle() }}</strong><br>
        @if($course->start_date)
            {{ $course->start_date->locale('pl')->translatedFormat('l, d.m.Y H:i') }}
        @endif
    </p>
    <p><strong>Numer:</strong> {{ $order->ident }}<br>
       <strong>Do zapłaty:</strong> {{ number_format((float) $order->total_amount, 2, ',', ' ') }} PLN brutto<br>
       <strong>Sposób płatności:</strong> {{ strtoupper((string) $order->payment_gateway) }}</p>

    <p>Do wiadomości dołączono Regulamin pnedu.pl w wersji {{ $order->terms_version }} oraz wzór odstąpienia od umowy.</p>
    @if(in_array($order->customer_profile, ['person', 'jdg'], true))
        <p>Informacja o prawie odstąpienia: <a href="{{ route('withdrawal') }}">pnedu.pl/odstapienie-od-umowy</a>.</p>
    @endif
    @if($order->early_performance_accepted_at)
        <p><strong>Złożone oświadczenie:</strong> {{ config('legal.early_performance.statement') }}
            ({{ $order->early_performance_accepted_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}).</p>
    @endif

    <p>W razie pytań: <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>, tel. 501 654 274.</p>
</body>
</html>
