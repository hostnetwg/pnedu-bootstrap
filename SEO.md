# SEO — wytyczne dla pnedu.pl

Dokument obowiązuje przy **każdej nowej treści publicznej** (strony, podstrony, artykuły, landingi, zmiany w layoutach) oraz przy refaktoringu widoków. Celem jest **silne wsparcie pozycjonowania** witryny **https://pnedu.pl** w wyszukiwarkach.

---

## 1. Zasada ogólna

- **Nowe treści mają być projektowane pod SEO od początku**: unikalny, wartościowy tekst po polsku, jasna intencja strony (słowa kluczowe naturalnie w nagłówkach i leadzie).
- Unikaj „pustych” podstron i duplikatów treści bez `canonical` i bez uzasadnienia biznesowego.

---

## 2. Warstwa techniczna (Laravel)

| Element | Gdzie / jak |
|--------|-------------|
| **Tytuł strony** | `@section('title', '…')` — unikalny na każdej podstronie; zawiera frazę tematyczną + markę tam gdzie ma sens. |
| **Meta description** | `@section('meta_description', '…')` — unikalny opis 120–160 znaków; layout: `resources/views/layouts/app.blade.php`. |
| **Canonical** | Domyślnie `url()->current()`; dla duplikatów parametrów URL użyj `@section('canonical', …)`. |
| **Open Graph / Twitter** | Domyślnie z `title` / `meta_description`; przy ważnych landingach: `@section('og_title')`, `og_description`, `og_type`. |
| **Obraz OG** | `config('seo.default_og_image')` / `SEO_OG_IMAGE` w `.env`; domyślnie logo przy poprawnym `APP_URL`. |
| **Robots** | `config('seo.block_search_indexing')` — na produkcji **wyłącz** blokadę (`SEO_BLOCK_INDEXING` nie `true`). |
| **Sitemap** | Dynamicznie: `GET /sitemap.xml` → `SitemapUrlBuilder::renderXml()` (bez Blade — deklaracja `<?xml` w widoku + `view:cache` powodowała ParseError na prod). |
| **Meta kursów / list** | `CourseSeoService` + `config/course_seo.php` (skrócone title/description, override m.in. kursy 540/548, Akademia Dyrektora). |
| **Schema Course** | JSON-LD na `/courses/{id}` — `Course`, `Offer`, `CourseInstance`, `BreadcrumbList`. |
| **Search Console** | Checklista operacyjna: [`docs/GSC_CHECKLIST.md`](docs/GSC_CHECKLIST.md). |
| **Robots.txt** | Dynamicznie: `GET /robots.txt` → ten sam kontroler; **nie dodawaj** statycznych `public/robots.txt` ani `public/sitemap.xml` (nadpisują Laravel). |
| **JSON-LD** | Globalne dane marki: `resources/views/layouts/partials/global-structured-data.blade.php`; przy nowych typach treści dodawaj zgodne z treścią `BlogPosting`, `BreadcrumbList`, `ItemList`, `Course` itp. |
| **Semantyka HTML** | Jeden `h1` na widok; hierarchia `h2`–`h3`; linki z sensownym tekstem kotwicy (nie „kliknij tutaj”). |
| **Obrazy** | Zawsze `alt` opisowy; pliki z nazwą sensowną (np. `szkolenie-tik-nauczyciele.jpg`). |

---

## 2a. AI Search / AI Overviews / AI Mode

Google deklaruje, że widoczność w AI Overviews i AI Mode opiera się na podstawowym indeksie Google Search. Nie ma osobnego „AI schema” ani obowiązkowego pliku dla Google. Dla `pnedu.pl` obowiązuje więc:

- Strony muszą być indeksowalne, dostępne dla crawlerów i uprawnione do snippetów (`max-snippet`, brak przypadkowego `noindex`).
- Treść ma odpowiadać wprost na pytania użytkowników: jasny `h1`, logiczne `h2`/`h3`, krótkie akapity, listy, tabele i konkretne definicje.
- Dane strukturalne używamy standardowe i zgodne z widoczną treścią: `EducationalOrganization`, `WebSite`, `Blog`, `BlogPosting`, `BreadcrumbList`, `ItemList`.
- Nie dodajemy sztucznych treści tylko dla AI, ukrytych bloków, „keyword stuffing”, fałszywych FAQ ani niezgodnego schema.
- `/llms.txt` jest dodatkiem dla narzędzi AI, które go czytają; nie jest czynnikiem rankingowym Google i nie zastępuje sitemap/robots/HTML.
- Najlepszym sygnałem dla AI jest spójna encja marki: te same dane organizacji, kontaktu, social links i obszarów specjalizacji na stronie, w schema oraz w treściach.

---

## 3. Nowa publiczna strona (checklista)

1. Zarejestrowana trasa nazwana (`->name('…')`) pod `APP_URL`.
2. Uzupełnione: `title`, `meta_description`.
3. Jeśli to **ważna podstrona marketingowa**: dopisz trasę w `App\Services\Seo\SitemapUrlBuilder` (metoda `staticUrls()`) — trafi do `sitemap.xml`.
4. Strony tylko dla zalogowanych — zwykle **nie** dodawaj do sitemap (zostają poza mapą lub `noindex` jeśli kiedyś indeksowane przez pomyłkę).

---

## 3a. Artykuły bloga (`/blog`)

Zarządzanie treścią: panel **`adm.pnedu.pl` → Artykuły** — kanon operacyjny: **`pneadm/docs/ARTICLES.md`**. Front: **`docs/BLOG_ARTICLES.md`**.

| Element | Wytyczna |
|---------|----------|
| Meta title | ok. **50–65 znaków**; pole `meta_title` w panelu lub skrócony tytuł |
| Meta description | **120–160 znaków**; odpowiedź na intencję wyszukiwania |
| Slug | krótki, bez `-1`/`-2` jeśli możliwe; zmiana po indeksacji → 301 |
| Treść | jeden `h1`, logiczne `h2`/`h3`, linki do szkoleń i powiązanych artykułów |
| Obrazy | `alt` opisowy |
| Po publikacji | GSC → Inspekcja URL → „Poproś o indeksowanie” ([GSC_CHECKLIST.md](docs/GSC_CHECKLIST.md)) |

Front automatycznie: `BlogPosting` + breadcrumb JSON-LD, wpis w sitemap, fragment w `llms.txt`, licznik wyświetleń (analityka).

---

## 3b. Kursy i listy bezpłatne

| Element | Gdzie |
|---------|--------|
| Skrócone meta | `CourseSeoService` + `config/course_seo.php` |
| Override ID (np. 540, 548) | `config/course_seo.php` → `course_overrides` |
| Listy bezpłatne (TIK, Dyrektor, Office…) | `config/course_seo.php` → `listings` |
| Schema | `/courses/{id}` — `Course`, `Offer`, `CourseInstance` |

Docelowe długości (audyt 2026-08-23): title **~50–65 znaków**, description **~140–160 znaków**.

---

## 4. Audyt SEO 2026-08-23 — wdrożone i otwarte

**Wdrożone (techniczne):**

- Naprawa `/sitemap.xml` (HTTP 500 → dynamiczny XML bez Blade)
- Meta Akademii Dyrektora + kursy 540/548
- JSON-LD `Course` na stronach szkoleń
- Checklista GSC: [docs/GSC_CHECKLIST.md](docs/GSC_CHECKLIST.md)
- Komenda diagnostyczna: `php artisan seo:sitemap-diagnose`

**Otwarte (kolejne etapy):**

- Strony-filarowe („Szkolenia dla dyrektorów”, „Szkolenia online dla nauczycieli”)
- TTFB / cache (raport: 3–5 s → cel < 0,8 s)
- Systematyczne linkowanie artykuł → kurs → kategoria
- Plan treści 90 dni (2 artykuły/tydzień)

---

## 5. Środowisko produkcyjne

- `APP_URL=https://pnedu.pl` (bez końcowego `/`).
- Po zmianie `.env`: `php artisan config:cache` (lub `config:clear` w dev).
- `SEO_BLOCK_INDEXING` — nie ustawiaj `true` na produkcji, jeśli chcesz indeksowania.
- Po deployu SEO/bloga: `composer dump-autoload -o` + `php artisan optimize:clear` + `route:cache` na **pnedu**; migracje tabel współdzielonych w **pneadm** (`courses.show_on_pnedu`, `training_offers`, `articles`, `articles.sort_order`).
- Diagnostyka prod: `php artisan seo:sitemap-diagnose` (w katalogu frontu).
- Smoke: `curl -sS -o /dev/null -w "%{http_code}\n" https://pnedu.pl/sitemap.xml` → oczekiwane `200`.

---

## 6. Dla Cursor / AI

Przy każdej zmianie dotyczącej **treści widocznej dla użytkownika i Google** uwzględnij powyższe punkty automatycznie.

- Nowa **podstrona publiczna** → `title`, `meta_description`, ewentualnie wpis w `SitemapUrlBuilder::staticUrls()`.
- Nowy **artykuł** → wytyczne sekcji 3a; nie twórz statycznego `public/sitemap.xml`.
- Zmiana **kursu** → rozważ wpis w `config/course_seo.php` gdy title/description wymagają ręcznej korekty.

---

## Powiązana dokumentacja

| Plik | Temat |
|------|--------|
| [docs/BLOG_ARTICLES.md](docs/BLOG_ARTICLES.md) | Blog publiczny, schema, wyświetlenia |
| [docs/GSC_CHECKLIST.md](docs/GSC_CHECKLIST.md) | Google Search Console |
| [../pneadm/docs/ARTICLES.md](../pneadm/docs/ARTICLES.md) | Panel admin — zarządzanie artykułami |

*Ostatnia aktualizacja: 2026-08-23 (audyt SEO, sitemap, meta kursów, schema Course, blog).*
