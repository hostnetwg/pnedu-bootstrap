# Wdrożenie optymalizacji wydajności (Fazy 1–7)

Checklista pod workflow: **dev → `git push` → produkcja → `git pull`**.

Ogólne kroki deployu (Composer, Vite, cache Laravel): **[PRODUCTION-DEPLOYMENT.md](./PRODUCTION-DEPLOYMENT.md)**.

Skrót po `git pull` na produkcji:

```bash
cd ~/domains/pnedu.pl/app   # dostosuj ścieżkę
bash scripts/production-optimize.sh
```

---

## Co obejmują fazy

| Faza | Opis | Repo |
|------|------|------|
| **1** | Lazy-load „Aktualna oferta”, cache oferty (10 min), szybszy redirect po logowaniu | `pnedu` |
| **2** | Lżejsze zapytania listy szkoleń, cache liczników menu (2 min) | `pnedu` |
| **3** | Sesja/cache na plikach, długi cache assetów Vite, skrypt `production-optimize.sh` | `pnedu` |
| **4** | Indeks `email_normalized`, `withCount` zamiast pełnych relacji, AJAX lista szkoleń | `pnedu` + **migrate `pneadm`** |
| **5** | Filtry `?typ=` bez pełnego przeładowania strony | `pnedu` |
| **6** | Cache strony głównej (goście), lazy-load obrazów, cache statycznych plików | `pnedu` |
| **7** | Log wydajności `/dashboard/*`, skrypt TTFB | `pnedu` |

---

## pnedu.pl — po `git pull`

### 1. Kod

```bash
cd ~/domains/pnedu.pl/app
git pull
```

Jeśli zmienił się `composer.lock`:

```bash
composer install --no-dev --optimize-autoloader
```

Migracje **pnedu** (baza `pnedu`):

```bash
php artisan migrate --force
```

### 2. `.env` na produkcji

**Faza 3** (zalecane na SeoHost):

```env
SESSION_DRIVER=file
CACHE_STORE=file
APP_DEBUG=false
LOG_LEVEL=warning
```

**Faza 6** — opcjonalnie cache HTML strony głównej dla gości:

```env
HOMEPAGE_PAGE_CACHE_MAX_AGE=60
HOMEPAGE_PAGE_CACHE_STALE=120
```

Wyjaśnienie: `max-age=60` = CDN/przeglądarka może serwować HTML `/` przez 60 s bez PHP; `stale-while-revalidate=120` = po wygaśnięciu jeszcze 2 min można oddać starą wersję podczas odświeżania w tle. **Nie dotyczy zalogowanych użytkowników.**

**Faza 7** — obserwowalność panelu:

```env
# Tryb cichy (domyślnie): log tylko wolnych requestów
DASHBOARD_PERF_SLOW_MS=500
DASHBOARD_PERF_SLOW_QUERIES=25

# Diagnostyka tymczasowa (1–2 dni): log każdego wejścia w /dashboard/*
# DASHBOARD_PERF_LOG=true
```

Po każdej zmianie `.env`:

```bash
php artisan config:cache
```

### 3. Cache Laravel

```bash
bash scripts/production-optimize.sh
```

Ręcznie (równoważne):

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan about
```

### 4. Pliki spoza gita

Upewnij się, że na produkcji są (z repozytorium):

- `public/.htaccess` — cache `/build/assets/*` i `/images/*`
- `scripts/production-optimize.sh`
- `scripts/check-public-ttfb.sh`

**Vite:** fazy 1–7 głównie Blade + inline JS — **nowy `npm run build` zwykle nie jest potrzebny**, chyba że zmieniałeś assety w `resources/js` / `resources/css`. Wtedy zbuduj lokalnie i wgraj `public/build/` (patrz PRODUCTION-DEPLOYMENT.md).

---

## pneadm — Faza 4 (indeks bazy `pneadm`)

Osobne repo / katalog admina:

```bash
cd ~/ścieżka/do/pneadm
git pull
php artisan migrate --force
```

Migracja: `2026_06_12_000001_add_participants_email_normalized_lookup_index.php`  
Indeks na `participants.email_normalized` — bez niego Faza 4 działa (fallback SQL), ale wolniej przy wielu uczestnikach.

---

## Weryfikacja po wdrożeniu

### Laravel

```bash
php artisan about | grep -E 'Session|Cache|Config|Routes|Views'
```

Oczekiwane na produkcji (Faza 3): **Session: file**, **Cache: file**, cache włączone.

### TTFB (Faza 7)

```bash
bash scripts/check-public-ttfb.sh https://pnedu.pl
```

`/dashboard/szkolenia` bez sesji mierzy tylko redirect do logowania — pełny test w przeglądarce po zalogowaniu.

### Panel (Fazy 1–5)

- [ ] `/dashboard/szkolenia` — sidebar „Aktualna oferta” ładuje się asynchronicznie (nie wisi wiecznie „Ładowanie terminów…”)
- [ ] Filtry **Wszystkie / Płatne / Bezpłatne** — bez pełnego przeładowania strony
- [ ] Paginacja listy — bez pełnego reloadu
- [ ] Redirect po logowaniu — od razu na listę szkoleń (bez zbędnego `/dashboard`)

### Strona główna (Faza 6)

- [ ] `/` — szybkie wejście dla gościa
- [ ] Obrazy karuzeli — pierwszy slajd od razu, pozostałe lazy-load
- [ ] Nagłówek `Cache-Control` na `/` dla gościa (opcjonalnie: `curl -I https://pnedu.pl/`)

### Logi wydajności (Faza 7)

```bash
tail -30 storage/logs/dashboard-performance.log
```

Po kilku wejściach w panel — wpisy `warning` przy wolnych requestach (>500 ms lub >25 query).

---

## Typowy flow dev → prod

```bash
# Dev (WSL / Sail)
git add …
git commit -m "…"
git push

# Produkcja pnedu
ssh …
cd ~/domains/pnedu.pl/app
git pull
bash scripts/production-optimize.sh

# Produkcja pneadm (jeśli była Faza 4)
cd ~/ścieżka/pneadm
git pull
php artisan migrate --force
```

---

## Rozwiązywanie problemów

| Problem | Co zrobić |
|---------|-----------|
| 500 na `/dashboard/szkolenia` | `php artisan optimize:clear`, potem `route:cache`, `view:cache`, `config:cache` |
| Skeleton oferty wisi | Upewnij się, że wgrany jest loader w `sidebar-nav-offer-mount.blade.php` (inline `@push('scripts')`) |
| Stary route fragmentu | W widokach używamy `url('/dashboard/fragments/…')` zamiast `route()` — wgraj aktualne Blade |
| Panel nadal wolny | Włącz tymczasowo `DASHBOARD_PERF_LOG=true`, przejrzyj `dashboard-performance.log` |
| Po zmianie `.env` dziwne zachowanie | `php artisan optimize:clear` → popraw `.env` → `config:cache` |

---

## Powiązane pliki

- `scripts/production-optimize.sh` — cache po deployu
- `scripts/check-public-ttfb.sh` — szybki pomiar TTFB
- `config/observability.php` — progi logów panelu (Faza 7)
- `config/seo.php` — cache strony głównej (Faza 6)
- `.env.example` — komentarze do zmiennych
