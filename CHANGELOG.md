# Historia zmian — pnedu.pl

Krótka numeracja frontu. Szczegóły w `docs/` tego repo i w kanonie `pneadm/docs/`. Nowy numer wersji tylko po potwierdzeniu Waldemara. Hotfixy dopisujemy do bieżącej wersji.

## 1.2 — 2026-09-23

Dostęp do nagrania po szkoleniu.

- Formularz `/dostep-do-szkolenia/{token}` zbiera imię, nazwisko, e-mail i hasło dostępu do nagrania. Nowy adres dostaje konto na pnedu.pl i wejście do panelu szkoleń. Gdy konto już jest, hasła nie zmieniamy — zostaje logowanie dotychczasowym hasłem.
- Kanon: [RECORDING_ENROLLMENT.md](../pneadm/docs/RECORDING_ENROLLMENT.md)

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
