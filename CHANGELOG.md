# Historia zmian — pnedu.pl

Krótka numeracja frontu. Szczegóły w `docs/` tego repo i w kanonie `pneadm/docs/`. Nowy numer wersji tylko po potwierdzeniu Waldemara. Hotfixy dopisujemy do bieżącej wersji.

## 1.3 — 2026-11-25

Panel trenera pod `/trener`. Osobny widok przy tym samym koncie. Trener widzi udostępnione zapytania o szkolenia zamknięte i wpisuje termin.

- Kanon: [TRAINER_PORTAL.md](../pneadm/docs/TRAINER_PORTAL.md)
- **Hotfix 2026-11-25:** na zapytaniu telefon jest w grupach (np. +48 501 654 274). Pod datą jest pole Notatka. Widzi ją administrator przy terminie.
- **Hotfix 2026-11-25:** w menu strony przycisk **Panel trenera (liczba)** wygląda jak „Twoje zasoby”, ale jest zielony. Liczba to udostępnione zapytania. W panelu logo PNE po lewej wraca na stronę startową.
- **Hotfix 2026-11-25:** na liście zapytań i na zapytaniu jest data zapytania.
- **Hotfix 2026-11-25:** w panelu trenera menu po lewej ma **Kalendarz** i **Zapytania**. Kalendarz pokazuje zaplanowane szkolenia tego trenera.
- **Hotfix 2026-11-25:** w panelu trenera nie ma linku „Panel uczestnika”. Przy przycisku **Panel trenera** na stronie głównej czerwone kółko pokazuje liczbę nieodczytanych zgłoszeń.
- **Hotfix 2026-11-25:** administrator w menu konta ma **Panele trenerów**. Podgląd otwiera panel wybranego instruktora bez zmiany hasła. Na górze jest powrót do własnego konta.
- **Hotfix 2026-11-25:** na zapytaniu trener może zaznaczyć, że nie poprowadzi szkolenia, i dopisać krótkie wyjaśnienie. Administrator widzi je na sprawie.
- **Hotfix 2026-11-25:** przy zapisie terminu zaznaczony test zapisuje datę tylko w historii sprawy, na pomarańczowo. Odznaczenie wpisuje datę i notatkę w ustaleniach.
- **Hotfix 2026-11-25:** na zapytaniu wiadomość dopisana przez administratora jest osobno, jako wiadomość od administratora.
- **Hotfix 2026-11-25:** na zapytaniu treść zapytania jest od razu pod danymi kontaktowymi. Wiadomość od administratora jest pod nią.
- **Hotfix 2026-11-25:** po zapisie terminu trener widzi datę i godzinę oraz notatkę. Formularz wraca po **Zmień**.
- **Hotfix 2026-11-25:** zaznaczony test zapisuje termin tak samo jak zwykłe ustalenie. U trenera i w historii sprawy jest oznaczony jako test, a pod datą w ustaleniach jest czerwony dopisek DATA TESTOWA.

## 1.2 — 2026-09-23

Dostęp do nagrania po szkoleniu.

- **Hotfix 2026-11-18:** mail o dostępie do kursu online buduje link „Ustaw hasło” z adresu strony (`APP_URL`, na produkcji pnedu.pl). Wejście z panelu ADM nie podmienia go na wewnętrzną nazwę serwera.
- **Hotfix 2026-11-17:** na stronie weryfikacji e-mail jest informacja, że link może dotrzeć na skrzynkę z małym opóźnieniem. To samo po ponownej wysyłce.
- Formularz `/dostep-do-szkolenia/{token}` zbiera imię, nazwisko, e-mail i hasło dostępu do nagrania. Nowy adres dostaje konto na pnedu.pl i wejście do panelu szkoleń. Gdy konto już jest, hasła nie zmieniamy — zostaje logowanie dotychczasowym hasłem.
- Kanon: [RECORDING_ENROLLMENT.md](../pneadm/docs/RECORDING_ENROLLMENT.md)
- **Hotfix 2026-10-19:** formularz zamówienia (legacy i V2) oraz checkout produktu wymagają e-maila z domeną z końcówką (np. `.pl`). Adresy w stylu `nazwa@gminakrzeszowice` są odrzucane już przy zapisie. Kanon: [ORDER_FORM_V2.md](docs/ORDER_FORM_V2.md)
- Hotfix 2026-09-29: sekcja „Dane na żywo” na stronie głównej ma prawdziwe liczby już w HTML. Gdy wyliczenie się nie uda, zostaje ostatni poprawny odczyt, a nie zero. Count-up od zera działa dopiero w przeglądarce (progressive enhancement); źródło HTML i crawler bez JS nadal widzą finalne wartości.
- Kanon: [STATISTICS_CALCULATION.md](STATISTICS_CALCULATION.md)

## 1.1 — 2026-09-19

Belka zasobów i oferta na `/transmisja`.

- Na osadzonym live przyciski **Pobierz materiały**, **Wypełnij ankietę** i **Pobierz zaświadczenie** (ikona dyplomu) wchodzą i schodzą bez odświeżania strony. Klik: nowa karta + zejście z pełnego ekranu.
- Druga belka: oferta kolejnego szkolenia, przycisk **Zamawiam szkolenie** otwiera opis kursu (nie od razu formularz).
- **Rejestracja: lista obecności** na belce jest ukryta, dopóki wejście wymaga konta pnedu i wpisu na liście.
- Kanon: [DASHBOARD_LIVE_EMBED.md](docs/DASHBOARD_LIVE_EMBED.md), [LIVE_EMBED_RESOURCE_BAR.md](../pneadm/docs/LIVE_EMBED_RESOURCE_BAR.md)

## 1.0 — 2026-09-18

Start numeracji. Stan produkcji z 18.09.2026.

- Konto uczestnika, dashboard szkoleń, wejście na live (osadzony pokój na pnedu.pl i rezerwowy link ClickMeeting). Wejście na `/transmisja` czyta aktualny typ dostępu z ClickMeeting i dopasowuje snapshot; brak klucza API pnedu jest odróżniony od tokenu uczestnika.
- Katalog i sprzedaż kursów nagranych, checkout, fulfillment dostępu.
- Historia tej aplikacji w panelu ADM ([pnedu.pl v …](/changelog/pnedu), pod Kontem, po linii), bez wpisu na publicznej stronie pnedu.pl. Przy pozycji menu jest licznik nieprzeczytanych zmian (stan w koncie operatora ADM).
- Kanon: [DASHBOARD_LIVE_EMBED.md](docs/DASHBOARD_LIVE_EMBED.md), [pneadm/docs/PRODUCT_COMMERCE.md](../pneadm/docs/PRODUCT_COMMERCE.md)
