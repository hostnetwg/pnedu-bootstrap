# ⏰ Podsumowanie Konfiguracji Strefy Czasowej

## 📋 Zmiany

### ✅ Naprawiono problem przesunięcia czasu o 2 godziny

**Problem:** Zamówienia na produkcji zapisywały się z czasem cofniętym o 2h względem rzeczywistej godziny polskiej.

**Rozwiązanie:** Dodano konfigurację `timezone` do połączeń MySQL.

---

## 🔧 Zmienione Pliki

### 1. `config/database.php`

Dodano linię `'timezone' => env('DB_TIMEZONE', '+02:00'),` do obu połączeń MySQL:

```php
'mysql' => [
    // ... inne opcje
    'timezone' => env('DB_TIMEZONE', '+02:00'),
],

'pneadm' => [
    // ... inne opcje
    'timezone' => env('DB_TIMEZONE', '+02:00'),
],
```

---

## 🌍 Konfiguracja Zmiennych Środowiskowych

### W pliku `.env`:

```env
# Strefa czasowa aplikacji (wymagane)
APP_TIMEZONE=Europe/Warsaw

# Strefa czasowa bazy danych (opcjonalne, domyślnie +02:00)
DB_TIMEZONE="+02:00"
```

### W pliku `.env.example` (dla nowych instalacji):

Dodaj te linie:

```env
APP_TIMEZONE=Europe/Warsaw
DB_TIMEZONE="+02:00"
```

---

## 🚀 Wdrożenie na Produkcję

### Kroki:

1. **Wgraj zmiany:**
   ```bash
   git push origin main
   ```

2. **Na serwerze produkcyjnym:**
   ```bash
   cd /ścieżka/do/projektu
   git pull origin main
   php artisan config:clear      # ⚠️ WYMAGANE!
   php artisan cache:clear
   sudo systemctl restart php8.4-fpm  # opcjonalne
   ```

3. **Przetestuj:**
   - Złóż zamówienie testowe
   - Sprawdź w bazie czy `order_date` jest poprawny

---

## 📊 Jak to Działa?

```
┌─────────────────────────────────────────────────────┐
│  Użytkownik wysyła formularz                        │
│  Czas: 14:00 (polska godzina)                      │
└──────────────────┬──────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────────────────┐
│  Laravel (APP_TIMEZONE=Europe/Warsaw)               │
│  now() zwraca: 2025-10-19 14:00:00                 │
└──────────────────┬──────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────────────────┐
│  MySQL Connection (timezone='+02:00')               │
│  Konwertuje i zapisuje: 2025-10-19 14:00:00        │
└──────────────────┬──────────────────────────────────┘
                   ↓
┌─────────────────────────────────────────────────────┐
│  Baza danych (form_orders.order_date)               │
│  TIMESTAMP: 2025-10-19 14:00:00                     │
└─────────────────────────────────────────────────────┘
```

### Przed poprawką (błąd):
```
Laravel now(): 14:00 → MySQL: 12:00 ❌ (UTC, bez konwersji)
```

### Po poprawce:
```
Laravel now(): 14:00 → MySQL: 14:00 ✅ (z timezone='+02:00')
```

---

## 🧪 Testowanie

### Test lokalny (WSL/Docker):
```bash
sail artisan tinker

# Sprawdź konfigurację:
config('app.timezone');              # "Europe/Warsaw"
config('database.connections.pneadm.timezone');  # "+02:00"

# Sprawdź czas:
now()->format('Y-m-d H:i:s');        # Aktualny czas polski
```

### Test produkcyjny:
```bash
php artisan tinker

# Sprawdź:
now()->format('Y-m-d H:i:s');
\App\Models\FormOrder::latest()->first()->order_date->format('Y-m-d H:i:s');
# Obie wartości powinny być zbliżone (różnica max kilka minut)
```

---

## 📝 Dodatkowe Zmienne .env

### Opcjonalne zmienne do dodania w `.env`:

```env
# ============================================
# TIMEZONE CONFIGURATION
# ============================================

# Strefa czasowa aplikacji Laravel
# Używana do formatowania dat, Carbon, now(), itp.
APP_TIMEZONE=Europe/Warsaw

# Strefa czasowa dla połączeń MySQL
# Polska: +02:00 (UTC+2)
# Format: "+HH:MM" lub "-HH:MM"
DB_TIMEZONE="+02:00"

# Uwaga: Po zmianie tych wartości wykonaj:
# php artisan config:clear
# php artisan cache:clear
```

---

## ⚠️ Ważne Uwagi

### 1. Cache MUSI być wyczyszczony
Po każdej zmianie w `config/database.php` lub `.env`:
```bash
php artisan config:clear
php artisan cache:clear
```

### 2. PHP-FPM może wymagać restartu
```bash
sudo systemctl restart php8.4-fpm
```

### 3. Queue workers muszą być zrestartowane
```bash
php artisan queue:restart
```

### 4. Istniejące dane
- Stare zamówienia mogą mieć błędny czas (sprzed poprawki)
- Nowe zamówienia będą miały poprawny czas
- Opcjonalnie możesz poprawić stare dane SQL query

---

## 🔍 Weryfikacja na Produkcji

```sql
-- Sprawdź ostatnie zamówienie
SELECT 
    id,
    ident,
    order_date,
    NOW() as current_time,
    TIMESTAMPDIFF(MINUTE, order_date, NOW()) as minutes_ago
FROM form_orders 
ORDER BY id DESC 
LIMIT 1;
```

**Oczekiwany wynik:**
- `order_date` = czas gdy wysłano formularz
- `minutes_ago` = ile minut temu (powinno być małe)

---

## 📚 Dokumentacja

- **Szczegółowa:** `TIMEZONE-FIX.md`
- **Wdrożenie:** `PRODUCTION-TIMEZONE-DEPLOY.md`
- **Ten plik:** Szybkie podsumowanie

---

## ✅ Checklist

```
Lokalnie (development):
[✓] Dodano timezone do config/database.php
[✓] Przetestowano lokalnie
[✓] Commit i push do repozytorium

Na produkcji:
[ ] git pull origin main
[ ] php artisan config:clear
[ ] php artisan cache:clear
[ ] Restart PHP-FPM (opcjonalnie)
[ ] Test zamówienia
[ ] Weryfikacja w bazie
```

---

**Status:** ✅ Gotowe do wdrożenia  
**Priorytet:** 🔴 Wysoki  
**Czas wdrożenia:** ~5 minut  
**Downtime:** 0 minut  
**Data:** 18 października 2025








