# ⏰ Naprawa Strefy Czasowej - Przesunięcie o 2 godziny

> **⚠️ DEPRECATED (2026-07-04):** Zalecenie `DB_TIMEZONE=+02:00` było błędne dla wspólnej bazy `pneadm`.
> Obowiązuje: **[pneadm/docs/TIMEZONE_POLICY.md](../pneadm/docs/TIMEZONE_POLICY.md)** — `DB_TIMEZONE=+00:00` w obu serwisach.

## 🐛 Problem

Na produkcji data/czas zapisywana w polu `order_date` tabeli `form_orders` była cofnięta o 2 godziny w stosunku do rzeczywistego czasu polskiego.

**Przykład:**
- Zamówienie złożone o: `14:00` (polska godzina)
- Zapisane w bazie: `12:00` ❌

## 🔍 Przyczyna

Laravel domyślnie używa UTC do przechowywania dat w bazie, ale połączenie MySQL nie było skonfigurowane aby automatycznie konwertować daty zgodnie ze strefą czasową aplikacji (`Europe/Warsaw` = UTC+2).

MySQL zapisywał daty w UTC, ale przy odczycie nie konwertował ich z powrotem na polską strefę czasową.

## ✅ Rozwiązanie

Dodano konfigurację `timezone` do połączeń MySQL w pliku `config/database.php`, która automatycznie obsługuje konwersję stref czasowych.

## 💻 Zmiany w Kodzie

### `config/database.php`

Dodano linię `'timezone' => env('DB_TIMEZONE', '+02:00'),` do obu połączeń:

#### Połączenie 'mysql':
```php
'mysql' => [
    'driver' => 'mysql',
    // ... inne opcje ...
    'timezone' => env('DB_TIMEZONE', '+02:00'),  // ← DODANE
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

#### Połączenie 'pneadm':
```php
'pneadm' => [
    'driver' => 'mysql',
    // ... inne opcje ...
    'timezone' => env('DB_TIMEZONE', '+02:00'),  // ← DODANE
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

## 🚀 Wdrożenie na Produkcję

### Krok 1: Wgraj zmiany
```bash
git add config/database.php
git commit -m "Fix: Dodano timezone do połączeń MySQL (naprawa przesunięcia o 2h)"
git push origin main
```

### Krok 2: Na serwerze produkcyjnym
```bash
# Przejdź do katalogu projektu
cd /ścieżka/do/projektu

# Pobierz zmiany
git pull origin main

# Wyczyść cache (WAŻNE!)
php artisan config:clear
php artisan cache:clear

# Opcjonalnie: zrestartuj PHP-FPM/workers
sudo systemctl restart php8.4-fpm  # lub inna wersja PHP
# lub jeśli używasz queue workers:
php artisan queue:restart
```

### Krok 3 (Opcjonalnie): Dodaj do .env na produkcji
```bash
# W pliku .env możesz dodać (opcjonalne, domyślnie +02:00):
DB_TIMEZONE="+02:00"
```

## 🧪 Testowanie

### Test 1: Złóż zamówienie testowe
1. Otwórz formularz: `http://localhost:8081/courses/402/deferred-order`
2. Wypełnij i wyślij formularz
3. Sprawdź aktualną godzinę w Polsce

### Test 2: Sprawdź zapisany czas w bazie
```bash
sail mysql pneadm -e "SELECT 
    id, 
    ident, 
    order_date,
    NOW() as current_time,
    TIMESTAMPDIFF(MINUTE, order_date, NOW()) as minutes_diff
FROM form_orders 
ORDER BY id DESC 
LIMIT 1;"
```

**Oczekiwany wynik:**
- `order_date` powinien być równy godzinie gdy wysłano formularz (lub max 1-2 minuty różnicy)
- `minutes_diff` powinno być ~0-2 minuty

### Test 3: Sprawdź w aplikacji
```php
// W Tinker
sail artisan tinker

$order = \App\Models\FormOrder::latest()->first();
echo "Order date: " . $order->order_date->format('Y-m-d H:i:s') . "\n";
echo "Current time: " . now()->format('Y-m-d H:i:s') . "\n";
echo "Timezone: " . config('app.timezone') . "\n";
```

## 📊 Przed i Po

### ❌ PRZED (błąd)
```
Czas złożenia zamówienia: 14:00 (polska godzina)
Zapisane w bazie:         12:00 (UTC, bez konwersji)
Wyświetlane w aplikacji:  12:00 (błędnie)
```

### ✅ PO (poprawnie)
```
Czas złożenia zamówienia: 14:00 (polska godzina)
Zapisane w bazie:         14:00 (z uwzględnieniem timezone)
Wyświetlane w aplikacji:  14:00 (poprawnie)
```

## 🔧 Jak to Działa?

1. **APP_TIMEZONE=Europe/Warsaw** w `.env` - ustawia strefę czasową aplikacji
2. **'timezone' => '+02:00'** w config - informuje MySQL jak konwertować daty
3. Laravel automatycznie:
   - Zapisuje daty w bazie z uwzględnieniem strefy czasowej
   - Odczytuje daty i konwertuje je do APP_TIMEZONE
   - Używa `now()` z prawidłową strefą czasową

## ⚠️ Ważne Uwagi

### Na produkcji MUSISZ:
1. ✅ Wgrać zmiany w `config/database.php`
2. ✅ Wykonać `php artisan config:clear` (wyczyścić cache konfiguracji)
3. ✅ Opcjonalnie: dodać `DB_TIMEZONE="+02:00"` do `.env`

### Istniejące dane:
- ⚠️ Stare zamówienia w bazie (sprzed zmiany) mogą mieć błędny czas
- ✅ Nowe zamówienia (po zmianie) będą miały poprawny czas
- 💡 Możesz poprawić stare dane SQL query (jeśli potrzeba)

### Korekta starych danych (opcjonalnie):
```sql
-- UWAGA: Tylko jeśli potrzebujesz poprawić stare dane!
-- To doda 2 godziny do wszystkich dat order_date
UPDATE form_orders 
SET order_date = DATE_ADD(order_date, INTERVAL 2 HOUR)
WHERE order_date < '2025-10-18 19:00:00'  -- data wdrożenia poprawki
AND order_date IS NOT NULL;
```

## 🌍 Inne Strefy Czasowe

Jeśli kiedyś zmienisz lokalizację serwera:

```bash
# W .env zmień:
APP_TIMEZONE=Europe/Warsaw      # Aplikacja
DB_TIMEZONE="+02:00"            # Baza danych (Warszawa = UTC+2)

# Lub dla innej strefy:
APP_TIMEZONE=America/New_York   # Aplikacja
DB_TIMEZONE="-05:00"            # Baza (NY = UTC-5 zimą)
```

## 📋 Checklist Wdrożenia

- [ ] Wgrać zmiany w `config/database.php`
- [ ] Wykonać `git pull` na produkcji
- [ ] Wykonać `php artisan config:clear`
- [ ] Wykonać `php artisan cache:clear`
- [ ] Opcjonalnie: zrestartować PHP-FPM
- [ ] Przetestować złożenie zamówienia
- [ ] Sprawdzić czy czas jest poprawny

## 🔗 Powiązane Pliki

- `config/database.php` - konfiguracja połączeń z bazą
- `.env` - zmienne środowiskowe (APP_TIMEZONE, DB_TIMEZONE)
- `app/Http/Controllers/CourseController.php` - używa `now()` do zapisywania czasu

## 📚 Dokumentacja Laravel

- [Database Configuration](https://laravel.com/docs/11.x/database#configuration)
- [Date Mutators](https://laravel.com/docs/11.x/eloquent-mutators#date-mutators)

---

**Data poprawki:** 18 października 2025  
**Problem:** Przesunięcie czasu o 2 godziny  
**Status:** ✅ Naprawione  
**Wymaga:** Wyczyszczenie cache na produkcji








