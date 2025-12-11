{{-- Klauzula informacyjna RODO --}}
@extends('layouts.app')

@section('title', 'Informacja o przetwarzaniu danych osobowych')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h1 class="mb-0">
                        Informacja o&nbsp;przetwarzaniu danych osobowych
                        <small class="d-block fs-6">(art.&nbsp;13 Rozporządzenia Parlamentu Europejskiego i&nbsp;Rady (UE)&nbsp;2016/679 – RODO)</small>
                    </h1>
                </div>
                <div class="card-body">

    <h2>1. Administrator danych</h2>
    <p><strong>Platforma Nowoczesnej Edukacji Waldemar Grabowski</strong>, ul.&nbsp;Andrzeja&nbsp;Zamoyskiego&nbsp;30/14, 09‑320&nbsp;Bieżuń, NIP&nbsp;7392137630 (dalej: „Administrator”).</p>

    <h2>2. Dane kontaktowe</h2>
    <p>E‑mail: <a href="mailto:kontakt@nowoczesna-edukacja.pl">kontakt@nowoczesna-edukacja.pl</a><br>
       Tel.: <a href="tel:+48501654274">+48&nbsp;501&nbsp;654&nbsp;274</a></p>

    <h3>2.1. Inspektor Ochrony Danych</h3>
    <p>Administrator <strong>nie powołał</strong> Inspektora Ochrony Danych. We wszystkich sprawach związanych z&nbsp;przetwarzaniem danych prosimy o&nbsp;kontakt na ww.&nbsp;adres e‑mail.</p>

    <h2>3. Cele, podstawy prawne i&nbsp;zakres przetwarzania</h2>
    <table class="table table-bordered align-middle small">
        <thead class="table-light"><tr><th>Cel</th><th>Podstawa prawna</th><th>Zakres danych</th></tr></thead>
        <tbody>
            <tr>
                <td>Rejestracja konta użytkownika</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;b) RODO – wykonanie umowy</td>
                <td>imię, nazwisko, e‑mail, hasło</td>
            </tr>
            <tr>
                <td>Udział w&nbsp;szkoleniach (stacjonarnych i&nbsp;online)</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;b) RODO – wykonanie umowy</td>
                <td>imię, nazwisko, e‑mail, telefon<sup>*</sup>, nazwa szkoły/pracodawcy, dane do faktury</td>
            </tr>
            <tr>
                <td>Wystawianie certyfikatów/zaświadczeń</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;c) RODO – obowiązek prawny wynikający z&nbsp;§&nbsp;23 Rozp. MEN z&nbsp;23.03.2015&nbsp;r.</td>
                <td>imię, nazwisko, data i&nbsp;miejsce urodzenia</td>
            </tr>
            <tr>
                <td>Obsługa płatności i&nbsp;rozliczeń księgowych</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;b) RODO (umowa) oraz art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;c) RODO (obowiązek prawny)</td>
                <td>dane z&nbsp;faktury (nazwa, NIP, adres), identyfikator transakcji</td>
            </tr>
            <tr>
                <td>Newsletter – przesyłanie materiałów edukacyjnych i&nbsp;informacji o&nbsp;nowych usługach Administratora</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;a) RODO – zgoda</td>
                <td>imię, nazwisko, e‑mail, historia aktywności korespondencji</td>
            </tr>
            <tr>
                <td>Marketing własny (remarketing, segmentacja odbiorców)</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;f) RODO – prawnie uzasadniony interes polegający na&nbsp;promocji usług Administratora</td>
                <td>e‑mail (zaszyfrowany hash), cookies, identyfikatory reklamowe</td>
            </tr>
            <tr>
                <td>Ustalenie, dochodzenie lub obrona roszczeń</td>
                <td>art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;f) RODO – prawnie uzasadniony interes</td>
                <td>wszystkie wyżej wymienione dane konieczne do wykazania roszczenia</td>
            </tr>
        </tbody>
    </table>
    <p class="small">
        <sup>*</sup> podanie numeru telefonu jest dobrowolne, lecz ułatwia szybki kontakt w&nbsp;sprawach organizacyjnych.
    </p>

    <h2>4. Odbiorcy danych</h2>
    <p>Dane mogą być przekazywane następującym kategoriom odbiorców, z&nbsp;którymi Administrator zawarł umowy powierzenia:</p>
    <ul>
        <li><strong>SEOHOST Sp. z o.o.</strong> – hosting stron i&nbsp;baz danych (serwery w&nbsp;Polsce);</li>
        <li><strong>Amazon Web Services Europe</strong> (Amazon SES Europe, region Frankfurt) – wysyłka wiadomości e‑mail (serwery w&nbsp;Unii Europejskiej);</li>
        <li><strong>PayU S.A.</strong> oraz <strong>mElements S.A.</strong> (Paynow) – operatorzy płatności online;</li>
        <li><strong>Publigo Sp. z o.o.</strong> – platforma kursów online (wersja GO);</li>
        <li><strong>Biuro Rachunkowe MAKI</strong> – obsługa księgowa;</li>
        <li><strong>Meta Platforms Ireland Ltd.</strong> (Facebook Pixel) oraz <strong>Google Ireland Ltd.</strong> (Google Analytics) – usługi marketingowe i&nbsp;analityczne.</li>
    </ul>

    <h2>5. Przekazywanie danych poza Europejski Obszar Gospodarczy</h2>
    <p>Dane mogą być przesyłane do&nbsp;państw trzecich (USA) w&nbsp;związku z&nbsp;korzystaniem z&nbsp;Facebook&nbsp;Pixel oraz Google&nbsp;Analytics. Transfer odbywa się na&nbsp;podstawie <strong>standardowych klauzul umownych</strong> zatwierdzonych przez Komisję Europejską lub decyzji stwierdzających odpowiedni stopień ochrony.</p>
    <p><strong>Uwaga:</strong> Amazon SES działa w&nbsp;regionie Europe (Frankfurt), więc dane e‑mailowe nie są przekazywane poza Europejski Obszar Gospodarczy.</p>

    <h2>6. Okres przechowywania danych</h2>
    <ul>
        <li>dane związane z&nbsp;umową szkoleniową – przez okres trwania umowy, a&nbsp;następnie do&nbsp;6&nbsp;lat (przedawnienie roszczeń);</li>
        <li>dokumenty księgowe – 5&nbsp;lat od końca roku podatkowego, w&nbsp;którym wystawiono fakturę;</li>
        <li>dane newsletterowe – do czasu wycofania zgody;</li>
        <li>dane wykorzystywane w&nbsp;marketingu – do skutecznego wniesienia sprzeciwu;</li>
        <li>dane niezbędne do obrony przed roszczeniami – do czasu ich prawomocnego zakończenia.</li>
    </ul>

    <h2>7. Zautomatyzowane decyzje i&nbsp;profilowanie</h2>
    <p>Administrator stosuje <strong>profilowanie</strong> polegające na&nbsp;segmentacji odbiorców newslettera według kryteriów (np. ukończone kursy, zainteresowania). Profilowanie nie wywołuje skutków prawnych ani w&nbsp;istotny sposób nie wpływa na&nbsp;osoby, których dane dotyczą.</p>

    <h2>8. Prawa osób, których dane dotyczą</h2>
    <p>Przysługuje Ci prawo do:</p>
    <ol>
        <li>dostępu do swoich danych i&nbsp;otrzymania ich kopii,</li>
        <li>sprostowania (poprawiania) danych,</li>
        <li>usunięcia danych (<em>prawo do bycia zapomnianym</em>),</li>
        <li>ograniczenia przetwarzania,</li>
        <li>przenoszenia danych,</li>
        <li>sprzeciwu wobec przetwarzania opartego na art.&nbsp;6 ust.&nbsp;1 lit.&nbsp;f) RODO,</li>
        <li>wycofania zgody w&nbsp;dowolnym momencie (bez wpływu na&nbsp;zgodność z&nbsp;prawem przetwarzania sprzed jej cofnięcia).</li>
    </ol>

    <h2>9. Prawo do cofnięcia zgody</h2>
    <p>Zgodę na otrzymywanie newslettera możesz wycofać w&nbsp;każdej chwili, klikając link <em>„wypisz się”</em> w&nbsp;stopce każdej wiadomości lub pisząc na adres <a href="mailto:kontakt@nowoczesna-edukacja.pl">kontakt@nowoczesna-edukacja.pl</a>.</p>

    <h2>10. Obowiązek / dobrowolność podania danych</h2>
    <p>Podanie danych jest <strong>dobrowolne</strong>, lecz niezbędne do zawarcia umowy szkoleniowej lub otrzymywania newslettera. Konsekwencją niepodania danych będzie brak możliwości wzięcia udziału w&nbsp;szkoleniu lub otrzymywania materiałów marketingowych.</p>

    <h2>11. Prawo wniesienia skargi</h2>
    <p>Jeżeli uważasz, że przetwarzamy Twoje dane niezgodnie z&nbsp;prawem, przysługuje Ci prawo złożenia skargi do Prezesa Urzędu Ochrony Danych Osobowych (ul.&nbsp;Stawki&nbsp;2, 00‑193&nbsp;Warszawa, <a href="https://uodo.gov.pl" target="_blank" rel="noopener">uodo.gov.pl</a>).</p>

    <h2>12. Źródło danych</h2>
    <p>Dane pozyskujemy bezpośrednio od Ciebie poprzez formularze rejestracyjne, zapisu na newsletter lub w&nbsp;ramach komunikacji e‑mailowej.</p>

    <h2>13. Zmiany dokumentu</h2>
    <p>Administrator może aktualizować niniejszą informację. Aktualna wersja będzie zawsze dostępna na stronie, a&nbsp;o&nbsp;istotnych zmianach poinformujemy e‑mailowo.</p>

    <h2>14. Data obowiązywania</h2>
    <p>Niniejszy dokument obowiązuje od <strong>18&nbsp;maja&nbsp;2025&nbsp;r.</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
