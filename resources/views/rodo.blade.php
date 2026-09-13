@extends('layouts.app')

@section('title', 'Informacja RODO – pnedu.pl')
@section('meta_description', 'Informacja o przetwarzaniu danych zamawiających, osób kontaktowych i uczestników szkoleń pnedu.pl.')

@section('content')
<section class="py-5">
<div class="container">
<div class="card">
<div class="card-header"><h1 class="mb-0">Informacja o przetwarzaniu danych osobowych</h1></div>
<div class="card-body">
    <h2 class="h4">1. Administrator i kontakt</h2>
    <p>Administratorem danych jest <strong>Platforma Nowoczesnej Edukacji Waldemar Grabowski</strong>, ul. Andrzeja Zamoyskiego 30/14, 09-320 Bieżuń, NIP 7392137630. Kontakt: <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>, tel. +48 501 654 274.</p>

    <h2 class="h4">2. Cele i podstawy przetwarzania</h2>
    <ul>
        <li><strong>Zamówienie i realizacja szkolenia lub kursu nagranego</strong> — zawarcie i wykonanie umowy albo działania przed jej zawarciem (art. 6 ust. 1 lit. b RODO), a dla osoby kontaktowej lub uczestnika niebędącego stroną umowy — uzasadniony interes administratora polegający na organizacji świadczenia zamówionego przez klienta (art. 6 ust. 1 lit. f RODO). W związku ze szkoleniami i kursami nagranymi przetwarzamy dane niezbędne do przyjęcia i realizacji zamówienia, przypisania dostępu właściwym uczestnikom, utworzenia i obsługi konta, udostępniania lekcji i materiałów, ustalenia początku i końca dostępu oraz obsługi jego przedłużenia. Obsługujemy także zamówienia z przyszłą datą udostępnienia kursu, płatności, reklamacje, odstąpienia i zgłoszenia w ramach gwarancji satysfakcji.</li>
        <li><strong>Faktury, księgowość i obowiązki podatkowe</strong> — obowiązek prawny (art. 6 ust. 1 lit. c RODO).</li>
        <li><strong>Kontakt organizacyjny, wyjaśnienie błędnego adresu e-mail, obsługa płatności i należności</strong> — wykonanie umowy oraz uzasadniony interes administratora (art. 6 ust. 1 lit. b i f RODO). Dlatego numer telefonu jest wymagany w płatnym formularzu.</li>
        <li><strong>Konto uczestnika, dostęp do live, nagrania, lekcje kursu i materiałów, wystawienie zaświadczenia</strong> — wykonanie umowy lub uzasadniony interes związany z jej wykonaniem (art. 6 ust. 1 lit. b lub f RODO); obowiązki dokumentacyjne placówki doskonalenia — art. 6 ust. 1 lit. c RODO, jeżeli mają zastosowanie. Oznaczamy ukończenie lekcji, gdy uczestnik sam to potwierdzi; nie zapisujemy automatycznego czasu oglądania nagrania.</li>
        <li><strong>Reklamacje, roszczenia, bezpieczeństwo i dowody złożonych oświadczeń</strong> — obowiązek prawny oraz uzasadniony interes administratora (art. 6 ust. 1 lit. c i f RODO).</li>
        <li><strong>Newsletter i marketing elektroniczny</strong> — wyłącznie po odrębnej zgodzie, którą można wycofać w dowolnej chwili (art. 6 ust. 1 lit. a RODO oraz właściwe przepisy o komunikacji elektronicznej).</li>
        <li><strong>Analityka serwisu</strong> — tylko po zgodzie na opcjonalne cookies lub podobne technologie (art. 6 ust. 1 lit. a RODO). Meta Pixel pozostaje wyłączony.</li>
    </ul>

    <h2 class="h4">3. Zakres i źródło danych</h2>
    <p>Przetwarzamy dane podane w formularzu, w szczególności dane kontaktowe, dane do faktury i dane uczestnika, dane o szkoleniu i płatności oraz techniczne dane bezpieczeństwa. Dane uczestnika możemy otrzymać od szkoły, pracodawcy, firmy albo innego zamawiającego. Pełna informacja dla takich osób znajduje się na stronie <a href="{{ route('rodo.art14') }}">RODO — dane otrzymane od zamawiającego</a>.</p>

    <h2 class="h4">4. Odbiorcy danych</h2>
    <p>Dane mogą otrzymać podmioty wspierające nas jako procesorzy lub odrębni administratorzy, w zakresie potrzebnym do danej usługi: hosting i infrastruktura IT, poczta elektroniczna, system wysyłki wiadomości operacyjnych Sendy, platformy szkoleniowe i — przy szkoleniach na żywo — systemy wideokonferencyjne, przy kursach nagranych także dostawcy odtwarzania osadzonych nagrań (YouTube, Vimeo), operatorzy płatności PayU i Paynow, księgowość i system fakturowy iFirma, dostawcy archiwizacji i obsługi prawnej oraz uprawnione organy publiczne. Nie przekazujemy każdemu dostawcy danych ze wszystkich zamówień.</p>

    <h2 class="h4">5. Transfer poza EOG</h2>
    <p>Niektórzy dostawcy technologiczni mogą przetwarzać dane poza Europejskim Obszarem Gospodarczym. W takim przypadku stosujemy mechanizmy wymagane przez RODO, w szczególności decyzję stwierdzającą odpowiedni stopień ochrony albo standardowe klauzule umowne i odpowiednie zabezpieczenia.</p>

    <h2 class="h4">6. Okres przechowywania</h2>
    <p>Dane umowne i rozliczeniowe przechowujemy przez okres realizacji umowy, wymagany prawem okres dokumentacji podatkowej i księgowej oraz do upływu terminów przedawnienia roszczeń. Dane konta i dostępu — przez okres korzystania z usługi oraz uzasadniony okres archiwalny. Dane marketingowe — do wycofania zgody lub zgłoszenia sprzeciwu. Logi bezpieczeństwa i dowody oświadczeń — przez okres potrzebny do ochrony praw i wykazania zgodności.</p>

    <h2 class="h4">7. Prawa</h2>
    <p>Masz prawo żądać dostępu do danych, ich sprostowania, usunięcia lub ograniczenia przetwarzania, przeniesienia danych, a przy podstawie z art. 6 ust. 1 lit. f RODO — wnieść sprzeciw. Zgodę można wycofać bez wpływu na zgodność wcześniejszego przetwarzania. Możesz wnieść skargę do Prezesa Urzędu Ochrony Danych Osobowych.</p>

    <h2 class="h4">8. Obowiązek podania danych</h2>
    <p>Dane oznaczone jako wymagane są potrzebne do przyjęcia i realizacji zamówienia, rozliczenia i kontaktu organizacyjnego. Bez nich realizacja może być niemożliwa. Podanie danych marketingowych jest dobrowolne.</p>

    <h2 class="h4">9. Zautomatyzowane decyzje</h2>
    <p>Nie podejmujemy wobec klientów decyzji wywołujących skutki prawne wyłącznie w sposób zautomatyzowany, w tym w oparciu o profilowanie.</p>
</div>
</div>
</div>
</section>
@endsection
