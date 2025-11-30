# 🔄 Aktualizacja .env i zmiana nazwy bazy danych

## Krok 1: Zaktualizuj plik `.env`

Otwórz plik `.env` w projekcie `pnedu` i zmień:

### PRZED (stare):
```env
# Secondary database connection for admpnedu (see config/database.php)
DB_ADMPNEDU_HOST=${DB_HOST}
DB_ADMPNEDU_PORT=${DB_PORT}
DB_ADMPNEDU_DATABASE=admpnedu
DB_ADMPNEDU_USERNAME=${DB_USERNAME}
DB_ADMPNEDU_PASSWORD=${DB_PASSWORD}
```

### PO (nowe):
```env
# Secondary database connection for pneadm (see config/database.php)
DB_PNEADM_HOST=${DB_HOST}
DB_PNEADM_PORT=${DB_PORT}
DB_PNEADM_DATABASE=pneadm
DB_PNEADM_USERNAME=${DB_USERNAME}
DB_PNEADM_PASSWORD=${DB_PASSWORD}
```

**Lub możesz usunąć te linie całkowicie** - kod ma domyślne wartości i fallback do starych zmiennych.

## Krok 2: Zmień nazwę bazy danych w MySQL

### Opcja A: Zmiana nazwy bazy (szybka, ale może nie działać w niektórych wersjach MySQL)

```bash
cd /home/hostnet/WEB-APP/pnedu
sail mysql -e "CREATE DATABASE IF NOT EXISTS pneadm;"
sail mysql -e "RENAME DATABASE admpnedu TO pneadm;"
```

**Uwaga:** `RENAME DATABASE` może nie działać w nowszych wersjach MySQL (8.0+). Jeśli dostaniesz błąd, użyj Opcji B.

### Opcja B: Kopiowanie danych (bezpieczniejsza, działa zawsze)

```bash
cd /home/hostnet/WEB-APP/pnedu

# 1. Utwórz nową bazę
sail mysql -e "CREATE DATABASE IF NOT EXISTS pneadm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Skopiuj wszystkie tabele i dane
sail mysqldump -u sail -ppassword admpnedu | sail mysql -u sail -ppassword pneadm

# 3. Sprawdź czy dane zostały skopiowane
sail mysql pneadm -e "SHOW TABLES;"

# 4. Jeśli wszystko OK, usuń starą bazę (OPCJONALNIE - najpierw sprawdź czy wszystko działa!)
# sail mysql -e "DROP DATABASE admpnedu;"
```

### Opcja C: Przez phpMyAdmin (graficznie)

1. Otwórz phpMyAdmin: http://localhost:8082
2. Kliknij na bazę `admpnedu` w lewym panelu
3. Kliknij zakładkę **"Operacje"** (Operations)
4. W sekcji **"Kopiuj bazę danych do:"** (Copy database to:)
   - Wpisz nową nazwę: `pneadm`
   - Zaznacz **"Struktura i dane"** (Structure and data)
   - Kliknij **"Wykonaj"** (Go)
5. Po skopiowaniu sprawdź czy nowa baza `pneadm` działa
6. Jeśli wszystko OK, możesz usunąć starą bazę `admpnedu`

## Krok 3: Wyczyść cache Laravel

```bash
cd /home/hostnet/WEB-APP/pnedu
sail artisan config:clear
sail artisan cache:clear
```

## Krok 4: Przetestuj połączenie

```bash
sail artisan tinker
```

W Tinker:
```php
// Test 1: Sprawdź połączenie
DB::connection('pneadm')->select('SELECT 1');
// Powinno zwrócić: [{"1": 1}]

// Test 2: Sprawdź czy modele działają
\App\Models\Course::count();
// Powinno zwrócić liczbę kursów

// Test 3: Sprawdź czy dane są dostępne
\App\Models\Course::first();
// Powinno zwrócić pierwszy kurs
```

## Krok 5: Sprawdź aplikację

1. **Strona kursów:**
   ```
   http://localhost:8081/szkolenia-online-live
   ```

2. **Formularz zamówienia:**
   ```
   http://localhost:8081/courses/402/deferred-order
   ```

3. **Sprawdź logi:**
   ```bash
   sail artisan pail
   ```

## ⚠️ Uwagi

1. **Backup:** Przed zmianą nazwy bazy, upewnij się że masz backup:
   ```bash
   sail mysqldump -u sail -ppassword admpnedu > backup_admpnedu_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Zgodność wsteczna:** Kod obsługuje obie wersje zmiennych (`DB_PNEADM_*` i `DB_ADMPNEDU_*`), więc możesz zaktualizować `.env` później, ale zalecane jest zrobienie tego teraz dla spójności.

3. **Uprawnienia:** Upewnij się, że użytkownik `sail` ma dostęp do bazy `pneadm`:
   ```sql
   GRANT ALL PRIVILEGES ON pneadm.* TO 'sail'@'%';
   FLUSH PRIVILEGES;
   ```

## ✅ Checklist

- [ ] Zaktualizowano plik `.env` (zmieniono `DB_ADMPNEDU_*` na `DB_PNEADM_*`)
- [ ] Utworzono/zaktualizowano bazę `pneadm` w MySQL
- [ ] Skopiowano dane z `admpnedu` do `pneadm` (jeśli używano Opcji B)
- [ ] Wyczyszczono cache Laravel
- [ ] Przetestowano połączenie w Tinker
- [ ] Przetestowano aplikację (strony działają)
- [ ] Sprawdzono logi (brak błędów)
- [ ] (Opcjonalnie) Usunięto starą bazę `admpnedu`

---

**Data:** $(date)  
**Status:** Gotowe do wykonania

