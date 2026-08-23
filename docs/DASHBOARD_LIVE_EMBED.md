# Osadzony pokój ClickMeeting na pnedu.pl

Data: 2026-08-21  
Projekty: `pnedu` + `pneadm`  
Kanon UI / tokenów: ten plik. Widoczność przycisku „Dołącz”: [DASHBOARD_LIVE_MEETING.md](./DASHBOARD_LIVE_MEETING.md). Provision / maile adm: `pneadm/docs/FORM_ORDERS_PNEDU_PROVISION.md`. **Strategia platform-first:** `pneadm/docs/strategy/PNEDU_PLATFORM_FIRST.md`.

## Cel

Per szkolenie admin wybiera **jeden** sposób wejścia na żywo z konta uczestnika:

- **Pokój na ClickMeeting** — zewnętrzny CM (nowa karta / auto-login), **albo**
- **Osadzony pokój na pnedu.pl** — pokój w iframe na koncie (`/dashboard/szkolenia/{participant}/transmisja`), z belką PNE i pełnym ekranem.

Obie ścieżki korzystają z tego samego rekordu `participant_live_access` (token CM).

## Decyzje (Waldemar)

| # | Decyzja |
|---|--------|
| 1 | Radio w adm (nie dwa checkboxy): **Pokój na ClickMeeting** (domyślnie) **albo** **Osadzony pokój na pnedu.pl** |
| 2 | Jeden zielony przycisk „Dołącz do spotkania na żywo” na `/dashboard/szkolenia` i homepage live — różni się tylko ikoną (zewnętrzne okno vs ekran) |
| 3 | Mobile + tryb osadzony: ten sam przycisk → **redirect** do CM z auto-login (embed RWD nie wspierany) |
| 4 | `CLICKMEETING_API_TOKEN` także w `.env` **pnedu** (wywołania API po stronie konta) |
| 5 | **Jeden aktywny token** na uczestnika; przy ponownym wejściu embed: nowy token + `DELETE` starego w CM |
| 6 | Anty-sharing: slot obecności Laravel (1 sesja / uczestnik) + wspólny token z mailem |
| 7 | Embed widoczny dla **wszystkich** uczestników kursu (wg radio w adm). `CLICKMEETING_EMBED_ALLOWLIST` opcjonalny; pusty/brak = bez ograniczenia |
| 8 | Przy trybie embed admin ma checkbox **Link w e-mailu do osadzonego w PNEDU pokoju** (domyślnie ON). ON = główny link maila do `/transmisja` + alternatywny bezpośredni CM; OFF = mail jak dotychczas (bezpośredni CM). |

## Adm (pneadm)

Edycja / tworzenie kursu → sekcja ID wydarzenia ClickMeeting → **Wejście do pokoju dla uczestnika** (radio):

| Opcja UI | Zapis w DB |
|----------|------------|
| Pokój na ClickMeeting | `clickmeeting_join_enabled = true`, `embed_on_pnedu = false` |
| Osadzony pokój na pnedu.pl | `clickmeeting_join_enabled = false`, `embed_on_pnedu = true` |

Kolumny:

- `course_online_details.clickmeeting_join_enabled` (bool, default **true**)
- `course_online_details.embed_on_pnedu` (bool, default **false**)
- `course_online_details.embed_email_link_enabled` (bool, default **true**) — działa tylko gdy `embed_on_pnedu = true`
- `participant_live_access.embed_token_consumed_at` — lokalna flaga „ten token już wpuszczono w embed” (bez czekania na `first_use_date` CM)

Migracje (pneadm):

- `2026_08_20_200210_…embed_on_pnedu…`
- `2026_08_21_181500_…clickmeeting_join_enabled…`
- `2026_08_21_182800_…normalize_live_room_mode…` (ustawia dokładnie jedną opcję na istniejących wierszach)
- `2026_08_22_131100_…embed_email_link_enabled…`
- `2026_08_20_230500_…embed_token_consumed_at…`

```bash
cd /home/hostnet/WEB-APP/pneadm && sail artisan migrate
```

Pola formularza:

- `live_room_mode` = `clickmeeting` \| `embed_pnedu` → `CoursesController::resolveLiveRoomFlags()`
- `embed_email_link_enabled` — checkbox widoczny przy `embed_pnedu`

## Pnedu — przepływ użytkownika

### Pierwszy zakup (nowe konto)

1. Mail z adm: **Ustaw hasło na pnedu.pl** (główny przycisk, przed sekcją spotkania).
2. Link `/ustaw-haslo/{token}?email=…&redirect=/dashboard/szkolenia` — po haśle użytkownik trafia na listę szkoleń (nie do pokoju; transmisja dopiero w oknie live, 2 h przed startem).
3. Po ustawieniu hasła: **autologin** (`NewPasswordController`) + przekierowanie na `/dashboard/szkolenia`.
4. Na liście widać szkolenie, licznik i przycisk „Dołącz do spotkania na żywo” (aktywny w oknie live).

Szczegóły maila provision: `pneadm/docs/FORM_ORDERS_PNEDU_PROVISION.md`.

### Wejście na żywo (konto istniejące)

1. Uczestnik ma zakup / provision → w oknie live (2 h przed startem do końca) widzi przycisk wg radio.
2. **ClickMeeting:** `joinUrl` = `room_url` + token z `participant_live_access` (lub `meeting_link`).
3. **Osadzony:** link do `route('dashboard.szkolenia.transmisja')` (+ `fullscreen=1` z homepage/listy).
4. Desktop embed: iframe CM (`?bare=1`), auto pełny ekran (gate modal). Strona `/transmisja` **bez menu i stopki pnedu** (layout `transmisja-bare`) — tylko zielony pasek PNE + okno CM. **Widok normalny:** branding po lewej, przyciski po prawej. **Pełny ekran:** ten sam pasek + „Wyjdź z pełnego ekranu” / „Zamknij”.
5. Esc / „Wyjdź z pełnego ekranu” = tylko wyjście z FS (pokój zostaje).
6. „Zamknij transmisję” = modal → zwolnienie slotu obecności + **przekierowanie na** `/po-szkoleniu?course={id}` (strona podziękowania). W trybie fullscreen host (`bare=1`) parent dostaje `postMessage` i też idzie na tę stronę.
7. **Auto po „Zakończ dla wszystkich”:** `/transmisja` polluje `GET …/transmisja/meeting-status` (co ~12 s, cache CM 12 s). Gdy API CM ma `status=inactive`, uczestnik jest automatycznie kierowany na `/po-szkoleniu`.

**Uwaga (embed vs CM full page):** ustawienie thank-you URL w CM przekierowuje przeglądarkę tylko w pełnym oknie ClickMeeting. W **iframe** na pnedu CM często zostawia czarny ekran końca — dlatego pnedu:
- przy zamykaniu transmisji sam prowadzi na `/po-szkoleniu`;
- polluje status wydarzenia w CM i po `inactive` zamyka embed automatycznie;
- jeśli CM jednak załaduje `/po-szkoleniu` w iframe, skrypt na stronie podziękowania (i listener `load` iframe) wyciąga widok do top-level.

**Zalogowany vs gość na `/po-szkoleniu`:** zalogowany → layout z menu i stopką pnedu + CTA „Przejdź do Twoich zasobów”; gość (redirect z CM poza kontem) → uproszczony layout + „Zaloguj się”. Tekst zależy od zasobów kursu w adm: materiały (`course_file_links`), nagranie (`course_videos`), status zaświadczeń (`courses.certificate_download_status`: `download_enabled` / `in_preparation` / `no_certificate`). Gdy kurs ma aktywną ankietę (`course_survey_links`) — dodatkowe CTA „Wypełnij ankietę”.

**Dwie fazy (tylko gdy jest `?course=` lub `?event=`):**
- **Wczesne wyjście** — przed progiem końcowym: krótki komunikat (przed startem / „wyszedłeś ze spotkania”), opcjonalnie **Wróć do transmisji** (gdy link live aktywny — URL embed **bez** `?fullscreen=1`, widok normalny), bez listy zasobów i ankiety.
- **Koniec szkolenia** — pełny komunikat (materiały, nagranie, zaświadczenie, ankieta): gdy `now >= end_date − min(20% czasu trwania, 45 min)`, po `end_date`, albo wcześniej gdy CM `inactive` (flaga w cache z polla `/transmisja`, bez dodatkowego API na thank-you).
- Brak `end_date` w kursie → koniec szacowany jako `start_date + 1 h`.

### Tokeny (model)

1. Pierwsze `/transmisja`: używa `participant_live_access.token` (z provision / maila), ustawia `embed_token_consumed_at`.
2. Kolejne wejście (flaga / `first_use_date` CM / `?rejoin=1` / „Wejdź ponownie”): `POST …/tokens` → **`DELETE` starego** → zapis tylko nowego w DB.
3. Klasyczny „Dołącz” (gdy radio = CM) i **maile adm** używają tego samego tokenu w DB; po zużyciu / rotacji stary link CM **nie** otwiera drugiej równoległej sesji.

Limit CM (dokumentacja vendor): max tokenów na wydarzenie ≈ **4 ×** max uczestników planu; miejsca w pokoju = concurrent attendees (np. 350).

### Anty-sharing

- `LiveTransmissionPresenceService` — 1 sesja Laravel na `participant_id` (drugie urządzenie na `/transmisja` → odmowa z komunikatem).
- Jeden aktywny token CM — mail z bezpośrednim linkiem i embed nie dostają dwóch wolnych „biletów” naraz.

## Kod (pnedu)

| Element | Ścieżka |
|---------|---------|
| Flagi + URL przycisku | `app/Services/DashboardCourseLiveAccessService.php`, `app/Support/DashboardCourseLiveAccess.php` |
| Przycisk wspólny | `resources/views/partials/live-join-button.blade.php` |
| Strona embed | `resources/views/dashboard/szkolenia-transmisja.blade.php` |
| Serwis embed / token | `app/Services/LiveTransmissionService.php` |
| API CM | `app/Services/ClickMeetingService.php` (`deactivateTokens` itd.) |
| Obecność | `app/Services/LiveTransmissionPresenceService.php` |
| Allowlista (opcjonalna) | `CLICKMEETING_EMBED_ALLOWLIST` — domyślnie pusta = wszyscy |
| Homepage | ten sam serwis + `HomepageLiveMeetingNotice` — [HOMEPAGE_LIVE_MEETING.md](./HOMEPAGE_LIVE_MEETING.md) |

## Relacja do maili adm (pneadm)

| Akcja adm | Co robi względem embed |
|-----------|-------------------------|
| **Dodaj uczestnika do PNEDU** | Tworzy konto + provision CM + mail. Gdy `embed_email_link_enabled = true`, główny link prowadzi do `/transmisja`, a bezpośredni CM jest alternatywą; gdy false — mail jak dawniej z bezpośrednim CM. |
| **Wyślij link do live** | Ta sama reguła dla maila live uczestnika. |

Embed w mailu jest sterowany osobnym checkboxem, żeby można było mieć przycisk embed w panelu pnedu, ale nadal wysyłać stare maile z bezpośrednim CM.

## Strona podziękowania po spotkaniu (ClickMeeting → pnedu.pl)

W ustawieniach wydarzenia CM (**Edycja → Ustawienia → Działania follow-up → Strona z podziękowaniem z własnym adresem URL**) wklej adres strony na pnedu.pl.

| Środowisko | URL (zalecany — z ID kursu w adm) |
|------------|-------------------------------------|
| Produkcja | `https://pnedu.pl/po-szkoleniu?course=563` |
| Lokalnie | `http://localhost:8081/po-szkoleniu?course=563` |

- `course` = ID szkolenia w adm (`courses.id`). Przy zapisie kursu online z platformą ClickMeeting i wypełnionym **ID wydarzenia ClickMeeting** adm **automatycznie** ustawia ten URL w CM przez API.
- Alternatywnie (ręcznie w CM): `?event=10166300` — ID wydarzenia z panelu CM.
- Gdy kurs istnieje w bazie, strona pokazuje **tytuł szkolenia**, **prowadzącego** oraz **datę i godzinę rozpoczęcia**.
- Bez parametru `course` / `event` działa ogólna strona podziękowania z CTA na logowanie / dashboard.
- Trasa: `GET /po-szkoleniu` (`PostTrainingThankYouController`), layout `noindex`.

**Strategia platform-first:** uczestnik po wyjściu z pokoju CM trafia na pnedu.pl z informacją o nagraniu, materiałach i zaświadczeniu — zamiast domyślnej strony ClickMeeting.

**Embed (iframe):** redirect CM nie zawsze „wychodzi” z iframe — patrz punkt 6 w przepływie UX powyżej.

**Awaria pnedu.pl (502):** redirect CM na tę stronę nie zadziała; operacyjnie można ponownie wysłać zaproszenie z zakładki **Zaproszenia** w panelu CM ([przykład](https://account-panel.clickmeeting.com/10166300#invites)).

## Testy

```bash
# pnedu
sail test --filter=LiveTransmission
sail test --filter=DashboardCourseLiveAccessServiceTest
sail test --filter=PostTrainingThankYouPageTest

# pneadm (provision / live mail — bez zmiany CTA embed)
sail test --filter=ParticipantLiveMeetingLink
```

## Deploy

1. **pneadm:** `sail artisan migrate` (flagi radio + `embed_token_consumed_at` jeśli jeszcze nie).
2. **pnedu:** `.env` — `CLICKMEETING_API_TOKEN` (allowlista niepotrzebna, domyślnie wszyscy); `config:clear` / deploy jak zwykle.
3. W edycji kursu ustawić radio na **Osadzony pokój** dla szkoleń eksperymentalnych.
