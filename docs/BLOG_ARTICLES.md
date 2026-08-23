# Blog / Artykuły na pnedu.pl

Publiczny blog ekspercki Platformy Nowoczesnej Edukacji. Treści **tworzy się i publikuje w panelu** `adm.pnedu.pl` → menu **Artykuły**. Ten dokument opisuje **front** (`pnedu.pl`); zarządzanie w panelu: **`pneadm/docs/ARTICLES.md`**.

---

## Źródło danych

- Tabela: `articles` w bazie **`pneadm`**
- Model: `App\Models\Article` (`protected $connection = 'pneadm'`)
- Migracje: **`pneadm/database/migrations/`** (nigdy w `pnedu`)

---

## Trasy publiczne

| URL | Opis |
|-----|------|
| `/blog` | Lista opublikowanych artykułów (paginacja, wyszukiwanie `?q=`) |
| `/blog/{slug}` | Pojedynczy artykuł |
| `/api/blog/new-count` | API licznika „nowych” artykułów (menu nawigacji) |

Widoczne są tylko rekordy:

- status `published`,
- ustawione `published_at`,
- data publikacji ≤ teraz,
- brak `deleted_at`.

Kolejność listy: `sort_order` → `published_at` → `created_at` (ustawiana w panelu).

---

## Funkcje publiczne artykułu

- **Hero:** tytuł (`h1`), excerpt, data, czas czytania, **licznik wyświetleń**
- **Okładka** (jeśli ustawiona w panelu)
- **Treść HTML** z responsywnymi stylami (tabele, typografia)
- **Udostępnianie:** Facebook, LinkedIn, X, e-mail, kopiuj link
- **Breadcrumb:** Start → Blog → tytuł artykułu

---

## Wyświetlenia (`view_count`)

- Middleware: `TrackArticlePageView` → `ArticlePageViewTracker`
- Inkrementacja w bazie `pneadm`, kolumna `articles.view_count`
- **Zgodność z analityką:** ustawienia z panelu adm (**Analityka → Ustawienia**) — wyłączenie, tryb, sampling, opt-out `pne_skip_analytics`, boty, prefetch
- Dedup: max. **raz na sesję** `pne_analytics_sid`
- Statystyka w panelu: kolumna **Wyśw.** na `/articles`

---

## SEO artykułu (techniczne)

Pełne wytyczne serwisu: **[SEO.md](../SEO.md)**. Checklista GSC: **[GSC_CHECKLIST.md](./GSC_CHECKLIST.md)**.

### Meta tagi

| Element | Źródło |
|---------|--------|
| `<title>` | `meta_title` lub tytuł + sufiks layoutu |
| `meta description` | `meta_description` → excerpt → skrót treści |
| `canonical` | `route('blog.show', slug)` |
| `og:type` | `article` |
| `og:image` | okładka artykułu lub domyślne logo |

### Zalecenia redakcyjne (długość)

| Pole | Cel |
|------|-----|
| Meta title | ok. **50–65 znaków** (fraza + sens; bez powielania długiej marki w polu meta) |
| Meta description | ok. **120–160 znaków** |
| Excerpt | 1–3 zdania pod listę bloga i lead artykułu |
| Treść | jeden temat, `h2`/`h3`, linki do szkoleń i powiązanych artykułów |

### Dane strukturalne (JSON-LD)

Na stronie artykułu (`@push('structured-data')`):

- `BlogPosting` — tytuł, opis, daty, obraz, autor/publisher (organizacja)
- `BreadcrumbList`
- `WebPage` (mainEntityOfPage)

Lista bloga (`/blog`):

- `Blog`, `ItemList`, meta title/description własne

### Indeksowanie i discovery

- **`/sitemap.xml`** — dynamicznie wszystkie opublikowane artykuły (`SitemapUrlBuilder`)
- **`/robots.txt`** — `Allow: /`, wskazanie sitemapy
- **`/llms.txt`** — 20 najnowszych artykułów (AEO, nie zamiennik sitemap)
- Lista bloga z wyszukiwaniem: `noindex, follow` (parametr `?q=` — unikamy indeksu duplikatów)

### Po publikacji nowego artykułu

1. Sprawdź URL na prod.
2. GSC → Inspekcja URL → **Poproś o indeksowanie** (patrz GSC_CHECKLIST).
3. Opcjonalnie: link z istniejących artykułów / ze strony kursu.

---

## Licznik „nowe” w menu Blog

- Endpoint: `GET /api/blog/new-count?since=…`
- Stan w **`localStorage`** przeglądarki (`pne_blog_last_seen_at`)
- Plakietka znika po wejściu na `/blog` lub artykuł
- Nie wymaga logowania

---

## Powiązanie z SEO reszty serwisu

| Obszar | Dokument / kod |
|--------|----------------|
| Sitemap, robots, llms | `App\Http\Controllers\SeoController`, `App\Services\Seo\SitemapUrlBuilder` |
| Meta kursów | `App\Services\Seo\CourseSeoService`, `config/course_seo.php` |
| Schema Course | `/courses/{id}` — JSON-LD Course/Offer |
| Audyt 2026-08-23 | Raport SEO + wdrożenia A/B/C (meta kursów, schema, GSC) |

**Strategia treści (raport):** 2 artykuły/tydzień — jeden dla dyrektora, jeden dla nauczyciela; każdy z FAQ, źródłami i linkiem do właściwego szkolenia.

---

## Komentarze (etap 2 — nieaktywne)

Pole `comments_enabled` w panelu jest przygotowaniem pod przyszły moduł. Brak publicznego formularza komentarzy.

---

## Testy i smoke

- `pneadm`: smoke w [TESTING.md](../../pneadm/docs/TESTING.md) (sekcja Artykuły / blog)
- `pnedu`: `--filter=SeoSitemapTest`, `--filter=ArticlePageViewTrackerTest`, `--filter=CourseSeoServiceTest`

---

## Powiązana dokumentacja

| Dokument | Gdzie |
|----------|--------|
| Zarządzanie w panelu | `pneadm/docs/ARTICLES.md` |
| SEO całego serwisu | `pnedu/SEO.md` |
| Google Search Console | `pnedu/docs/GSC_CHECKLIST.md` |

*Ostatnia aktualizacja: 2026-08-23.*
