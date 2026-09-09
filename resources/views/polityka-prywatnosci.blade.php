@extends('layouts.app')

@section('title', 'Polityka prywatności i cookies – pnedu.pl')
@section('meta_description', 'Polityka prywatności pnedu.pl: dane osobowe, dostawcy, cookies niezbędne i analityczne oraz zarządzanie zgodą.')

@section('content')
<section class="py-5">
<div class="container">
<div class="card">
<div class="card-header"><h1 class="mb-0">Polityka prywatności i cookies</h1></div>
<div class="card-body">
    <p>Dbamy o ograniczenie danych i technologii śledzących do zakresu potrzebnego do działania serwisu i obsługi szkoleń. Szczegółowe podstawy prawne i prawa osób opisuje <a href="{{ route('rodo') }}">Informacja RODO</a>.</p>

    <h2 class="h4">Administrator</h2>
    <p>Platforma Nowoczesnej Edukacji Waldemar Grabowski, ul. Andrzeja Zamoyskiego 30/14, 09-320 Bieżuń, NIP 7392137630; <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>; tel. +48 501 654 274.</p>

    <h2 class="h4">Dane w formularzach i podczas realizacji usług</h2>
    <p>W formularzach zamówienia zbieramy dane kontaktowe, dane uczestników, dane do faktury, wybraną ofertę i płatność. Numer telefonu jest wymagany, ponieważ umożliwia pilny kontakt organizacyjny i rozliczeniowy, zwłaszcza gdy adres e-mail jest błędny, wiadomość nie dochodzi albo zamówienie z odroczoną płatnością wymaga wyjaśnienia.</p>
    <p>Dane uczestnika służą do zapewnienia dostępu do szkolenia na żywo, konta, materiałów, nagrania i zaświadczenia. Gdy przekazuje je szkoła, firma lub inny zamawiający, uczestnik otrzymuje informację o źródle danych w pierwszej wiadomości. Zobacz <a href="{{ route('rodo.art14') }}">informację art. 14 RODO</a>.</p>

    <h2 class="h4">Wiadomości e-mail i Sendy</h2>
    <p>Listy przypisane do konkretnego szkolenia w Sendy służą do komunikacji operacyjnej: potwierdzeń, przypomnień, linków do transmisji, materiałów, nagrań i zaświadczeń. Zapis na taką listę nie oznacza zgody na newsletter. Wiadomości marketingowe wysyłamy wyłącznie na podstawie odrębnej zgody, z możliwością jej wycofania.</p>

    <h2 class="h4">Płatności, faktury i pozostali dostawcy</h2>
    <p>W zależności od wybranej usługi dane mogą być przekazywane operatorom PayU lub Paynow, systemowi fakturowemu iFirma, dostawcom hostingu, poczty, wideokonferencji, platformy szkoleniowej, kopii zapasowych oraz obsługi prawnej i księgowej. Każdy otrzymuje tylko zakres potrzebny do wykonania swojej roli.</p>

    <h2 class="h4">Cookies niezbędne</h2>
    <p>Bez zgody możemy używać wyłącznie technologii koniecznych do działania serwisu, bezpieczeństwa, sesji, logowania, zachowania koszyka lub wznowienia rozpoczętego formularza. W tym samym zakresie zapisujemy własne, pierwszostronne zdarzenia lejka zamówienia (wejście na opis szkolenia i formularz, interakcje techniczne, złożenie zamówienia), aby obsłużyć zakup i panel operacyjny. Te dane nie są wysyłane do Google ani innych narzędzi reklamowych.</p>

    <h2 class="h4">Cookies analityczne</h2>
    <p>Google Analytics lub Google Tag Manager uruchamiamy dopiero po wyborze „Akceptuję analityczne”. Zgoda obejmuje wyłącznie analitykę Google; sygnały reklamowe <code>ad_storage</code>, <code>ad_user_data</code> i <code>ad_personalization</code> pozostają wyłączone. Meta Pixel nie jest aktywny. Zgodę możesz w każdej chwili zmienić przez link „Ustawienia cookies” w stopce.</p>

    <h2 class="h4">Logi techniczne</h2>
    <p>Serwer może automatycznie zapisywać adres IP, czas żądania, adres zasobu, informacje o przeglądarce i wyniku operacji. Logi wykorzystujemy do bezpieczeństwa, diagnostyki błędów i ochrony przed nadużyciami, a następnie usuwamy lub anonimizujemy zgodnie z ustalonym okresem retencji.</p>

    <h2 class="h4">Bezpieczeństwo i przechowywanie</h2>
    <p>Stosujemy kontrolę dostępu, szyfrowane połączenia, kopie zapasowe i ograniczenie dostępu pracowników oraz dostawców. Dane przechowujemy przez czas realizacji celu, okres wymagany prawem i okres potrzebny do dochodzenia lub obrony roszczeń.</p>

    <h2 class="h4">Kontakt i prawa</h2>
    <p>W sprawach prywatności napisz na <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>. Możesz skorzystać z praw opisanych w Informacji RODO i złożyć skargę do Prezesa UODO.</p>
</div>
</div>
</div>
</section>
@endsection
