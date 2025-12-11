# Instrukcja wdrożenia na produkcji

## Po wykonaniu git pull na produkcji wykonaj:

### 1. Zaktualizuj autoloader Composer

**Opcja A:** Jeśli composer jest dostępny globalnie:
```bash
composer dump-autoload
```

**Opcja B:** Jeśli composer jest w vendor/bin:
```bash
php vendor/bin/composer dump-autoload
```

**Opcja C:** Jeśli masz composer.phar w katalogu głównym:
```bash
php composer.phar dump-autoload
```

**Opcja D:** Jeśli używasz Laravel Sail:
```bash
./vendor/bin/sail composer dump-autoload
# lub
sail composer dump-autoload
```

**Opcja E:** Jeśli nie masz dostępu do composer, możesz ręcznie odświeżyć autoload:
```bash
php artisan clear-compiled
php artisan optimize:clear
```

### 2. Wyczyść cache Laravel
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Zbuduj zasoby frontend (Vite) - **WAŻNE dla CSS/JS**
**To jest kluczowe dla poprawnego wyświetlania formularzy logowania/rejestracji!**

**Opcja A:** Jeśli npm/node są dostępne na serwerze:
```bash
npm install
npm run build
```

**Opcja B:** Jeśli używasz Laravel Sail:
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
# lub
sail npm install
sail npm run build
```

**Opcja C:** Jeśli nie masz npm/node na produkcji (ZALECANE):

**Krok 1 - Lokalnie (na swoim komputerze):**
```bash
cd /home/hostnet/WEB-APP/pnedu
./vendor/bin/sail npm run build

# Sprawdź czy pliki zostały utworzone:
ls -la public/build/
# Powinny być: manifest.json i pliki assets/*.css oraz *.js
```

**Krok 2 - Wgraj katalog public/build na produkcję:**

**Metoda A - SCP (jeśli masz dostęp SSH):**
```bash
# Z lokalnego komputera:
scp -r public/build/ użytkownik@serwer-produkcyjny:/ścieżka/do/pnedu/public/

# Przykład:
scp -r public/build/ srv66127@h30.home.pl:/home/srv66127/app/public/
```

**Metoda B - Rsync (jeśli masz dostęp SSH):**
```bash
# Z lokalnego komputera:
rsync -avz public/build/ użytkownik@serwer-produkcyjny:/ścieżka/do/pnedu/public/build/

# Przykład:
rsync -avz public/build/ srv66127@h30.home.pl:/home/srv66127/app/public/build/
```

**Metoda C - Przez FTP/SFTP:**
1. Połącz się z serwerem przez klienta FTP (FileZilla, WinSCP, etc.)
2. Przejdź do katalogu `public/` na serwerze
3. Wgraj cały katalog `build/` z lokalnego `public/build/`

**Krok 3 - Na produkcji: Sprawdź i ustaw uprawnienia:**

**Najpierw sprawdź jaki użytkownik web servera jest używany:**
```bash
# Sprawdź procesy web servera:
ps aux | grep -E 'apache|nginx|httpd' | head -1

# Lub sprawdź właściciela innych plików w public/:
ls -la public/ | head -5

# Typowe użytkowniki:
# - apache (dla Apache)
# - nginx (dla Nginx)
# - www-data (dla niektórych systemów)
# - srv66127 (może być Twój użytkownik)
```

**Następnie ustaw uprawnienia (zamień USER:GROUP na właściwego użytkownika):**
```bash
# Jeśli używasz Apache:
chmod -R 755 public/build
chown -R apache:apache public/build

# Jeśli używasz Nginx:
chmod -R 755 public/build
chown -R nginx:nginx public/build

# Jeśli Twój użytkownik to srv66127:
chmod -R 755 public/build
chown -R srv66127:srv66127 public/build

# Lub jeśli web server działa pod Twoim użytkownikiem:
chmod -R 755 public/build
```

**Sprawdź czy pliki są dostępne:**
```bash
ls -la public/build/
# Powinny być: manifest.json i pliki assets/*.css oraz *.js
```

### 4. Uruchom ponownie cache (opcjonalnie, dla lepszej wydajności)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Sprawdź logi błędów
```bash
tail -f storage/logs/laravel.log
```

## Jeśli nadal występuje błąd 500:

### Sprawdź, czy wszystkie pliki są na miejscu:
```bash
ls -la app/Mail/OrderNotificationMail.php
ls -la resources/views/emails/order-notification.blade.php
```

### Sprawdź, czy nie ma błędów składniowych:
```bash
php artisan route:list | grep orders.summary
php -l app/Mail/OrderNotificationMail.php
php -l app/Http/Controllers/CourseController.php
```

### Sprawdź uprawnienia do plików:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Dodatkowe sprawdzenie:

### Sprawdź, czy pakiet barryvdh/laravel-dompdf jest zainstalowany:
```bash
composer show barryvdh/laravel-dompdf
```

Jeśli nie jest, zainstaluj:
```bash
composer require barryvdh/laravel-dompdf
```
