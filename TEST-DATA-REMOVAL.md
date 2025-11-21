# 🧪 Usuwanie Danych Testowych z Formularza

## ⚠️ UWAGA
Formularz zamówienia zawiera obecnie domyślne dane testowe do celów testowania.

## 📝 Dane Testowe

### NABYWCA (dane do faktury)
- Nazwa nabywcy: `Gmina Bieżuń`
- Adres: `ul. Warszawska 5`
- Kod pocztowy: `09-320`
- Miasto: `Bieżuń`
- NIP: `5110265245`

### ODBIORCA (opcjonalnie)
- Nazwa odbiorcy: `Szkoła Podstawowa im. Andrzeja Zamoyskiego`
- Adres: `ul. Andrzeja Zamoyskiego 28`
- Kod pocztowy: `09-320`
- Miasto: `Bieżuń`

### DANE KONTAKTOWE ZAMAWIAJĄCEGO
- Nazwa/imię nazwisko: `Zespół Placówek Oświatowych w Bieżuniu`
- Telefon kontaktowy: `23 76 876 54`
- E-mail do faktury: `waldemar.grabowski@zdalna-lekcja.pl`

### DANE UCZESTNIKA SZKOLENIA
- Imię: `Waldemar`
- Nazwisko: `Grabowski`
- E-mail: `waldemar.grabowski@hostnet.pl`

### INNE
- Uwagi do faktury: `Uwaga do faktury`

## 🗑️ Jak Usunąć Dane Testowe

Po zakończeniu testów należy usunąć domyślne wartości z pliku:
**`resources/views/courses/deferred-order.blade.php`**

### Metoda 1: Automatyczne usunięcie (Polecana)

Uruchom poniższą komendę aby usunąć wszystkie wartości testowe:

```bash
sail artisan tinker
```

Lub możesz użyć search/replace w edytorze.

### Metoda 2: Ręczne usunięcie

W pliku `resources/views/courses/deferred-order.blade.php` zamień:

#### NABYWCA
```blade
value="{{ old('buyer_name', 'Gmina Bieżuń') }}"
```
na:
```blade
value="{{ old('buyer_name') }}"
```

Podobnie dla wszystkich pól:
- `buyer_name`: usuń `'Gmina Bieżuń'`
- `buyer_address`: usuń `'ul. Warszawska 5'`
- `buyer_postcode`: usuń `'09-320'`
- `buyer_city`: usuń `'Bieżuń'`
- `buyer_nip`: usuń `'5110265245'`

#### ODBIORCA
- `recipient_name`: usuń `'Szkoła Podstawowa im. Andrzeja Zamoyskiego'`
- `recipient_address`: usuń `'ul. Andrzeja Zamoyskiego 28'`
- `recipient_postcode`: usuń `'09-320'`
- `recipient_city`: usuń `'Bieżuń'`

#### DANE KONTAKTOWE
- `contact_name`: usuń `'Zespół Placówek Oświatowych w Bieżuniu'`
- `contact_phone`: usuń `'23 76 876 54'`
- `contact_email`: usuń `'waldemar.grabowski@zdalna-lekcja.pl'`

#### UCZESTNIK
- `participant_first_name`: usuń `'Waldemar'`
- `participant_last_name`: usuń `'Grabowski'`
- `participant_email`: usuń `'waldemar.grabowski@hostnet.pl'`

#### UWAGI
- `invoice_notes`: zamień `{{ old('invoice_notes', 'Uwaga do faktury') }}` na `{{ old('invoice_notes') }}`

### Metoda 3: Szybkie wyszukaj i zamień

W edytorze kodu (VS Code, Cursor, etc.):

1. **Wyszukaj:** `old\('([^']+)',\s*'[^']*'\)`
2. **Zamień na:** `old('$1')`
3. **Użyj regex:** TAK

Lub prościej:

**Wyszukaj wszystkie wystąpienia:**
```
, '[wartość testowa]'
```

**I zamień na puste:**
```
(nic)
```

## ✅ Weryfikacja

Po usunięciu danych testowych, formularz powinien wyświetlać się z pustymi polami.

Sprawdź:
```
http://localhost:8081/courses/402/deferred-order
```

Pola powinny być puste (poza domyślną wartością "14" dla terminu płatności, którą można zostawić).

## 📋 Quick Remove Script

Możesz też użyć tego skryptu sed (w terminalu):

```bash
cd /home/hostnet/WEB-APP/pnedu-bootstrap

# Backup
cp resources/views/courses/deferred-order.blade.php resources/views/courses/deferred-order.blade.php.backup

# Remove test data
sed -i "s/old('buyer_name', 'Gmina Bieżuń')/old('buyer_name')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('buyer_address', 'ul\. Warszawska 5')/old('buyer_address')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('buyer_postcode', '09-320')/old('buyer_postcode')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('buyer_city', 'Bieżuń')/old('buyer_city')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('buyer_nip', '5110265245')/old('buyer_nip')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('recipient_name', 'Szkoła Podstawowa im\. Andrzeja Zamoyskiego')/old('recipient_name')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('recipient_address', 'ul\. Andrzeja Zamoyskiego 28')/old('recipient_address')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('recipient_postcode', '09-320')/old('recipient_postcode')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('recipient_city', 'Bieżuń')/old('recipient_city')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('contact_name', 'Zespół Placówek Oświatowych w Bieżuniu')/old('contact_name')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('contact_phone', '23 76 876 54')/old('contact_phone')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('contact_email', 'waldemar\.grabowski@zdalna-lekcja\.pl')/old('contact_email')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('participant_first_name', 'Waldemar')/old('participant_first_name')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('participant_last_name', 'Grabowski')/old('participant_last_name')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('participant_email', 'waldemar\.grabowski@hostnet\.pl')/old('participant_email')/g" resources/views/courses/deferred-order.blade.php
sed -i "s/old('invoice_notes', 'Uwaga do faktury')/old('invoice_notes')/g" resources/views/courses/deferred-order.blade.php

echo "✅ Dane testowe zostały usunięte!"
```

## ⚠️ Przypomnienie

**NIE ZAPOMNIJ** usunąć danych testowych przed wdrożeniem na produkcję!

---

**Data dodania danych testowych:** 18 października 2025  
**Do usunięcia po:** Zakończeniu testów

