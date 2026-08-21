# Pasek „spotkanie na żywo” na stronie głównej

Data: 2026-07-18  
Projekt: `pnedu`  
Status: **próba** (łatwe do wycofania)

## Cel

Zalogowany użytkownik na **stronie głównej** (`/`) widzi dyskretny pasek z zakupionymi szkoleniami live (link „Dołącz do spotkania”, data, licznik, hasło jeśli jest) — bez zaśmiecania hero.

## Decyzje (Waldemar)

| # | Decyzja | Data |
|---|--------|------|
| A | Tylko homepage (nie layout globalny) | 2026-07-18 |
| — | Tylko zalogowany | 2026-07-18 |
| — | Link „Moje szkolenia” / „Wszystkie szkolenia” do panelu | 2026-07-18 |
| — | Przycisk „Dołącz” aktywny od **2 h przed startem**; wcześniej tooltip + auto-odblokowanie bez reload | 2026-07-18 |
| B | Najbliższy **dzień** z otwartym oknem live + linkiem; jeśli tego dnia jest więcej szkoleń użytkownika — pokaż **wszystkie z tego dnia** (jak karty na `/dashboard/szkolenia`) | 2026-08-10 |

Reguły widoczności i URL = jak w [DASHBOARD_LIVE_MEETING.md](./DASHBOARD_LIVE_MEETING.md) (ten sam `DashboardCourseLiveAccessService`).  
Tryb **osadzonego pokoju** (radio w adm): [DASHBOARD_LIVE_EMBED.md](./DASHBOARD_LIVE_EMBED.md).

## Kod

| Element | Ścieżka |
|---------|---------|
| Resolver | `app/Support/HomepageLiveMeetingNotice.php` |
| Pozycja listy | `app/Support/HomepageLiveMeetingItem.php` |
| Controller | `HomeController` → `$homepageLiveNotice` |
| UI | `resources/views/layouts/homepage-live-meeting-notice.blade.php` |
| Include | `welcome.blade.php` (`@section('banner')`, pod paskiem akredytacji) |
| Licznik JS | ten sam co dashboard |

## Wycofanie (revert)

1. Usunąć include + `@push` licznika z `welcome.blade.php`
2. Usunąć `$homepageLiveNotice` z `HomeController`
3. Usunąć pliki: `HomepageLiveMeetingNotice.php`, `HomepageLiveMeetingItem.php`, partial Blade, test Feature, ten doc

## Testy

```bash
sail test --filter=HomepageLiveMeetingNoticeTest
```

## Deploy

Tylko **pnedu** (`git pull` + `view:clear` / `view:cache`). Bez migracji.
