# 🚀 Szybki przewodnik - Budowanie zasobów dla produkcji

## Problem
Na produkcji nie ma npm/node, więc nie można zbudować zasobów Vite bezpośrednio na serwerze.

## Rozwiązanie
Zbuduj zasoby lokalnie i wgraj je na produkcję.

---

## 📋 KROK PO KROKU

### 1️⃣ Lokalnie - Zbuduj zasoby

```bash
cd /home/hostnet/WEB-APP/pnedu
./vendor/bin/sail npm run build
```

**Sprawdź czy build się udał:**
```bash
ls -la public/build/
# Powinny być:
# - manifest.json
# - assets/app-*.css
# - assets/app-*.js
```

### 2️⃣ Wgraj katalog `public/build` na produkcję

**Opcja A - SCP (jeśli masz dostęp SSH):**
```bash
# Z lokalnego komputera (WSL):
scp -r public/build/ srv66127@h30.home.pl:/home/srv66127/app/public/
```

**Opcja B - Rsync (jeśli masz dostęp SSH):**
```bash
# Z lokalnego komputera (WSL):
rsync -avz public/build/ srv66127@h30.home.pl:/home/srv66127/app/public/build/
```

**Opcja C - Przez FTP/SFTP (FileZilla, WinSCP):**
1. Połącz się z serwerem przez klienta FTP
2. Przejdź do katalogu `/home/srv66127/app/public/` na serwerze
3. Wgraj cały katalog `build/` z lokalnego `public/build/`

### 3️⃣ Na produkcji - Sprawdź i ustaw uprawnienia

**Najpierw sprawdź właściciela plików:**
```bash
# Na produkcji:
cd /home/srv66127/app
ls -la public/ | head -5
# Sprawdź w kolumnie 3 (użytkownik) kto jest właścicielem plików
```

**Ustaw uprawnienia (dostosuj USER:GROUP do właściciela z powyższego):**
```bash
# Jeśli właścicielem jest srv66127:
chmod -R 755 public/build
chown -R srv66127:srv66127 public/build

# Jeśli właścicielem jest apache:
chmod -R 755 public/build
chown -R apache:apache public/build

# Jeśli właścicielem jest nginx:
chmod -R 755 public/build
chown -R nginx:nginx public/build
```

**Sprawdź czy pliki są dostępne:**
```bash
ls -la public/build/
# Powinny być: manifest.json i pliki assets/*.css oraz *.js
```

### 4️⃣ Wyczyść cache Laravel

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## ✅ Weryfikacja

Po wgraniu zasobów:

1. **Sprawdź czy pliki są na miejscu:**
   ```bash
   ls -la public/build/
   ```

2. **Otwórz w przeglądarce:**
   - http://twoja-domena.pl/login
   - http://twoja-domena.pl/register
   
3. **Sprawdź czy CSS się ładuje:**
   - Otwórz DevTools (F12)
   - Przejdź do zakładki Network
   - Odśwież stronę
   - Sprawdź czy pliki CSS/JS z `build/assets/` się ładują (status 200)

---

## 🔄 Gdy zaktualizujesz zasoby (CSS/JS)

Za każdym razem gdy zmieniasz pliki w `resources/sass/` lub `resources/js/`:

1. Zbuduj lokalnie: `./vendor/bin/sail npm run build`
2. Wgraj katalog `public/build` na produkcję
3. Wyczyść cache: `php artisan view:clear`

---

## 💡 Wskazówki

- **Backup przed wgraniem:** Jeśli na produkcji już istnieje katalog `public/build`, zrób backup:
  ```bash
  cp -r public/build public/build.backup
  ```

- **Sprawdź rozmiar:** Zasoby powinny mieć około 300KB (CSS + JS)
  ```bash
  du -sh public/build/
  ```

- **Jeśli nadal nie działa:** Sprawdź logi Laravel:
  ```bash
  tail -f storage/logs/laravel.log
  ```









