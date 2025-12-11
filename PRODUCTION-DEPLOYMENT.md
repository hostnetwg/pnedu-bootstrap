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

**Opcja C:** Jeśli nie masz npm/node na produkcji, zbuduj lokalnie i wgraj katalog `public/build`:
```bash
# Lokalnie (na swoim komputerze):
cd /home/hostnet/WEB-APP/pnedu
./vendor/bin/sail npm run build

# Następnie wgraj cały katalog public/build na produkcję
# (użyj scp, rsync lub innego narzędzia)
```

**Sprawdź czy pliki zostały utworzone:**
```bash
ls -la public/build/
# Powinny być: manifest.json i pliki assets/*.css oraz *.js
```

**Ustaw uprawnienia do katalogu build:**
```bash
chmod -R 755 public/build
chown -R www-data:www-data public/build
# lub jeśli używasz innego użytkownika web servera:
# chown -R apache:apache public/build
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
