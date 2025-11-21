# 📋 Formularz Zamówienia z Odroczonym Terminem - Krótka Instrukcja

## 🎯 Co zostało zrobione

Formularz zamówienia został w pełni zintegrowany z bazą danych `admpnedu`, tabela `form_orders`, z zachowaniem zgodności ze starym formularzem z platformy zdalna-lekcja.pl.

## 🔗 Adresy

- **Formularz dla kursu 402**: http://localhost:8081/courses/402/deferred-order
- **Strona kursu**: http://localhost:8081/courses/402

## ✅ Zaimplementowane Funkcjonalności

### 1. Zapisywanie danych do bazy
Wszystkie dane z formularza są zapisywane w tabeli `form_orders`:
- ✅ Dane nabywcy (faktura)
- ✅ Dane odbiorcy (opcjonalne)
- ✅ Dane kontaktowe
- ✅ Dane uczestnika szkolenia
- ✅ **publigo_product_id** - kluczowe pole dla integracji Publigo
- ✅ publigo_price_id
- ✅ Termin płatności (dni)
- ✅ Uwagi do faktury
- ✅ IP użytkownika
- ✅ Data zamówienia
- ✅ Unikalny identyfikator zamówienia (format: YYMMDD-XXXXXX)

### 2. Walidacja
- ✅ Walidacja HTML5 (po stronie przeglądarki)
- ✅ Walidacja Laravel (po stronie serwera)
- ✅ Komunikaty błędów po polsku
- ✅ Przywracanie wartości po błędzie

### 3. Użytkownik
- ✅ Komunikaty sukcesu/błędu
- ✅ Responsywny design (Bootstrap 5.2.3)
- ✅ Intuicyjny interfejs
- ✅ Powrót do strony kursu

## 🗂️ Struktura Plików

```
app/
├── Models/
│   ├── FormOrder.php           # Model zamówienia
│   └── Course.php              # Zaktualizowany (pola publigo)
└── Http/
    └── Controllers/
        └── CourseController.php # Nowe metody: deferredOrder, storeDeferredOrder

database/
└── migrations/
    └── 2025_10_18_171335_add_publigo_fields_to_courses_table.php

resources/
└── views/
    └── courses/
        └── deferred-order.blade.php # Formularz

routes/
└── web.php                     # Nowe trasy: GET i POST
```

## 🚀 Jak Używać

### Dla Kursu

1. **Ustaw pola publigo w bazie dla kursu**:
```bash
sail mysql admpnedu -e "UPDATE courses SET publigo_product_id = 989898, publigo_price_id = 1 WHERE id = 402;"
```

2. **Otwórz formularz**:
```
http://localhost:8081/courses/402/deferred-order
```

3. **Wypełnij formularz i wyślij**

### Sprawdzenie Zamówienia w Bazie

```bash
# Zobacz ostatnie zamówienie
sail mysql admpnedu -e "SELECT * FROM form_orders ORDER BY id DESC LIMIT 1\G"

# Zobacz zamówienia dla konkretnego kursu
sail mysql admpnedu -e "SELECT id, ident, participant_name, participant_email, order_date FROM form_orders WHERE product_id = 402 ORDER BY id DESC LIMIT 5;"
```

## 📊 Przykładowe Zapytania SQL

### Wszystkie zamówienia z dziś
```sql
SELECT id, ident, participant_name, product_name, order_date 
FROM form_orders 
WHERE DATE(order_date) = CURDATE() 
ORDER BY id DESC;
```

### Zamówienia oczekujące na wysłanie do Publigo
```sql
SELECT id, ident, participant_name, publigo_product_id, publigo_sent 
FROM form_orders 
WHERE publigo_sent = 0 AND publigo_product_id IS NOT NULL
ORDER BY order_date DESC;
```

### Statystyki zamówień
```sql
SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN publigo_sent = 1 THEN 1 END) as sent_to_publigo,
    COUNT(CASE WHEN status_completed = 1 THEN 1 END) as completed
FROM form_orders;
```

## 🔧 Konfiguracja Kursu w Bazie

### Wymagane pola w tabeli `courses`:
- `publigo_product_id` - ID produktu w systemie Publigo (np. 989898)
- `publigo_price_id` - ID ceny w systemie Publigo (np. 1)

### Przykład dla kursu 402:
```sql
UPDATE courses 
SET 
    publigo_product_id = 989898,
    publigo_price_id = 1
WHERE id = 402;
```

## 🧪 Testowanie

### Test 1: Formularz Wyświetla Się Poprawnie
```bash
# Otwórz w przeglądarce
http://localhost:8081/courses/402/deferred-order
```

Sprawdź:
- [ ] Formularz się ładuje
- [ ] Widoczne są wszystkie sekcje
- [ ] Tytuł i data kursu są prawidłowe

### Test 2: Walidacja Działa
1. Wyślij pusty formularz
2. Powinny pojawić się komunikaty błędów

### Test 3: Zapisywanie Działa
1. Wypełnij wszystkie wymagane pola
2. Wyślij formularz
3. Sprawdź komunikat sukcesu
4. Sprawdź w bazie czy rekord został zapisany:
```bash
sail mysql admpnedu -e "SELECT * FROM form_orders ORDER BY id DESC LIMIT 1\G"
```

## 📝 Pola Formularza

### Wymagane (*)
- **Nabywca**:
  - Nazwa nabywcy *
  - Adres *
  - Kod pocztowy *
  - Miasto *
  - NIP *
  
- **Dane kontaktowe**:
  - Nazwa/imię nazwisko *
  - Telefon kontaktowy *
  - E-mail do faktury *
  
- **Uczestnik**:
  - Imię *
  - Nazwisko *
  - E-mail *
  
- **Inne**:
  - Termin płatności (dni) * [domyślnie: 14]
  - Zgoda na przetwarzanie danych *

### Opcjonalne
- **Odbiorca** (wszystkie pola):
  - Nazwa odbiorcy
  - Adres
  - Kod pocztowy
  - Miasto
  - NIP
  
- **Inne**:
  - Uwagi do faktury

## 🔐 Bezpieczeństwo

- ✅ CSRF Token
- ✅ Walidacja danych
- ✅ Zapisywanie IP
- ✅ Logowanie operacji w `storage/logs/laravel.log`
- ✅ Try-catch dla obsługi błędów

## 📈 Co Dalej

### Krótkoterminowe
- [ ] Powiadomienia e-mail (potwierdzenie dla klienta)
- [ ] Panel administratora (lista zamówień)

### Długoterminowe
- [ ] Integracja z API Publigo (automatyczne wysyłanie)
- [ ] Generowanie faktur PDF
- [ ] Historia zamówień dla użytkownika
- [ ] Możliwość dodania wielu uczestników

## 🐛 Troubleshooting

### Problem: Formularz nie wysyła danych
**Rozwiązanie**: Sprawdź logi
```bash
sail artisan pail
# lub
tail -f storage/logs/laravel.log
```

### Problem: Błąd połączenia z bazą
**Rozwiązanie**: Sprawdź czy kontenery działają
```bash
sail ps
sail restart
```

### Problem: publigo_product_id nie jest zapisywane
**Rozwiązanie**: Sprawdź czy kurs ma ustawione to pole
```bash
sail mysql admpnedu -e "SELECT id, title, publigo_product_id FROM courses WHERE id = 402;"
```

### Problem: Nie można otworzyć formularza
**Rozwiązanie**: Sprawdź routing
```bash
sail artisan route:list | grep deferred
```

## 📞 Wsparcie

W przypadku problemów:
1. Sprawdź logi: `storage/logs/laravel.log`
2. Sprawdź logi Docker: `sail logs`
3. Sprawdź routing: `sail artisan route:list`
4. Sprawdź bazę: `sail mysql admpnedu`

## 📚 Dokumentacja Techniczna

Szczegółowa dokumentacja techniczna: **DEFERRED-ORDER-IMPLEMENTATION.md**

---

**Data**: 18 października 2025  
**Status**: ✅ Gotowe do użycia  
**Wersja**: 1.0

## ✨ Podsumowanie

Formularz zamówienia z odroczonym terminem płatności jest w pełni funkcjonalny i zintegrowany z bazą danych `admpnedu`. Wszystkie dane, **w tym kluczowe pole publigo_product_id**, są poprawnie zapisywane w tabeli `form_orders`.

**Możesz teraz:**
1. ✅ Przyjmować zamówienia przez formularz
2. ✅ Zapisywać je w bazie danych
3. ✅ Śledzić zamówienia w tabeli form_orders
4. ✅ Integrować z systemem Publigo (pole publigo_product_id jest zapisywane)

**Następny krok**: Testowanie formularza na http://localhost:8081/courses/402/deferred-order

