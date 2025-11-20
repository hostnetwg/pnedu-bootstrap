# 🚀 SZYBKI PRZEWODNIK - Wdrożenie Poprawki Strefy Czasowej na PRODUKCJĘ

## ⚡ Quick Start (dla admina produkcji)

### 📦 Co zostało zmienione?
- Dodano `timezone` do konfiguracji MySQL w `config/database.php`
- Naprawa: daty nie będą już cofnięte o 2 godziny

---

## 🎯 KROK PO KROKU - Wdrożenie na Produkcję

### 1️⃣ Wgraj zmiany (deweloper)
```bash
# Lokalnie (już zrobione)
git add config/database.php
git commit -m "Fix: Timezone dla MySQL (naprawa przesunięcia -2h)"
git push origin main
```

### 2️⃣ Na serwerze produkcyjnym (admin/deweloper)

**A. Połącz się z serwerem:**
```bash
ssh user@your-production-server.com
```

**B. Przejdź do katalogu projektu:**
```bash
cd /var/www/pnedu-bootstrap  # lub inna ścieżka
```

**C. Pobierz zmiany:**
```bash
git pull origin main
```

**D. ⚠️ NAJWAŻNIEJSZE - Wyczyść cache:**
```bash
php artisan config:clear
php artisan cache:clear
```

**E. Opcjonalnie - Restart serwisów:**
```bash
# Jeśli używasz PHP-FPM:
sudo systemctl restart php8.4-fpm

# Jeśli używasz queue workers:
php artisan queue:restart

# Jeśli używasz Supervisor:
sudo supervisorctl restart all
```

### 3️⃣ Weryfikacja
```bash
# Sprawdź czy konfiguracja jest załadowana:
php artisan tinker

# W tinker wpisz:
config('database.connections.admpnedu.timezone');
# Powinno zwrócić: "+02:00"

# Sprawdź aktualny czas:
now()->format('Y-m-d H:i:s');
# Powinno pokazać aktualną polską godzinę
```

---

## ✅ CHECKLIST Wdrożenia

```
[ ] 1. Wgrać zmiany do repozytorium (git push)
[ ] 2. Połączyć się z serwerem produkcyjnym
[ ] 3. Wykonać: git pull origin main
[ ] 4. Wykonać: php artisan config:clear  ⚠️ KRYTYCZNE!
[ ] 5. Wykonać: php artisan cache:clear
[ ] 6. Opcjonalnie: Restart PHP-FPM/workers
[ ] 7. Przetestować złożenie zamówienia
[ ] 8. Sprawdzić w bazie czy czas jest poprawny
```

---

## 🧪 Test Po Wdrożeniu

### Test 1: Złóż zamówienie testowe
1. Otwórz formularz na produkcji
2. Zapisz aktualną godzinę (np. 14:30)
3. Wyślij formularz
4. Sprawdź w bazie:

```sql
SELECT 
    id,
    ident,
    order_date,
    NOW() as db_current_time
FROM form_orders 
ORDER BY id DESC 
LIMIT 1;
```

**Oczekiwany wynik:**
- `order_date` = ~14:30 (godzina gdy wysłano)
- `db_current_time` = aktualna godzina
- **Różnica max 1-2 minuty**

### Test 2: Szybki check w terminalu
```bash
# Na serwerze produkcyjnym:
php artisan tinker

# Wpisz:
\App\Models\FormOrder::latest()->first()->order_date->format('Y-m-d H:i:s');
now()->format('Y-m-d H:i:s');
```

Obie wartości powinny być w polskiej strefie czasowej.

---

## ⚠️ Typowe Problemy

### Problem 1: Cache nie został wyczyszczony
**Objaw:** Nadal błędny czas mimo zmian
**Rozwiązanie:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache  # opcjonalnie
```

### Problem 2: Używane OPcache
**Objaw:** Zmiany nie są widoczne
**Rozwiązanie:**
```bash
# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### Problem 3: Queue workers używają starego cache
**Objaw:** Zamówienia z queue mają błędny czas
**Rozwiązanie:**
```bash
php artisan queue:restart
```

---

## 📝 Opcjonalne: Dodaj do .env

Możesz dodać do pliku `.env` na produkcji (opcjonalnie):

```env
# Strefa czasowa Polski
APP_TIMEZONE=Europe/Warsaw
DB_TIMEZONE="+02:00"
```

Jeśli dodasz `DB_TIMEZONE` do `.env`, to zmień w `config/database.php` z:
```php
'timezone' => env('DB_TIMEZONE', '+02:00'),
```
na:
```php
'timezone' => env('DB_TIMEZONE'),
```

Ale to nie jest wymagane - domyślna wartość `+02:00` działa świetnie.

---

## 🔄 Rollback (gdyby coś poszło nie tak)

Jeśli z jakiegoś powodu trzeba cofnąć zmiany:

```bash
# Na serwerze
git revert HEAD  # cofnij ostatni commit
php artisan config:clear
php artisan cache:clear
```

Lub usuń ręcznie linię `'timezone'` z `config/database.php`.

---

## 📊 Korekta Starych Danych (Opcjonalnie)

Jeśli masz stare zamówienia z błędnym czasem i chcesz je poprawić:

```sql
-- UWAGA: Uruchom to TYLKO RAZ po wdrożeniu!
-- To doda 2 godziny do starych zamówień

-- Najpierw sprawdź ile rekordów będzie dotkniętych:
SELECT COUNT(*) 
FROM form_orders 
WHERE order_date < '2025-10-19 00:00:00'  -- data wdrożenia
AND order_date IS NOT NULL;

-- Jeśli wynik OK, wykonaj update:
UPDATE form_orders 
SET order_date = DATE_ADD(order_date, INTERVAL 2 HOUR)
WHERE order_date < '2025-10-19 00:00:00'  -- data wdrożenia
AND order_date IS NOT NULL;

-- Sprawdź wynik:
SELECT id, ident, order_date 
FROM form_orders 
ORDER BY id DESC 
LIMIT 10;
```

⚠️ **UWAGA:** Backup bazy przed wykonaniem UPDATE!

---

## 📞 Kontakt w Razie Problemów

Jeśli coś poszło nie tak:
1. Sprawdź logi: `tail -f storage/logs/laravel.log`
2. Sprawdź konfigurację: `php artisan config:show database`
3. Sprawdź czy cache został wyczyszczony
4. Zrestartuj PHP-FPM/workers

---

## ✅ Podsumowanie

Po wykonaniu tych kroków:
- ✅ Nowe zamówienia będą miały poprawny czas (polska strefa)
- ✅ Nie będzie już przesunięcia o 2 godziny
- ✅ Kompatybilność z istniejącymi danymi

**Czas wdrożenia: ~5 minut**

---

**Priorytet:** 🔴 WYSOKI (naprawa krytycznego błędu)  
**Wymagane restart:** PHP-FPM (zalecane)  
**Downtime:** 0 minut  
**Data:** 18 października 2025







