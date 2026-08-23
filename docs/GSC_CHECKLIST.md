# Google Search Console — checklista po audycie SEO (pnedu.pl)

Checklist operacyjna po raporcie **Audyt widoczności SEO pnedu.pl** (23.08.2026).  
Wykonuj w [Google Search Console](https://search.google.com/search-console) dla właściwości **`https://pnedu.pl`**.

---

## 0. Przed startem (technika)

- [ ] `https://pnedu.pl/robots.txt` → **200**, `Allow: /`, wskazanie sitemapy
- [ ] `https://pnedu.pl/sitemap.xml` → **200**, poprawny XML (`<urlset>`)
- [ ] Właściwość GSC: **prefiks URL** `https://pnedu.pl/` (lub domena, jeśli zweryfikowana inaczej)
- [ ] Po deployu frontu: `composer dump-autoload -o`, `php artisan optimize:clear`, `route:cache`, `config:cache`

---

## 1. Mapa witryny (P0)

1. GSC → **Indeksowanie** → **Mapy witryn**
2. **Dodaj nową mapę witryny:** `https://pnedu.pl/sitemap.xml`
3. Status oczekiwany: **Powodzenie** (może potrwać kilka godzin)
4. Jeśli błąd:
   - sprawdź `curl -I https://pnedu.pl/sitemap.xml`
   - log: `storage/logs/laravel.log`
   - komenda: `php artisan seo:sitemap-diagnose`

---

## 2. Ręczna indeksacja nowych artykułów (P0)

Dla każdego świeżego wpisu bloga (np. z 23.08.2026):

1. GSC → **Inspekcja adresu URL**
2. Wklej pełny URL artykułu, np.  
   `https://pnedu.pl/blog/ocena-pracy-nauczyciela-2026-kryteria-zasady-i-odwolanie`
3. Poczekaj na wynik testu na żywo
4. Jeśli URL **nie jest w indeksie Google** → **Poproś o indeksowanie**
5. Powtórz dla drugiego artykułu

**Uwaga:** indeksacja zwykle trwa **3–7 dni**; GSC przyspiesza odkrycie, nie gwarantuje natychmiastowej pozycji.

---

## 3. Strony — kontrola tygodniowa

GSC → **Indeksowanie** → **Strony**

| Metryka | Co sprawdzać |
|--------|----------------|
| Zindeksowane | rośnie po publikacji bloga i nowych kursów |
| Niezindeksowane | brak masowych błędów 404/500 |
| Wykluczone | `noindex` tylko tam, gdzie zamierzone (panel, formularze wewnętrzne) |

Przy wzroście wykluczeń: otwórz próbkę URL → sprawdź canonical, robots, status HTTP.

---

## 4. Skuteczność (KPI organiczne)

GSC → **Wyniki** → **Skuteczność w wyszukiwarce**

Co tydzień (porównanie **ostatnie 28 dni** vs poprzednie 28 dni):

- [ ] **Kliknięcia** — cel raportu: **+50%** w 90 dni (przy podobnej sezonowości)
- [ ] **Wyświetlenia**
- [ ] **CTR** — szczególnie strony `/courses/*` po poprawie title/description
- [ ] **Średnia pozycja** — frazy długiego ogona (dyrektor, TIK, statut, podstawa programowa)

Filtry przydatne w raporcie:

- Strona zawiera: `/blog/`
- Strona zawiera: `/courses/`
- Zapytanie zawiera: `dyrektor`, `nauczyciel`, `TIK`, `statut`, `podstawa programowa`

---

## 5. Core Web Vitals i szybkość

GSC → **Experiences** → **Core Web Vitals**

- [ ] URL-e z raportu (strona główna, listy, kursy) — brak statusu **Poor** na mobile
- [ ] TTFB docelowo **< 0,8 s** (raport miał 3,1–5,3 s — osobny etap wydajności)

Uzupełniająco: PageSpeed Insights dla:

- `https://pnedu.pl/`
- `https://pnedu.pl/szkolenia-indywidualne`
- `https://pnedu.pl/courses/540`
- `https://pnedu.pl/courses/548`

---

## 6. Rich Results (schema Course)

Po wdrożeniu JSON-LD `Course` na `/courses/{id}`:

1. [Google Rich Results Test](https://search.google.com/test/rich-results)
2. Sprawdź przykładowo:
   - `https://pnedu.pl/courses/540`
   - `https://pnedu.pl/courses/548`
3. Oczekiwane: wykryty typ **Course** (oraz Offer / CourseInstance gdy są dane)

---

## 7. Harmonogram pierwszych 14 dni (z raportu)

| Dzień | Działanie | Gdzie potwierdzić |
|------|-----------|-------------------|
| 1 | Naprawa sitemap + dodanie w GSC | Mapy witryn |
| 1–2 | Inspekcja URL 2 nowych artykułów | Inspekcja adresu URL |
| 3–7 | Meta kursów 540/548, Akademia Dyrektora | Podgląd wyniku Google / GSC |
| 7 | Przegląd Strony + Skuteczność | GSC |
| 14 | Ponowny audyt fraz niszowych | Skuteczność → Zapytania |

---

## 8. Adresy testowe (z raportu)

- https://pnedu.pl/robots.txt
- https://pnedu.pl/sitemap.xml
- https://pnedu.pl/bezplatne/akademia-dyrektora
- https://pnedu.pl/blog
- https://pnedu.pl/courses/540
- https://pnedu.pl/courses/548

---

## Powiązane w repo

- `SEO.md` — wytyczne techniczne
- `config/course_seo.php` — ręczne title/description (540, 548, listy bezpłatne)
- `app/Services/Seo/CourseSeoService.php` — meta + schema Course
- `php artisan seo:sitemap-diagnose` — smoke sitemap na prod

*Ostatnia aktualizacja: 2026-08-23 (wdrożenie A/B/C po raporcie SEO).*
