# Changelog - Dodanie pola "Nazwa/imię nazwisko" w Danych Kontaktowych

## 📅 Data: 18 października 2025

## 🎯 Zmiana

Dodano nowe **wymagane pole** "Nazwa/imię nazwisko" na początku sekcji **DANE KONTAKTOWE ZAMAWIAJĄCEGO** w formularzu zamówienia z odroczonym terminem płatności.

## 📝 Szczegóły Implementacji

### 1. Formularz (`resources/views/courses/deferred-order.blade.php`)
**Dodano nowe pole:**
```html
<div class="mb-3">
    <label for="contact_name" class="form-label">Nazwa/imię nazwisko <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('contact_name') is-invalid @enderror" 
           id="contact_name" 
           name="contact_name" 
           value="{{ old('contact_name') }}" 
           required>
    @error('contact_name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

**Pozycja:** Pierwsze pole w sekcji "DANE KONTAKTOWE ZAMAWIAJĄCEGO", przed polem "Telefon kontaktowy"

### 2. Controller (`app/Http/Controllers/CourseController.php`)

#### Walidacja:
```php
'contact_name' => 'required|string|max:255',
```

#### Komunikat błędu:
```php
'contact_name.required' => 'Nazwa/imię nazwisko jest wymagane.',
```

#### Zapisywanie do bazy:
```php
'orderer_name' => $validated['contact_name'],
```

### 3. Dokumentacja
- ✅ Zaktualizowano `README-DEFERRED-ORDER.md`
- ✅ Zaktualizowano `DEFERRED-ORDER-IMPLEMENTATION.md`

## 📊 Struktura Sekcji "DANE KONTAKTOWE ZAMAWIAJĄCEGO"

### Przed zmianą:
1. Telefon kontaktowy *
2. E-mail do przesłania faktury *

### Po zmianie:
1. **Nazwa/imię nazwisko *** ⬅️ NOWE
2. Telefon kontaktowy *
3. E-mail do przesłania faktury *

## 💾 Zapis w Bazie Danych

| Pole w bazie | Źródło | Opis |
|--------------|--------|------|
| `orderer_name` | `contact_name` | Nazwa/imię nazwisko zamawiającego |

**Uwaga:** Pole `orderer_name` wcześniej było wypełniane wartością `buyer_name` (nazwa nabywcy). Teraz jest wypełniane dedykowaną wartością z pola kontaktowego.

## ✅ Walidacja

- **Typ:** `string`
- **Max długość:** 255 znaków
- **Wymagane:** TAK
- **Komunikat błędu:** "Nazwa/imię nazwisko jest wymagane."

## 🧪 Testowanie

### Test 1: Pole jest widoczne
1. Otwórz: http://localhost:8081/courses/402/deferred-order
2. Przewiń do sekcji "DANE KONTAKTOWE ZAMAWIAJĄCEGO"
3. ✅ Pole "Nazwa/imię nazwisko" powinno być pierwszym polem w tej sekcji

### Test 2: Walidacja działa
1. Pozostaw pole puste
2. Wyślij formularz
3. ✅ Powinien pojawić się komunikat: "Nazwa/imię nazwisko jest wymagane."

### Test 3: Zapis w bazie
1. Wypełnij formularz, wpisz w pole "Nazwa/imię nazwisko": "Jan Kowalski"
2. Wyślij formularz
3. Sprawdź w bazie:
```bash
sail mysql pneadm -e "SELECT orderer_name FROM form_orders ORDER BY id DESC LIMIT 1;"
```
4. ✅ Wartość `orderer_name` powinna być "Jan Kowalski"

## 📋 Zmodyfikowane Pliki

```
✏️  resources/views/courses/deferred-order.blade.php
✏️  app/Http/Controllers/CourseController.php
✏️  README-DEFERRED-ORDER.md
✏️  DEFERRED-ORDER-IMPLEMENTATION.md
```

## 🎯 Cel Zmiany

Dodanie dedykowanego pola dla osoby kontaktowej zamawiającej szkolenie, aby oddzielić:
- **Dane nabywcy** (instytucja/firma - dane do faktury)
- **Dane kontaktowe zamawiającego** (osoba fizyczna - kontakt operacyjny)

## 💡 Użycie

### Przykład wypełnienia:
- **Nabywca (faktura):** "Szkoła Podstawowa nr 5 w Warszawie"
- **Nazwa/imię nazwisko (kontakt):** "Anna Nowak" ⬅️ osoba zamawiająca
- **Telefon kontaktowy:** "123 456 789"
- **E-mail do faktury:** "sekretariat@sp5.edu.pl"

## 🔐 Zgodność wsteczna

✅ **Brak problemów ze zgodnością wsteczną**
- Dodano nowe wymagane pole (nie usunięto ani nie zmieniono istniejących pól)
- Istniejące zamówienia w bazie pozostają bez zmian
- Pole `buyer_name` nadal istnieje i jest zapisywane

## ✨ Status

✅ **Zaimplementowane i gotowe do użycia**

---

**Wersja:** 1.1  
**Data:** 18 października 2025  
**Autor zmian:** Implementacja na życzenie użytkownika

