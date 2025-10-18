# 🔧 Logika Pobierania publigo_product_id

## 📋 Opis

Wartość `publigo_product_id` zapisywana w tabeli `form_orders` jest pobierana dynamicznie z tabeli `courses` zgodnie z następującą logiką:

## 🎯 Logika

### Priorytet 1: Kursy z Publigo (certgen_Publigo)
Jeśli kurs ma:
- `source_id_old = 'certgen_Publigo'`
- **ORAZ** pole `id_old` nie jest puste

**TO:** użyj wartości z pola `id_old` jako `publigo_product_id`

### Priorytet 2: Ręcznie ustawione
W przeciwnym razie:
- Użyj wartości z pola `publigo_product_id` (jeśli istnieje)

### Priorytet 3: Brak wartości
Jeśli żaden z powyższych warunków nie jest spełniony:
- `publigo_product_id = NULL`

## 💻 Implementacja

### Controller (`app/Http/Controllers/CourseController.php`)

```php
// Określ publigo_product_id - dla kursów z Publigo użyj id_old
$publicoProductId = null;
if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
    $publicoProductId = $course->id_old;
} elseif ($course->publigo_product_id) {
    $publicoProductId = $course->publigo_product_id;
}

// Zapisz w zamówieniu
$order = FormOrder::create([
    // ...
    'publigo_product_id' => $publicoProductId,
    // ...
]);
```

### Widok (`resources/views/courses/deferred-order.blade.php`)

```blade
{{-- Dla kursów z certgen_Publigo użyj id_old, w przeciwnym razie użyj publigo_product_id --}}
<input type="hidden" name="publigo_product_id" 
       value="{{ ($course->source_id_old === 'certgen_Publigo' && $course->id_old) 
                 ? $course->id_old 
                 : $course->publigo_product_id }}">
```

## 📊 Przykłady

### Przykład 1: Kurs z Publigo

**Dane w tabeli `courses`:**
```
id: 402
id_old: 74393
source_id_old: 'certgen_Publigo'
publigo_product_id: 989898
```

**Wynik:**
```
publigo_product_id zapisane w form_orders: 74393
```
*(użyto id_old, ponieważ source_id_old = 'certgen_Publigo')*

---

### Przykład 2: Kurs bez Publigo, z ręcznym publigo_product_id

**Dane w tabeli `courses`:**
```
id: 500
id_old: NULL
source_id_old: NULL
publigo_product_id: 123456
```

**Wynik:**
```
publigo_product_id zapisane w form_orders: 123456
```
*(użyto publigo_product_id)*

---

### Przykład 3: Kurs bez żadnych wartości

**Dane w tabeli `courses`:**
```
id: 600
id_old: NULL
source_id_old: NULL
publigo_product_id: NULL
```

**Wynik:**
```
publigo_product_id zapisane w form_orders: NULL
```

---

## 🔍 Sprawdzanie Wartości

### SQL Query - Kursy z Publigo
```sql
SELECT id, title, id_old, source_id_old, publigo_product_id 
FROM courses 
WHERE source_id_old = 'certgen_Publigo'
LIMIT 10;
```

### Tinker - Test logiki dla konkretnego kursu
```bash
sail artisan tinker
```

```php
$course = \App\Models\Course::find(402);

// Sprawdź wartości
echo "id_old: " . $course->id_old . "\n";
echo "source_id_old: " . $course->source_id_old . "\n";
echo "publigo_product_id (pole): " . $course->publigo_product_id . "\n\n";

// Test logiki
if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
    echo "UŻYJE: id_old = " . $course->id_old;
} elseif ($course->publigo_product_id) {
    echo "UŻYJE: publigo_product_id = " . $course->publigo_product_id;
} else {
    echo "UŻYJE: NULL";
}
```

### Sprawdzenie w bazie po złożeniu zamówienia
```sql
SELECT 
    id,
    ident,
    product_id,
    product_name,
    publigo_product_id,
    order_date
FROM form_orders 
WHERE product_id = 402
ORDER BY id DESC 
LIMIT 1;
```

## 🧪 Test

### Krok 1: Sprawdź kurs
```bash
sail mysql admpnedu -e "SELECT id, id_old, source_id_old, publigo_product_id FROM courses WHERE id = 402;"
```

**Oczekiwany wynik dla kursu 402:**
```
id    | id_old | source_id_old   | publigo_product_id
------|--------|-----------------|-------------------
402   | 74393  | certgen_Publigo | 989898
```

### Krok 2: Złóż zamówienie
1. Otwórz: http://localhost:8081/courses/402/deferred-order
2. Wyślij formularz (dane są już wypełnione)

### Krok 3: Sprawdź zapisaną wartość
```bash
sail mysql admpnedu -e "SELECT publigo_product_id FROM form_orders ORDER BY id DESC LIMIT 1;"
```

**Oczekiwany wynik:**
```
publigo_product_id
------------------
74393
```
*(wartość z id_old, nie z publigo_product_id)*

## ✅ Korzyści Tej Logiki

1. **Automatyczna kompatybilność** - Kursy importowane z Publigo automatycznie używają właściwego ID
2. **Elastyczność** - Możliwość ręcznego ustawienia `publigo_product_id` dla nowych kursów
3. **Spójność danych** - Jedna logika w kontrolerze i widoku
4. **Bezpieczeństwo** - Sprawdzanie czy wartości istnieją przed użyciem

## 📝 Model Course

Pola dodane do `$fillable`:
```php
protected $fillable = [
    // ...
    'id_old',
    'source_id_old',
    'publigo_product_id',
    'publigo_price_id',
];
```

## 🔄 Aktualizacja Dokumentacji

- ✅ `CourseController.php` - logika pobierania wartości
- ✅ `deferred-order.blade.php` - ukryte pole z właściwą wartością
- ✅ `Course.php` - dodano id_old do fillable
- ✅ `PUBLIGO-PRODUCT-ID-LOGIC.md` - dokumentacja logiki (ten plik)

## 📊 Statystyki

### Ile kursów ma source_id_old = 'certgen_Publigo'?
```bash
sail mysql admpnedu -e "SELECT COUNT(*) as total FROM courses WHERE source_id_old = 'certgen_Publigo';"
```

### Ile kursów ma ręcznie ustawiony publigo_product_id?
```bash
sail mysql admpnedu -e "SELECT COUNT(*) as total FROM courses WHERE publigo_product_id IS NOT NULL AND (source_id_old != 'certgen_Publigo' OR source_id_old IS NULL);"
```

---

**Data implementacji:** 18 października 2025  
**Status:** ✅ Zaimplementowane i przetestowane  
**Wersja:** 1.2

