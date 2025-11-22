# Instrukcja wdrożenia na produkcji

## Po wykonaniu git pull na produkcji wykonaj:

### 1. Zaktualizuj autoloader Composer
```bash
composer dump-autoload
```

### 2. Wyczyść cache Laravel
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Uruchom ponownie cache (opcjonalnie, dla lepszej wydajności)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Sprawdź logi błędów
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
