# 🔧 Naprawa błędu generowania certyfikatów - brak logo

## ❌ Problem

Błąd w logach:
```
file_get_contents(/var/www/html/storage/app/public/certificates/logos/1759876024_logo-pne-czarne.png): 
Failed to open stream: No such file or directory
```

**Przyczyna:** Szablony certyfikatów w pakiecie `pne-certificate-generator` próbowały załadować logo, które nie istniało w projekcie `pnedu`.

## ✅ Rozwiązanie

### 1. Skopiowano logo z `pneadm-bootstrap`
- Utworzono katalog: `storage/app/public/certificates/logos/`
- Skopiowano plik: `1759876024_logo-pne-czarne.png` z `pneadm-bootstrap` do `pnedu`

### 2. Poprawiono szablony w pakiecie
Zaktualizowano wszystkie szablony, aby obsługiwały brak logo:

**Zmienione pliki:**
- `pne-certificate-generator/resources/views/certificates/default.blade.php`
- `pne-certificate-generator/resources/views/certificates/landscape.blade.php`
- `pne-certificate-generator/resources/views/certificates/minimal.blade.php`

**Zmiany:**
- Dodano sprawdzenie `file_exists()` przed załadowaniem logo
- Logo wyświetla się tylko jeśli plik istnieje
- Brak logo nie powoduje błędu - certyfikat generuje się bez logo

**Kod przed:**
```php
$logoFile = storage_path('app/public/' . $logoPath);
$logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
<img src="{{ $logoSrc }}" alt="Logo">
```

**Kod po:**
```php
$logoFile = storage_path('app/public/' . $logoPath);
$logoSrc = null;

if ($isPdfMode ?? false) {
    if (file_exists($logoFile)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
    }
} else {
    if (file_exists($logoFile)) {
        $logoSrc = asset('storage/' . $logoPath);
    }
}

@if($logoSrc)
    <img src="{{ $logoSrc }}" alt="Logo" style="max-width: 120px; height: auto;">
@endif
```

## 📁 Lokalizacja plików

**Logo:**
- `pnedu/storage/app/public/certificates/logos/1759876024_logo-pne-czarne.png`

**Szablony (w pakiecie):**
- `pne-certificate-generator/resources/views/certificates/default.blade.php`
- `pne-certificate-generator/resources/views/certificates/landscape.blade.php`
- `pne-certificate-generator/resources/views/certificates/minimal.blade.php`

## ✅ Status

- ✅ Logo skopiowane do `pnedu`
- ✅ Szablony zaktualizowane (obsługa braku logo)
- ✅ Cache wyczyszczony
- ✅ Pakiet `pne-certificate-generator` zintegrowany w `pnedu`

## 🧪 Testowanie

Spróbuj teraz wygenerować certyfikat - powinno działać bez błędów.

Jeśli nadal występują problemy, sprawdź:
1. Czy plik logo istnieje: `ls -la storage/app/public/certificates/logos/`
2. Czy uprawnienia są poprawne: `chmod -R 775 storage/app/public/certificates/`
3. Logi: `sail artisan pail` lub `tail -f storage/logs/laravel.log`








