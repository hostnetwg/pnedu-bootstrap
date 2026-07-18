# Pasek „spotkanie na żywo” na stronie głównej

Data: 2026-07-18  
Projekt: `pnedu`  
Status: **próba** (łatwe do wycofania)

## Cel

Zalogowany użytkownik na **stronie głównej** (`/`) widzi dyskretny pasek z najbliższym zakupionym szkoleniem live (link, data, licznik, hasło jeśli jest) — bez zaśmiecania hero.

## Decyzje (Waldemar, 2026-07-18)

| # | Decyzja |
|---|--------|
| A | Tylko homepage (nie layout globalny) |
| — | Tylko zalogowany |
| — | Najbliższe szkolenie z otwartym oknem live + linkiem |
| — | Link „Moje szkolenia” do panelu |
| — | Przycisk „Dołącz” aktywny od **2 h przed startem**; wcześniej tooltip + auto-odblokowanie bez reload |

Reguły widoczności i URL = jak w [DASHBOARD_LIVE_MEETING.md](./DASHBOARD_LIVE_MEETING.md) (ten sam `DashboardCourseLiveAccessService`).

## Kod

| Element | Ścieżka |
|---------|---------|
| Resolver | `app/Support/HomepageLiveMeetingNotice.php` |
| Controller | `HomeController` → `$homepageLiveNotice` |
| UI | `resources/views/layouts/homepage-live-meeting-notice.blade.php` |
| Include | `welcome.blade.php` (`@section('banner')`, pod paskiem akredytacji) |
| Licznik JS | ten sam co dashboard |

## Wycofanie (revert)

1. Usunąć include + `@push` licznika z `welcome.blade.php`
2. Usunąć `$homepageLiveNotice` z `HomeController`
3. Usunąć pliki: `HomepageLiveMeetingNotice.php`, partial Blade, test Feature, ten doc

## Testy

```bash
sail test --filter=HomepageLiveMeetingNoticeTest
```

## Deploy

Tylko **pnedu** (`git pull` + `view:clear` / `view:cache`). Bez migracji.
