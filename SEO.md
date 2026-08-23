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
3. Jeśli to **ważna podstrona marketingowa**: dopisz trasę do listy w `SeoController::staticUrls()` (żeby trafiła do `sitemap.xml`).
4. Strony tylko dla zalogowanych — zwykle **nie** dodawaj do sitemap (zostają poza mapą lub `noindex` jeśli kiedyś indeksowane przez pomyłkę).

---

## 4. Środowisko produkcyjne

- `APP_URL=https://pnedu.pl` (bez końcowego `/`).
- Po zmianie `.env`: `php artisan config:cache` (lub `config:clear` w dev).
- `SEO_BLOCK_INDEXING` — nie ustawiaj `true` na produkcji, jeśli chcesz indeksowania.
- Po deployu SEO/bloga: `composer dump-autoload -o` + `php artisan optimize:clear` + `route:cache` na **pnedu**; migracje tabel współdzielonych w **pneadm** (`courses.show_on_pnedu`, `training_offers`, `articles`, `articles.sort_order`).
- Diagnostyka prod: `php artisan seo:sitemap-diagnose` (w katalogu frontu).
- Smoke: `curl -sS -o /dev/null -w "%{http_code}\n" https://pnedu.pl/sitemap.xml` → oczekiwane `200`.

---

## 5. Dla Cursor / AI

Przy każdej zmianie dotyczącej **treści widocznej dla użytkownika i Google** uwzględnij powyższe punkty automatycznie; w razie nowej podstrony publicznej — **domyślnie** zaproponuj `meta_description` i rozważ wpis w `SeoController`.

---

*Ostatnia aktualizacja: dokumentacja projektu pnedu — wsparcie SEO jako standard pracy nad treścią.*
