# Spotkanie na żywo w panelu uczestnika (`/dashboard/szkolenia`)

Data: 2026-07-18  
Projekt: `pnedu`

## Cel

Na liście szkoleń uczestnika (`http://edu.localhost:8081/dashboard/szkolenia` / produkcja `pnedu.pl`) pokazujemy link do spotkania online **przed startem i w trakcie** szkolenia — niezależnie od tego, czy ClickMeeting wymaga tokena.

## Decyzje (Waldemar, 2026-07-18)

| # | Decyzja |
|---|--------|
| 1 | Preferuj `participant_live_access` (tokenowy URL), inaczej `course_online_details.meeting_link` |
| 2 | Wszystkie platformy z linkiem (CM, Zoom, Meet, YouTube…) |
| 3 | Ukryj po `end_date`; bez `end_date` — ukryj gdy minął `start_date` |
| 4 | Pokaż hasło (`meeting_password`), gdy jest |
| 5 | Przycisk „Dołącz do spotkania na żywo” + licznik do startu / do końca |

## Skąd dane

Wspólna baza **pneadm** (bez nowego API):

- `participant_live_access` — po provision CM w adm
- `course_online_details` — platforma, `meeting_link`, `meeting_password`

## Kod

| Element | Ścieżka |
|---------|---------|
| Model | `app/Models/ParticipantLiveAccess.php` |
| Serwis | `app/Services/DashboardCourseLiveAccessService.php` |
| Listing | `app/Support/DashboardParticipantsListing.php` |
| UI | `resources/views/dashboard/partials/szkolenia-list-inner.blade.php` |
| Licznik JS | `resources/views/dashboard/partials/szkolenia-live-countdown-script.blade.php` |

## Widoczność

Sekcja live pojawia się, gdy:

1. okno czasowe otwarte (jak wyżej),
2. jest `joinUrl` (live access success **lub** `meeting_link`).

Po zakończeniu szkolenia sekcja znika; zostaje informacja o nagraniach/materiałach/zaświadczeniu.

## Testy

```bash
sail test --filter=DashboardCourseLiveAccessServiceTest
```

## Deploy

Tylko **pnedu** (`git pull` + `view:clear` / `view:cache`). Bez migracji — tabele już istnieją w pneadm.
