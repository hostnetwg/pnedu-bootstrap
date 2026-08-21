# Osadzony pokój ClickMeeting na pnedu.pl

Data: 2026-08-21  
Projekty: `pnedu` + `pneadm`  
Kanon UI / tokenów: ten plik. Widoczność przycisku „Dołącz”: [DASHBOARD_LIVE_MEETING.md](./DASHBOARD_LIVE_MEETING.md). Provision / maile adm: `pneadm/docs/FORM_ORDERS_PNEDU_PROVISION.md`.

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
| 7 | Przycisk / wejście embed tymczasowo tylko dla allowlisty e-mail (`CLICKMEETING_EMBED_ALLOWLIST`; puste = wszyscy) |
| 8 | **Maile (provision FORM + „Wyślij link do live”) na razie bez zmian** — nadal bezpośredni URL ClickMeeting; radio steruje tylko UI na pnedu (2026-08-21) |

## Adm (pneadm)

Edycja / tworzenie kursu → sekcja ID wydarzenia ClickMeeting → **Wejście do pokoju dla uczestnika** (radio):

| Opcja UI | Zapis w DB |
|----------|------------|
| Pokój na ClickMeeting | `clickmeeting_join_enabled = true`, `embed_on_pnedu = false` |
| Osadzony pokój na pnedu.pl | `clickmeeting_join_enabled = false`, `embed_on_pnedu = true` |

Kolumny:

- `course_online_details.clickmeeting_join_enabled` (bool, default **true**)
- `course_online_details.embed_on_pnedu` (bool, default **false**)
- `participant_live_access.embed_token_consumed_at` — lokalna flaga „ten token już wpuszczono w embed” (bez czekania na `first_use_date` CM)

Migracje (pneadm):

- `2026_08_20_200210_…embed_on_pnedu…`
- `2026_08_21_181500_…clickmeeting_join_enabled…`
- `2026_08_21_182800_…normalize_live_room_mode…` (ustawia dokładnie jedną opcję na istniejących wierszach)
- `2026_08_20_230500_…embed_token_consumed_at…`

```bash
cd /home/hostnet/WEB-APP/pneadm && sail artisan migrate
```

Pole formularza: `live_room_mode` = `clickmeeting` \| `embed_pnedu` → `CoursesController::resolveLiveRoomFlags()`.

## Pnedu — przepływ użytkownika

1. Uczestnik ma zakup / provision → w oknie live (2 h przed startem do końca) widzi przycisk wg radio.
2. **ClickMeeting:** `joinUrl` = `room_url` + token z `participant_live_access` (lub `meeting_link`).
3. **Osadzony:** link do `route('dashboard.szkolenia.transmisja')` (+ `fullscreen=1` z homepage/listy).
4. Desktop embed: iframe CM (`?bare=1`), auto pełny ekran (gate modal), belka PNE z napisem „NODN Platforma Nowoczesnej Edukacji”.
5. Esc / „Wyjdź z pełnego ekranu” = tylko wyjście z FS (pokój zostaje).
6. „Zamknij transmisję” = modal → zwolnienie slotu obecności + zamknięcie hosta.

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
| Allowlista | `config/services.php` ← `CLICKMEETING_EMBED_ALLOWLIST` (`.env` / `.env.example`) |
| Homepage | ten sam serwis + `HomepageLiveMeetingNotice` — [HOMEPAGE_LIVE_MEETING.md](./HOMEPAGE_LIVE_MEETING.md) |

## Relacja do maili adm (pneadm)

| Akcja adm | Co robi względem embed |
|-----------|-------------------------|
| **Dodaj uczestnika do PNEDU** | Tworzy konto + provision CM + mail z **bezpośrednim** linkiem CM (bez URL `/transmisja`) |
| **Wyślij link do live** | Mail z **bezpośrednim** linkiem CM |

Radio **nie** zmienia treści tych maili (decyzja 8). Embed działa dopiero po wejściu na pnedu, gdy kurs ma `embed_on_pnedu`.

Opcja na później (nie wdrożona): CTA w mailu → `/transmisja`, gdy włączony tryb osadzony.

## Testy

```bash
# pnedu
sail test --filter=LiveTransmission
sail test --filter=DashboardCourseLiveAccessServiceTest

# pneadm (provision / live mail — bez zmiany CTA embed)
sail test --filter=ParticipantLiveMeetingLink
```

## Deploy

1. **pneadm:** `sail artisan migrate` (flagi radio + `embed_token_consumed_at` jeśli jeszcze nie).
2. **pnedu:** `.env` — `CLICKMEETING_API_TOKEN`, opcjonalnie `CLICKMEETING_EMBED_ALLOWLIST`; `config:clear` / deploy jak zwykle.
3. W edycji kursu ustawić radio na **Osadzony pokój** dla szkoleń eksperymentalnych.
