# Karuzela wyróżnionych ofert RP na stronie głównej

Status: wdrożone lokalnie (2026-07-30)

## Zachowanie

Sekcja „Zamów szkolenie dla rady pedagogicznej” na `welcome`:

1. W pierwszym HTML renderowane jest najwyżej **6** ofert (`featured_on_homepage`).
2. Kolejne partie po **6** dociągane są dopiero po kliknięciu strzałki „następne” (fragment AJAX).
3. Widok karuzeli: 3 karty (desktop ≥992px), 2 (tablet ≥768px), 1 (mobile).

## Technicznie (pnedu)

| Element | Opis |
|--------|------|
| Support | `App\Support\FeaturedHomepageTrainingOffers` (`INITIAL_LIMIT=6`, `BATCH_LIMIT=6`) |
| Home | `HomeController` — `page(0, INITIAL_LIMIT)` + `count()` |
| Fragment | `HomepageFragmentController::featuredTrainingOffers` |
| Trasa | `GET /fragments/featured-training-offers` → `fragments.featured-training-offers` (throttle 60/min) |
| Partial | `training-offers/partials/featured-homepage-slides.blade.php` |
| JS/CSS | inline w `resources/views/welcome.blade.php` |

Nagłówki odpowiedzi fragmentu: `X-Featured-Offers-Total`, `X-Featured-Offers-Count`, `X-Featured-Offers-Offset`.

## Testy

`sail artisan test --filter=TrainingOfferPublicTest` — m.in. homepage z limitami oraz fragment kolejnej partii.
