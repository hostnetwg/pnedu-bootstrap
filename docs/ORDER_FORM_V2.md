# Formularze zamówienia (legacy + V2)

Dokument opisuje **oba** publiczne formularze zamówienia na `pnedu.pl` oraz wspólną **bramę URL**, która wybiera wariant. Legacy i V2 działają równolegle — V2 nie zastępuje stabilnego formularza ani jego kontraktu edycji zamówienia.

**Powiązane:** ustawienia w panelu adm → [`pneadm/docs/ORDER_FORMS.md`](../../pneadm/docs/ORDER_FORMS.md) · analityka → [`pneadm/docs/analytics/EVENT_TAXONOMY.md`](../../pneadm/docs/analytics/EVENT_TAXONOMY.md)

---

## Przegląd wariantów

| Aspekt | Legacy (uniwersalny) | V2 (kreator) |
|--------|----------------------|--------------|
| Widok | Jedna strona, wszystkie sekcje | 4 kroki: profil → kontakt → faktura → płatność |
| Profil zamawiającego | Pola buyer/recipient na jednej stronie | Wybór: szkoła publiczna/JST, organizacja, osoba prywatna |
| Kontakt | Jak dotychczas | Szkoła/organizacja: jedno pole „Nazwa / imię nazwisko zamawiającego” → `contact_name`. Osoba prywatna: imię + nazwisko → składane do `contact_name` |
| Przełącznik „Zamawiający = uczestnik” | Legacy UI | **Tylko profil osoba prywatna**; ukryty dla szkoły/firmy, bez kopiowania danych |
| Limit terminu płatności odroczonej | 0–31 dni | 0–30 dni |
| Edycja istniejącego zamówienia | Tak (`/order-form/edit/{ident}`) | **Nie** — edycja zawsze legacy |
| POST (zapis) | `POST /courses/{id}/order-form` | `POST /courses/{id}/order-form-v2` |
| Backend zapisu | Wspólny pipeline `FormOrder` | Ten sam kontrakt co legacy |

---

## Ustawienia (baza `pneadm`, panel adm)

Tabela `payment_display_options`. Panel: **Ustawienia → Zakupy pnedu.pl** (`/settings/pnedu-zakupy`).

| Pole | Znaczenie |
|------|-----------|
| `show_order_form` | Checkbox „Zamawiam szkolenie” — włącza CTA i dostępność wariantu **legacy** |
| `show_order_form_v2` | Checkbox „Zamawiam szkolenie v2” — włącza CTA (inny styl) i bezpośredni URL `/order-form-v2`. Gdy wyłączone → GET/POST na `/order-form-v2` zwraca **404** |
| `default_signup_order_form_variant` | Radio `legacy` \| `v2` — domyślny wariant dla CTA i wejść **bez** parametru `form_variant` w URL |

**Reguła dostępności** (`OrderFormVariant::resolveAvailable`):

1. Preferowany wariant (z query lub radia) — jeśli jego checkbox jest włączony → użyty.
2. W przeciwnym razie → fallback na drugi włączony wariant (np. kampania z `form_variant=v2` przy wyłączonym V2 → legacy).
3. Oba checkboxy wyłączone → brama nadal może otworzyć legacy (ostatni fallback w kodzie); **przycisk** „Zamawiam szkolenie” na stronie kursu znika.

**Zabezpieczenie zapisu w adm:** nie można wyłączyć obu checkboxów naraz; radio „Domyślna wersja” musi wskazywać wariant z włączonym checkboxem (`PneduPurchasesController`).

**Brak A/B:** nie ma automatycznego podziału ruchu. Administrator wybiera domyślną wersję; kampanie mogą wymusić wariant parametrem URL.

---

## Routing i brama URL

Jeden **publiczny punkt wejścia** dla nowych linków:

```
GET /courses/{id}/order-form
```

Brama (`App\Support\OrderFormGateway`) rozstrzyga wariant:

1. Query `?form_variant=legacy|v2` (kampanie z przypiętą wersją, jawne linki) — ma pierwszeństwo.
2. Brak parametru (`global`, CTA, „Zapisz się”) → `default_signup_order_form_variant` z ustawień adm.
3. Wynik zapisywany w atrybucie requestu (`order_form_resolved_variant`) — używany przez analitykę.

### Tabela tras

| Trasa | Metoda | Wariant | Uwagi |
|-------|--------|---------|-------|
| `/courses/{id}/order-form` | GET | brama | Nowe linki, CTA, kampanie |
| `/courses/{id}/order-form/edit/{ident}` | GET | **zawsze legacy** | Prefill z `FormOrder`; poza bramą |
| `/courses/{id}/order-form-v2` | GET | V2 | QA, stare bezpośrednie linki; 404 gdy `show_order_form_v2=false` |
| `/courses/{id}/order-form` | POST | legacy | Zapis zamówienia |
| `/courses/{id}/order-form-v2` | POST | V2 | Zapis; hidden `form_variant=v2` |

Named routes: `payment.order-form`, `payment.order-form.edit`, `payment.order-form-v2`, `payment.order-form.store`, `payment.order-form-v2.store`.

**Listy kursów / „Zapisz się”:** `Course::publicOrderFormUrl()` → brama `/order-form` (+ opcjonalnie `price_variant_id`), bez jawnego `form_variant` — stosuje domyślny wariant z adm.

---

## Kampanie marketingowe

Skróty `/l/{campaign_code}` i linki UTM z adm:

- Pole kampanii `order_form_variant` (`legacy` | `v2` | `global`):
  - **legacy / v2** — przypięty `form_variant` w URL (stabilność po wysyłce newslettera).
  - **global** (domyślnie przy **nowej** kampanii) — brama `/order-form` **bez** `form_variant`; formularz jak przycisk na stronie kursu / archiwalne linki FB.
- Landing `order_form` → `/courses/{id}/order-form` + UTM; opcjonalnie `?form_variant=…` tylko gdy kampania ma legacy/v2 (`MarketingCampaignLinkResolver`, `MarketingCampaignUrlBuilder` w adm).
- Landing `course_show` → opis kursu bez `form_variant`.

Preview linku w adm pokazuje pełny URL z bramą.

---

## CTA na stronie kursu

Partial: `resources/views/courses/partials/course-paid-actions-box.blade.php`.

- Przycisk „Zamawiam szkolenie” widoczny, gdy aktywny jest **domyślny** wariant (po `resolveAvailable`).
- Link zawsze na bramę `route('payment.order-form', $course->id)` — **nie** na `/order-form-v2`.
- Wariant V2: klasa `btn-purchase-cta-v2`; legacy: `btn-purchase-cta`.
- Przy wielu wariantach cenowych JS dokleja `price_variant_id` do href.

---

## Formularz V2 — UX

### Kroki

1. **Profil** — `customer_profile`: `school` \| `organisation` \| `person`
2. **Kontakt i uczestnik** — e-mail/telefon, dane uczestnika
3. **Dane do faktury** — NIP/GUS, nabywca, odbiorca (zależnie od profilu)
4. **Płatność i podsumowanie** — online / odroczona, gateway, termin

Widok: `resources/views/courses/order-form-v2.blade.php`.

### Pola kontaktowe (zgodność z legacy)

- **Szkoła / organizacja:** jedno pole wyświetlane „Nazwa / imię nazwisko zamawiającego” → hidden/post `contact_name`.
- **Osoba prywatna:** osobno imię i nazwisko (`contact_first_name`, `contact_last_name`); JS składa `contact_name` przed submitem.

### Przełącznik uczestnika

Logika domyślna: `App\Support\OrderFormV2ParticipantDefaults`.

| Profil | Domyślnie włączony? | Widoczność przełącznika |
|--------|---------------------|-------------------------|
| Szkoła / organizacja | Nie | **Ukryty** — pola uczestnika zawsze osobno |
| Osoba prywatna | Tak | Widoczny; odznaczenie czyści skopiowane dane uczestnika |

Po zmianie profilu w kroku 1 przełącznik i domyślna forma płatności ustawiają się ponownie.

Uzasadnienie danych historycznych (5793 zamówień, 07/2026): ~99% zamówień bez NIP to ta sama osoba kontakt/uczestnik; ~36% z NIP/odbiorcą.

### Blok oferty nad formularzem

Kompaktowy blok „Zamawiasz szkolenie” — tylko V2:

- Partial: `courses/partials/order-form-v2-offer-summary.blade.php`
- Adapter: `App\Support\OrderFormV2OfferSummary`

Cel: użytkownik z newslettera/reklamy od razu widzi tytuł, datę, cenę i elementy w cenie. Responsywnie: od `lg` cena w prawej kolumnie.

---

## Tryb testowy i dane testowe

Włączenie **przycisku** „Wypełnij dane testowe” (legacy i V2):

| Źródło | Kto widzi przycisk | Auto-wypełnienie pól przy wejściu |
|--------|-------------------|----------------------------------|
| `order_form_auto_fill_test_data_developers_only` (adm) | Zalogowani: `waldemar.grabowski@hostnet.pl`, `luman0599@gmail.com` | **Nigdy** |
| `order_form_auto_fill_test_data` (adm, czerwony) | **Wszyscy**, także niezalogowani | **Nigdy** |
| Query `?test=1` | Tak (wymusza tryb testowy) | **Nigdy** |

Po kliknięciu przycisku dane pochodzą z `App\Support\OrderFormTestData::defaults()` (JS w obu widokach; V2 uzupełnia też profil, płatność, przełącznik uczestnika).

**Ważne:** `$testData` z kontrolera jest **puste** przy wejściu na formularz — pola nie są wstępnie wypełniane w HTML. Wyjątek: wznowienie checkoutu (`FormOrderCheckoutResumeService`) lub edycja zamówienia (prefill z `FormOrder`).

Opcja unrestricted na **produkcji** wyłącza się automatycznie po TTL (domyślnie 1 min) — także w panelu adm (`PaymentDisplayOption::expireUnrestrictedAutoFillIfNeeded`).

Logika: `PaymentDisplayOption::isOrderFormTestModeEnabled()`. Przygotowanie widoku: `CourseController::renderNewOrderForm()`.

---

## Analityka

Wariant śledzony jako **`metadata.form_variant`**: `legacy` \| `v2`.

Źródło (`BackendAnalyticsTracker::resolveTrackedFormVariant`):

1. Trasa `payment.order-form-v2*` → `v2`
2. Atrybut bramy po GET
3. POST input `form_variant`
4. Fallback `legacy`

### Eventy backendowe z `form_variant`

- `order_form_viewed`
- `order_form_submit_attempted`
- `order_form_validation_failed`
- `form_order_created`
- `online_payment_selected`, `deferred_invoice_selected`, `payment_order_created`

Whitelist w sanitizerze: `AnalyticsPayloadSanitizer`.

### JS collector (B2)

Inline w `order-form-client-tracking.blade.php` — **ładowany na obu widokach** formularza. Eventy MVP (`order_form_started`, `order_form_section_interacted`, …) **nie** wysyłają `form_variant` w batchu (zamierzone — wariant identyfikuje backend przy `order_form_viewed` i POST). V2 używa dodatkowych `data-analytics-section-v2` dla sekcji kreatora.

---

## Kluczowe pliki

### pnedu

| Plik | Rola |
|------|------|
| `app/Support/OrderFormGateway.php` | Bramka GET, query `form_variant` |
| `app/Support/OrderFormVariant.php` | Stałe, `resolveAvailable`, nazwy tras |
| `app/Support/OrderFormTestData.php` | Zestaw danych testowych |
| `app/Support/OrderFormV2ParticipantDefaults.php` | Domyślny stan przełącznika uczestnika |
| `app/Support/OrderFormV2OfferSummary.php` | Blok oferty V2 |
| `app/Http/Controllers/CourseController.php` | `orderForm`, `orderFormV2`, `renderNewOrderForm`, store |
| `app/Services/MarketingCampaignLinkResolver.php` | Redirect kampanii → brama |
| `app/Services/Analytics/BackendAnalyticsTracker.php` | `form_variant` w metadata |
| `resources/views/courses/order-form.blade.php` | Legacy |
| `resources/views/courses/order-form-v2.blade.php` | V2 |

### pneadm

| Plik | Rola |
|------|------|
| `app/Http/Controllers/Settings/PneduPurchasesController.php` | Zapis flag, walidacja checkboxów formularza |
| `resources/views/settings/pnedu-purchases.blade.php` | UI ustawień |
| `resources/views/settings/partials/order-form-variant-radios.blade.php` | Radio legacy/v2 (ustawienia) lub + global (kampanie) |
| `app/Services/MarketingCampaignUrlBuilder.php` | URL kampanii z `form_variant` |
| Migracje `2026_07_10_*` | `show_order_form_v2`, `default_signup_order_form_variant`, `order_form_variant` w kampaniach |

---

## Lokalne URL (dev)

| Środowisko | Baza |
|------------|------|
| pnedu (Sail) | `http://localhost:8081` lub `http://edu.localhost:8081` |
| pneadm (Sail) | `http://localhost:8083` — ustawienia |

Przykłady:

```
/courses/{id}/order-form
/courses/{id}/order-form?form_variant=v2
/courses/{id}/order-form?form_variant=legacy&test=1
/courses/{id}/order-form-v2          # tylko gdy V2 włączone w adm
/courses/{id}/order-form/edit/{ident}
```

---

## Testy

```
# pnedu
sail artisan test --filter=OrderForm
sail artisan test --filter=OrderFormGateway
sail artisan test --filter=AnalyticsPaymentSelection
sail artisan test --filter=AnalyticsPaymentOrderCreated
```

Kluczowe klasy testów: `OrderFormV2Test`, `OrderFormGatewayTest`, `OrderFormTestDataTest`, `AnalyticsPaymentSelectionStage2A1Test`, `AnalyticsPaymentOrderCreatedStage2A2Test`.

---

## Świadomie poza zakresem

- Edycja zamówienia w V2
- Automatyczny A/B test między wariantami
- `form_variant` w batchu JS client-events (możliwe usprawnienie backlog)
