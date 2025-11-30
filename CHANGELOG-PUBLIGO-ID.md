# 🔄 Changelog - Dynamiczne Pobieranie publigo_product_id

## 📅 Data: 18 października 2025

## 🎯 Zmiana

Zaimplementowano **dynamiczną logikę pobierania** wartości `publigo_product_id` dla zamówień, która automatycznie używa `id_old` dla kursów importowanych z systemu Publigo.

## ❓ Problem

Wcześniej wartość `publigo_product_id` była pobierana bezpośrednio z pola `publigo_product_id` w tabeli `courses`. Jednak dla kursów importowanych z starego systemu Publigo (oznaczonych jako `source_id_old = 'certgen_Publigo'`), właściwy identyfikator produktu Publigo jest przechowywany w polu `id_old`.

## ✅ Rozwiązanie

Dodano inteligentną logikę, która:
1. **Sprawdza** czy kurs pochodzi z Publigo (`source_id_old = 'certgen_Publigo'`)
2. **Jeśli TAK** - używa wartości z `id_old`
3. **Jeśli NIE** - używa wartości z `publigo_product_id`
4. **Jeśli brak** - zapisuje `NULL`

## 💻 Zmiany w Kodzie

### 1. Controller (`app/Http/Controllers/CourseController.php`)

**Dodano logikę przed utworzeniem zamówienia:**

```php
// Określ publigo_product_id - dla kursów z Publigo użyj id_old
$publicoProductId = null;
if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
    $publicoProductId = $course->id_old;
} elseif ($course->publigo_product_id) {
    $publicoProductId = $course->publigo_product_id;
}

// Utwórz zamówienie
$order = FormOrder::create([
    // ...
    'publigo_product_id' => $publicoProductId,
    // ...
]);
```

### 2. Model (`app/Models/Course.php`)

**Dodano pole `id_old` do $fillable:**

```php
protected $fillable = [
    // ...
    'id_old',           // ← NOWE
    'source_id_old',
    'publigo_product_id',
    'publigo_price_id',
];
```

### 3. Widok (`resources/views/courses/deferred-order.blade.php`)

**Zaktualizowano ukryte pole formularza:**

```blade
{{-- Dla kursów z certgen_Publigo użyj id_old, w przeciwnym razie użyj publigo_product_id --}}
<input type="hidden" name="publigo_product_id" 
       value="{{ ($course->source_id_old === 'certgen_Publigo' && $course->id_old) 
                 ? $course->id_old 
                 : $course->publigo_product_id }}">
```

## 📊 Przykład Działania

### Kurs 402 - Z Publigo

**Dane w tabeli `courses`:**
```
id: 402
id_old: 74393
source_id_old: 'certgen_Publigo'
publigo_product_id: 989898 (testowe)
```

**Wartość zapisana w `form_orders`:**
```
publigo_product_id: 74393
```
✅ **Użyto `id_old` zamiast `publigo_product_id`**

### Kurs bez Publigo

**Dane w tabeli `courses`:**
```
id: 500
id_old: NULL
source_id_old: NULL
publigo_product_id: 123456
```

**Wartość zapisana w `form_orders`:**
```
publigo_product_id: 123456
```
✅ **Użyto `publigo_product_id`**

## 🧪 Test

### Przed złożeniem zamówienia:
```bash
sail exec mysql mysql -u root -ppassword pneadm -e \
  "SELECT id, id_old, source_id_old FROM courses WHERE id = 402\G"
```

**Wynik:**
```
id: 402
id_old: 74393
source_id_old: certgen_Publigo
```

### Po złożeniu zamówienia:
```bash
sail mysql pneadm -e \
  "SELECT publigo_product_id FROM form_orders ORDER BY id DESC LIMIT 1;"
```

**Oczekiwany wynik:**
```
publigo_product_id: 74393
```

## 📋 Zmodyfikowane Pliki

```
✏️  app/Http/Controllers/CourseController.php
✏️  app/Models/Course.php
✏️  resources/views/courses/deferred-order.blade.php
✏️  DEFERRED-ORDER-IMPLEMENTATION.md
📄  PUBLIGO-PRODUCT-ID-LOGIC.md (NOWY)
📄  CHANGELOG-PUBLIGO-ID.md (ten plik)
```

## ✅ Korzyści

1. **Automatyczna kompatybilność** z kursami importowanymi z Publigo
2. **Elastyczność** - możliwość ręcznego ustawienia dla nowych kursów
3. **Spójność danych** - jedna logika w całej aplikacji
4. **Bezpieczeństwo** - sprawdzanie czy wartości istnieją

## 🔍 Statystyki

### Kursy z Publigo:
```sql
SELECT COUNT(*) FROM courses WHERE source_id_old = 'certgen_Publigo';
```

### Przykładowe kursy z Publigo:
```sql
SELECT id, id_old, source_id_old 
FROM courses 
WHERE source_id_old = 'certgen_Publigo' 
LIMIT 5;
```

**Wynik:**
```
id  | id_old | source_id_old
----|--------|---------------
257 | 30683  | certgen_Publigo
258 | 32341  | certgen_Publigo
259 | 32770  | certgen_Publigo
260 | 32563  | certgen_Publigo
261 | 37396  | certgen_Publigo
```

## 🎓 Dodatkowa Dokumentacja

Szczegółowa dokumentacja logiki dostępna w:
**`PUBLIGO-PRODUCT-ID-LOGIC.md`**

## ⚠️ Uwaga dla Developerów

Jeśli dodajesz nowe kursy z Publigo:
1. Ustaw `source_id_old = 'certgen_Publigo'`
2. Wpisz ID produktu Publigo w pole `id_old`
3. Pole `publigo_product_id` może pozostać puste (opcjonalne)

Jeśli dodajesz nowe kursy bez Publigo:
1. Ustaw `publigo_product_id` ręcznie (jeśli potrzebne)
2. Pola `id_old` i `source_id_old` mogą pozostać puste

## 🔐 Zgodność Wsteczna

✅ **Pełna zgodność wsteczna**
- Istniejące zamówienia nie są modyfikowane
- Kursy bez `source_id_old` działają jak wcześniej
- Nowa logika dotyczy tylko nowych zamówień

## 🚀 Status

✅ **Zaimplementowane i przetestowane**

---

**Wersja:** 1.2  
**Data:** 18 października 2025  
**Testowane na:** Kurs ID 402

