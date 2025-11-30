# 🔄 Migracja nazwy bazy danych: admpnedu → pneadm

## ✅ Co zostało zrobione

Wszystkie pliki kodu zostały zaktualizowane:
- ✅ `config/database.php` - nazwa połączenia zmieniona na `'pneadm'`
- ✅ Wszystkie modele (Course, FormOrder, Instructor, CoursePriceVariant, CourseOnlineDetail)
- ✅ CourseController - wszystkie zapytania DB::connection()
- ✅ Migracje
- ✅ Dokumentacja

## 📋 Co należy zrobić teraz

### 1. Sprawdź nazwę bazy danych w MySQL

```bash
cd /home/hostnet/WEB-APP/pnedu
sail mysql -e "SHOW DATABASES;"
```

**Oczekiwany wynik:** Powinieneś zobaczyć bazę o nazwie `pneadm` (nie `admpnedu`).

### 2. Jeśli baza nazywa się jeszcze `admpnedu`, zmień nazwę

```bash
# Opcja A: Zmień nazwę bazy (jeśli nie ma zależności)
sail mysql -e "RENAME DATABASE admpnedu TO pneadm;"

# Opcja B: Utwórz nową bazę i skopiuj dane (bezpieczniejsze)
sail mysql -e "CREATE DATABASE IF NOT EXISTS pneadm;"
sail mysqldump -u sail -ppassword admpnedu | sail mysql -u sail -ppassword pneadm
```

### 3. Zaktualizuj plik `.env` (opcjonalnie, ale zalecane)

Jeśli masz w `.env` zmienne `DB_ADMPNEDU_*`, możesz je zmienić na `DB_PNEADM_*`:

```env
# Stare (działa, ale zalecane jest użycie nowych):
# DB_ADMPNEDU_HOST=mysql
# DB_ADMPNEDU_DATABASE=admpnedu
# DB_ADMPNEDU_USERNAME=sail
# DB_ADMPNEDU_PASSWORD=password

# Nowe (zalecane):
DB_PNEADM_HOST=mysql
DB_PNEADM_DATABASE=pneadm
DB_PNEADM_USERNAME=sail
DB_PNEADM_PASSWORD=password
```

**Uwaga:** Kod obsługuje obie wersje (zgodność wsteczna), więc nie jest to wymagane, ale zalecane dla spójności.

### 4. Wyczyść cache konfiguracji Laravel

```bash
sail artisan config:clear
sail artisan cache:clear
```

### 5. Przetestuj połączenie z bazą

```bash
# Test 1: Sprawdź czy połączenie działa
sail artisan tinker
```

W Tinker:
```php
DB::connection('pneadm')->select('SELECT 1');
// Powinno zwrócić: [{"1": 1}]

// Test 2: Sprawdź czy modele działają
\App\Models\Course::count();
// Powinno zwrócić liczbę kursów
```

### 6. Przetestuj aplikację

1. **Sprawdź stronę kursów:**
   ```
   http://localhost:8081/szkolenia-online-live
   ```

2. **Sprawdź formularz zamówienia:**
   ```
   http://localhost:8081/courses/402/deferred-order
   ```

3. **Sprawdź logi:**
   ```bash
   sail artisan pail
   ```

## 🔍 Weryfikacja

### Sprawdź czy wszystko działa:

```bash
# 1. Sprawdź połączenia w bazie
sail mysql pneadm -e "SHOW TABLES;"

# 2. Sprawdź czy modele działają
sail artisan tinker
# W Tinker:
\App\Models\Course::first();
\App\Models\FormOrder::count();
```

### Sprawdź logi błędów:

```bash
# Jeśli są błędy połączenia:
sail artisan pail
# lub
tail -f storage/logs/laravel.log
```

## ⚠️ Uwagi

1. **Zgodność wsteczna:** Kod obsługuje zarówno `DB_PNEADM_*` jak i `DB_ADMPNEDU_*`, więc istniejące `.env` będą działać.

2. **Nazwa bazy w MySQL:** Ważne jest, aby baza danych w MySQL rzeczywiście nazywała się `pneadm`. Jeśli nazywa się `admpnedu`, musisz ją przemianować lub zaktualizować `DB_PNEADM_DATABASE` w `.env`.

3. **Uprawnienia użytkownika:** Upewnij się, że użytkownik `sail` ma dostęp do bazy `pneadm`:
   ```sql
   GRANT ALL PRIVILEGES ON pneadm.* TO 'sail'@'%';
   FLUSH PRIVILEGES;
   ```

## 📝 Podsumowanie zmian

| Element | Przed | Po |
|---------|-------|-----|
| Nazwa połączenia w kodzie | `'admpnedu'` | `'pneadm'` |
| Zmienne środowiskowe (zalecane) | `DB_ADMPNEDU_*` | `DB_PNEADM_*` |
| Nazwa bazy w MySQL | `admpnedu` | `pneadm` |
| Komendy w dokumentacji | `sail mysql admpnedu` | `sail mysql pneadm` |

## ✅ Checklist

- [ ] Sprawdzono nazwę bazy w MySQL (`SHOW DATABASES;`)
- [ ] Baza nazywa się `pneadm` (lub zaktualizowano `.env`)
- [ ] Wyczyszczono cache Laravel (`sail artisan config:clear`)
- [ ] Przetestowano połączenie w Tinker
- [ ] Przetestowano aplikację (strony kursów, formularz)
- [ ] Sprawdzono logi (brak błędów)

---

**Data migracji:** $(date)  
**Status:** Gotowe do testowania

