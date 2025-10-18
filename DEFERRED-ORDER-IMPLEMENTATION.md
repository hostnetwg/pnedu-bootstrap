# Implementacja Formularza Zamówienia z Odroczonym Terminem Płatności

## 📋 Opis

Formularz zamówienia z odroczonym terminem płatności został zaimplementowany zgodnie ze wzorcem formularza ze starej platformy (zdalna-lekcja.pl).

## 🎯 Co zostało zrobione

### 1. **Model FormOrder** (`app/Models/FormOrder.php`)
- Połączenie z bazą `admpnedu`, tabela `form_orders`
- Wszystkie pola z tabeli dodane do `$fillable`
- Metoda `generateIdent()` do generowania unikalnych numerów zamówień (format: YYMMDD-XXXXXX)
- Relacja do modelu `Course`
- Odpowiednie casty dla pól (datetime, decimal, boolean)

### 2. **Migracja** (`database/migrations/2025_10_18_171335_add_publigo_fields_to_courses_table.php`)
- Dodano pola `publigo_product_id` i `publigo_price_id` do tabeli `courses`
- Migracja została uruchomiona

### 3. **Model Course** (`app/Models/Course.php`)
- Dodano pola `publigo_product_id` i `publigo_price_id` do `$fillable`

### 4. **Controller** (`app/Http/Controllers/CourseController.php`)
- Metoda `deferredOrder($id)` - wyświetla formularz
- Metoda `storeDeferredOrder(Request $request, $id)` - zapisuje zamówienie:
  - Walidacja wszystkich pól formularza
  - Generowanie unikalnego identyfikatora zamówienia
  - Zapisywanie danych do tabeli `form_orders`
  - Logowanie operacji
  - Przekierowanie z komunikatem sukcesu/błędu

### 5. **Routes** (`routes/web.php`)
- `GET /courses/{id}/deferred-order` - wyświetlanie formularza
- `POST /courses/{id}/deferred-order` - zapisywanie zamówienia

### 6. **Widok** (`resources/views/courses/deferred-order.blade.php`)
- Pełny formularz z wszystkimi sekcjami:
  - Informacje o szkoleniu (tytuł, data, prowadzący)
  - Nabywca (dane do faktury) - wymagane
  - Odbiorca (opcjonalne)
  - Dane kontaktowe zamawiającego
  - Dane uczestnika szkolenia
  - Uwagi do faktury
  - Termin płatności (dni)
  - Zgoda na przetwarzanie danych osobowych
- Pola ukryte dla integracji Publigo:
  - `publigo_product_id`
  - `publigo_price_id`
- Walidacja po stronie klienta (HTML5 required)
- Walidacja po stronie serwera
- Wyświetlanie błędów walidacji
- Przywracanie wartości po błędzie (old values)
- Komunikaty sukcesu/błędu

## 📊 Struktura Danych Zapisywanych w `form_orders`

### Pola zapisywane w bazie:

| Pole | Źródło | Wymagane | Opis |
|------|--------|----------|------|
| `ident` | Generowane | Tak | Unikalny identyfikator zamówienia (YYMMDD-XXXXXX) |
| `ptw` | `payment_terms` | Tak | Termin płatności w dniach |
| `order_date` | `now()` | Tak | Data i czas złożenia zamówienia |
| `product_id` | `$course->id` | Tak | ID kursu z tabeli courses |
| `product_name` | `$course->title` | Tak | Nazwa szkolenia |
| `product_price` | null | Nie | Cena (można dodać jeśli istnieje w courses) |
| `product_description` | `$course->description` | Nie | Opis szkolenia (bez HTML) |
| `publigo_product_id` | `$course->id_old` (jeśli source_id_old='certgen_Publigo') lub `$course->publigo_product_id` | Nie | ID produktu w Publigo (logika dynamiczna) |
| `publigo_price_id` | `$course->publigo_price_id` | Nie | ID ceny w Publigo |
| `publigo_sent` | 0 | Tak | Czy wysłano do Publigo (domyślnie: nie) |
| `participant_name` | `first_name + last_name` | Tak | Imię i nazwisko uczestnika |
| `participant_email` | `participant_email` | Tak | E-mail uczestnika |
| `orderer_name` | `contact_name` | Tak | Nazwa/imię nazwisko zamawiającego |
| `orderer_address` | `buyer_address` | Tak | Adres zamawiającego |
| `orderer_postal_code` | `buyer_postcode` | Tak | Kod pocztowy zamawiającego |
| `orderer_city` | `buyer_city` | Tak | Miasto zamawiającego |
| `orderer_phone` | `contact_phone` | Tak | Telefon kontaktowy |
| `orderer_email` | `contact_email` | Tak | E-mail do faktury |
| `buyer_name` | `buyer_name` | Tak | Nazwa nabywcy (dane do faktury) |
| `buyer_address` | `buyer_address` | Tak | Adres nabywcy |
| `buyer_postal_code` | `buyer_postcode` | Tak | Kod pocztowy nabywcy |
| `buyer_city` | `buyer_city` | Tak | Miasto nabywcy |
| `buyer_nip` | `buyer_nip` | Tak | NIP nabywcy |
| `recipient_name` | `recipient_name` | Nie | Nazwa odbiorcy (jeśli inny niż nabywca) |
| `recipient_address` | `recipient_address` | Nie | Adres odbiorcy |
| `recipient_postal_code` | `recipient_postcode` | Nie | Kod pocztowy odbiorcy |
| `recipient_city` | `recipient_city` | Nie | Miasto odbiorcy |
| `recipient_nip` | `recipient_nip` | Nie | NIP odbiorcy |
| `invoice_notes` | `invoice_notes` | Nie | Uwagi do faktury |
| `invoice_payment_delay` | `payment_terms` | Tak | Termin płatności (dni) |
| `status_completed` | 0 | Tak | Status zamówienia (domyślnie: niezakończone) |
| `ip_address` | `$request->ip()` | Nie | Adres IP użytkownika |
| `created_at` | auto | Tak | Data utworzenia rekordu |
| `updated_at` | auto | Tak | Data aktualizacji rekordu |

## 🔧 Konfiguracja Kursu

Aby formularz działał poprawnie dla kursu, należy ustawić pola `publigo_product_id` i `publigo_price_id` w tabeli `courses`.

### Przykład dla kursu 402:
```sql
UPDATE courses 
SET publigo_product_id = 989898, 
    publigo_price_id = 1 
WHERE id = 402;
```

## 🧪 Testowanie

### 1. Otwórz formularz
```
http://localhost:8081/courses/402/deferred-order
```

### 2. Wypełnij wymagane pola:
- **Nabywca**: Nazwa, adres, kod pocztowy, miasto, NIP
- **Dane kontaktowe**: Telefon, e-mail
- **Uczestnik**: Imię, nazwisko, e-mail
- **Termin płatności**: np. 14 dni
- **Zgoda**: Zaznacz checkbox

### 3. Opcjonalne:
- Dane odbiorcy (jeśli inny niż nabywca)
- Uwagi do faktury

### 4. Wyślij formularz

### 5. Sprawdź wynik:
- Powinno przekierować do szczegółów kursu z komunikatem sukcesu
- Sprawdź w bazie czy rekord został zapisany:
```bash
sail mysql admpnedu -e "SELECT * FROM form_orders ORDER BY id DESC LIMIT 1\G"
```

## 📝 Walidacja

### Wymagane pola (required):
- `buyer_name` - Nazwa nabywcy
- `buyer_address` - Adres
- `buyer_postcode` - Kod pocztowy
- `buyer_city` - Miasto
- `buyer_nip` - NIP
- `contact_name` - Nazwa/imię nazwisko zamawiającego
- `contact_phone` - Telefon kontaktowy
- `contact_email` - E-mail (format email)
- `participant_first_name` - Imię uczestnika
- `participant_last_name` - Nazwisko uczestnika
- `participant_email` - E-mail uczestnika (format email)
- `payment_terms` - Termin płatności (integer, min: 1)
- `consent` - Zgoda (checkbox, accepted)

### Opcjonalne pola:
- Wszystkie pola odbiorcy
- `invoice_notes`

### Limity długości:
- Nazwy/adresy: max 500 znaków
- Miasta: max 255 znaków
- Kody pocztowe: max 50 znaków
- NIP: max 50 znaków
- Telefon: max 50 znaków
- E-maile: max 255 znaków

## 🔐 Bezpieczeństwo

- ✅ CSRF protection (token)
- ✅ Walidacja danych po stronie serwera
- ✅ Zapisywanie IP użytkownika
- ✅ Logowanie operacji
- ✅ Try-catch dla obsługi błędów
- ✅ Sanityzacja HTML (strip_tags dla description)

## 📊 Porównanie ze Starym Formularzem

### Stary formularz (zdalna-lekcja.pl):
- Wysyłał `idP` (publigo_product_id)
- Dane nabywcy i odbiorcy
- Dane dostępowe do kursu (imię, nazwisko, email)
- Odroczony termin płatności (dni)

### Nowy formularz (localhost:8081):
- ✅ Wysyła `publigo_product_id` (jako hidden field)
- ✅ Wysyła `publigo_price_id` (jako hidden field)
- ✅ Dane nabywcy i odbiorcy (rozszerzone)
- ✅ Dane uczestnika (imię, nazwisko, email)
- ✅ Termin płatności (dni)
- ✅ Dodatkowo: dane kontaktowe, uwagi, IP, timestamp

## 🚀 Następne Kroki

1. **Integracja z Publigo**:
   - Dodać funkcję wysyłania zamówienia do API Publigo
   - Aktualizować pole `publigo_sent` i `publigo_sent_at`

2. **Powiadomienia e-mail**:
   - Wysyłanie potwierdzenia zamówienia do klienta
   - Powiadomienie administratora o nowym zamówieniu

3. **Panel administracyjny**:
   - Widok listy zamówień
   - Edycja statusu zamówienia
   - Generowanie faktur

4. **Rozszerzenia**:
   - Możliwość dodania wielu uczestników
   - Historia zamówień dla użytkownika
   - Eksport zamówień do CSV/PDF

## 📞 Pomoc Techniczna

W przypadku problemów sprawdź:
- Logi Laravel: `storage/logs/laravel.log`
- Logi Docker: `sail logs`
- Połączenie z bazą: `sail mysql admpnedu`

## ✅ Status Implementacji

- [x] Model FormOrder
- [x] Migracja pól publigo w courses
- [x] Controller: wyświetlanie formularza
- [x] Controller: zapisywanie zamówienia
- [x] Routing (GET i POST)
- [x] Widok formularza
- [x] Walidacja
- [x] Obsługa błędów
- [x] Komunikaty użytkownika
- [x] Logowanie operacji
- [x] Pola ukryte dla Publigo
- [ ] Integracja z API Publigo
- [ ] Powiadomienia e-mail
- [ ] Panel administracyjny

---

**Data implementacji**: 18 października 2025  
**Wersja**: 1.0  
**Status**: ✅ Gotowe do testowania

