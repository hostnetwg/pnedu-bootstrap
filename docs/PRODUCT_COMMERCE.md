# Publiczna sprzedaż kursów nagranych

Data: 2026-09-12
Status: wdrożone lokalnie, przed produkcją wymagany przegląd prawny

## Publiczne trasy

- `GET /kursy` — katalog aktywnych ofert,
- `GET /kursy/{product:slug}` — opis i warianty dostępu,
- `GET|POST /kursy/{product:slug}/zapis` — bezpłatny zapis (flaga `is_complimentary`, bez checkoutu i FV),
- `GET|POST /kursy/{product:slug}/zamowienie` — checkout,
- `GET /kursy/{product:slug}/zamowienie/{ident}` — edycja odroczonego zamówienia,
- `GET /zamowienia-kursow/{ident}` — podsumowanie (numer ID, PDF i EDYTUJ jak przy szkoleniach),
- menu **Kursy** znajduje się bezpośrednio po **Szkolenia**,
- kursy nagrane nie są pokazywane na stronie głównej.

Katalog pokazuje kursy z publiczną ofertą. **Można kupić** i **pokazuj w katalogu** są niezależne: kurs może być w `/kursy` ze statusem „Sprzedaż wyłączona” (portfolio / nieaktualna wersja). Taka strona ma `noindex` i nie trafia do sitemapy. Checkout jest zablokowany. Osoba z aktywnym dostępem nadal widzi „Przejdź do kursu”.

Aktywna promocja z datą końcową pokazuje na katalogu, ofercie i checkoutcie te same informacje co szkolenia: „Promocja trwa do” i **najniższą cenę z 30 dni przed obniżką** (z `price_offer_histories` w bazie `pneadm`; gdy brak historii — cena regularna wariantu). Licznik do końca promocji jest opcjonalny (`product_prices.show_promotion_countdown`). Korekta wpisów (test / pomyłka) jest tylko w ADM.

Zalogowany uczestnik (ten sam e-mail co `online_course_enrollments`) widzi na `/kursy` i na ofercie stan swojego dostępu zamiast zwykłego cennika: bezterminowy → „Przejdź do kursu”; czasowy aktywny → data końca + przedłużenie tymi samymi wariantami; przedsprzedaż → „Dostęp od”; po wygaśnięciu → zwykły zakup. Gość i zamawiający z innym e-mailem widzą normalną ofertę.

## Zamówienie

`ProductCheckoutController` zapisuje dane biznesowe w bazie `pneadm`:

- nagłówek `form_orders` z `order_kind = product` i pustym legacy `product_id`,
- `order_items` ze snapshotem nazwy, ceny, VAT i dostępu,
- `order_item_recipients` dla wszystkich uczestników,
- równoległe `form_order_participants` dla kompatybilności panelu i faktur,
- `online_payment_orders` z pustym `course_id` przy PayU/PayNow.

Kwota = cena aktualnego wariantu × liczba uczestników. Zmiana późniejszej ceny lub promocji nie zmienia istniejącego zamówienia.

Przy NIP nabywcy i odbiorcy jest ten sam przycisk **Wpisz NIP i pobierz dane z GUS** co w zamówieniu szkolenia (`POST /courses/gus-lookup-by-nip`).

## Dashboard uczestnika

Na `/dashboard/kursy-online` karta oczekująca pojawia się **tylko wtedy, gdy e-mail zalogowanego konta jest e-mailem uczestnika** (`order_item_recipients`), nie zamawiającego.

Nie tworzymy sztucznego zapisu w `online_course_enrollments`. To osobna karta na tej samej siatce:

- faktura odroczona albo płatność już zaksięgowana, ale bez nadanego dostępu — zablokowana karta i komunikat, że zespół pnedu.pl nada dostęp;
- płatność online nieopłacona / anulowana / nieudana — te same kafelki plus **Dokończ płatność** i **Anuluję / rezygnuję z zamówienia** (modal Bootstrap, tylko zamówienia nadal czekające na wpłatę);
- po `paid` i po nadaniu dostępu komunikaty płatności znikają, a pojawia się zwykły kurs;
- po wycofaniu dostępu w ADM karta oczekująca może wrócić.

Rezygnacja oznacza tylko kartę tego uczestnika (`order_item_recipients.status = cancelled`). Pozostali na tym samym zamówieniu nadal widzą swoją kartę. Całe `form_orders.cancelled_at` ustawiane jest dopiero, gdy nie zostanie nikt aktywny. Konto pnedu nie jest ruszane. Licznik „Kursy online” w menu wlicza też karty oczekujące, żeby nie pokazywać zera przy widocznej kafelce.

## Dostawa dostępu

`ProductOrderFulfillmentService`:

1. rozpoznaje kurs przez snapshot `online_course_id`,
2. tworzy albo wykorzystuje konto po znormalizowanym e-mailu,
3. tworzy albo aktualizuje `online_course_enrollments`,
4. zapisuje idempotentny `order_fulfillments`,
5. wysyła e-mail z logowaniem lub ustawieniem hasła.

Płatność online wywołuje fulfillment automatycznie po `paid`. Panel ADM wywołuje ten sam kod przez chroniony endpoint:

`POST /api/internal/form-orders/{id}/fulfill-product` (opcjonalnie `recipient_id`)
`POST /api/internal/form-orders/{id}/revoke-product` (opcjonalnie `recipient_id`)

Wymagany jest identyczny token `PNEDU_INTERNAL_API_TOKEN` w obu aplikacjach. Wycofanie w panelu ADM korzysta z lokalnego serwisu pneadm (usuwa enrollment, zeruje fulfillment, zostawia konto).

## Przedsprzedaż i gwarancja satysfakcji

- start dostępu: od nadania albo od `access_starts_at` wariantu (snapshot na zamówieniu),
- okres czasowy liczy się od późniejszej daty: nadanie albo zaplanowany start,
- oferta pokazuje `satisfaction_guarantee_days` (domyślnie 30); zwrot tylko e-mailem/telefonem, bez przycisku na koncie,
- oświadczenie 14-dniowe (`early_performance_accepted`) tylko dla osoby/JDG przy natychmiastowym starcie — przy przedsprzedaży jest ukryte,
- na dashboardzie enrollment przed datą startu to zablokowana karta „Dostęp od…” bez „Przejdź do kursu”.

## Reguły czasu

- nowy albo wygasły dostęp czasowy: od późniejszej z dat (nadanie / zaplanowany start),
- aktywny dostęp czasowy: od dotychczasowego wygaśnięcia,
- `fixed_until` nie skraca późniejszej istniejącej daty,
- dostęp bezterminowy nigdy nie jest skracany.

## SEO

- do sitemapy wchodzą tylko kursy, które da się kupić,
- oferta z wyłączoną sprzedażą zostaje na `/kursy`, ale ma `noindex, nofollow`,
- oferta ma konfigurowalne meta title/description i canonical,
- checkout, edycja i podsumowanie są `noindex,nofollow`,
- obrazy mają opisowe `alt`.

## Ograniczenie prawne

Obowiązuje Regulamin `2026-09-08`. Oświadczenie kursu pozostaje dotychczasowe, dopóki nie zatwierdzisz kwalifikacji produktu. Projekt nowej wersji nie jest publiczny. Przed produkcją: [LEGAL_PRODUCT_LAUNCH_DECISIONS.md](../../pneadm/docs/LEGAL_PRODUCT_LAUNCH_DECISIONS.md).

## Testy

```bash
sail artisan test tests/Feature/ProductCheckoutTest.php
sail artisan test tests/Feature/DashboardPendingProductCoursesTest.php
sail artisan test tests/Feature/StorefrontOwnerAccessTest.php
sail artisan test tests/Feature/StorefrontArchiveSalesTest.php
sail artisan test tests/Unit/ProductAccessExpiryServiceTest.php
sail artisan test tests/Unit/ProductLegalCheckoutServiceTest.php
sail artisan view:cache
```

Kanon modelu danych i deployu:

- `pneadm/docs/PRODUCT_COMMERCE.md`
- `pneadm/docs/deploy/2026-09-product-catalog-online-courses-deploy.md`
