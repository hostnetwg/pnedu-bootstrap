@php
    $mailProfile = $order->customer_profile ?? null;
    $mailTermsVersion = $order->terms_version ?: config('legal.terms.current_version');
    $mailIsProtectedCustomer = in_array($mailProfile, ['person', 'jdg'], true);
    $mailOrdererEmail = strtolower(trim((string) $order->orderer_email));
    $mailHasThirdPartyParticipant = $order->relationLoaded('participants')
        && $order->participants->contains(
            fn ($participant) => strtolower(trim((string) $participant->participant_email)) !== $mailOrdererEmail
        );
@endphp

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 24px 0; border: 1px solid #e0e0e0; border-radius: 6px; background-color: #fafafa;">
<tr><td style="padding: 18px 22px;">
    <p style="margin: 0 0 8px;"><strong>Dokumenty dotyczące zamówienia</strong></p>
    <p style="margin: 0 0 8px; font-size: 13px;">
        Do wiadomości dołączono Regulamin pnedu.pl w wersji {{ $mailTermsVersion }}
        oraz wzór odstąpienia od umowy.
    </p>
    @if($mailIsProtectedCustomer)
        <p style="margin: 0 0 8px; font-size: 13px;">
            Informacja o prawie odstąpienia:
            <a href="{{ route('withdrawal') }}">pnedu.pl/odstapienie-od-umowy</a>.
        </p>
    @endif
    @if($order->early_performance_accepted_at)
        <p style="margin: 0; font-size: 13px;">
            <strong>Złożone oświadczenie:</strong>
            {{ $order->early_performance_statement_text ?: config('legal.early_performance.statement') }}
            ({{ $order->early_performance_accepted_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}).
        </p>
    @endif
    @if($mailHasThirdPartyParticipant)
        <p style="margin: 8px 0 0; font-size: 13px;">
            <strong>Informacja dla uczestnika:</strong>
            dane uczestnika otrzymaliśmy od zamawiającego „{{ $order->orderer_name ?: $order->buyer_name }}”
            w celu organizacji udziału w szkoleniu. Pełna informacja:
            <a href="{{ route('rodo.art14') }}">pnedu.pl/rodo-art-14</a>.
        </p>
    @endif
</td></tr>
</table>
