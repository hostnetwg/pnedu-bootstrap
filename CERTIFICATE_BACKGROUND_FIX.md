# ✅ Naprawa problemu z grafiką tła w certyfikatach na pnedu.pl

## 🐛 Problem
Na `pnedu.pl` generowane certyfikaty nie wyświetlały grafiki tła, mimo że:
- ✅ Logo wyświetlało się poprawnie
- ✅ Marginesy były prawidłowe (z szablonu)
- ✅ `show_background` było ustawione na `1`

## 🔍 Przyczyna
W bazie danych (tabela `certificate_templates`, rekord ID=5) była zapisana **stara ścieżka** do grafiki tła:
```
certificate-backgrounds/q3qIczUxD7ZTBvnfLUOFC1nSU1gWFmuUn0k21Y5T.png
```

Ale:
- Plik `q3qIczUxD7ZTBvnfLUOFC1nSU1gWFmuUn0k21Y5T.png` **nie istnieje** w pakiecie
- W pakiecie są dostępne pliki:
  - `1764537260_1764532105-gilosz-a4-pionowy.png`
  - `1764537269_1764532099-gilosz-a4-poziomy.png`

Szablon normalizował ścieżkę (`certificate-backgrounds/` → `certificates/backgrounds/`), ale plik nadal nie istniał, więc tło się nie wyświetlało.

## ✅ Rozwiązanie
Zaktualizowano ścieżkę tła w bazie danych na poprawną:
```sql
UPDATE certificate_templates 
SET config = JSON_SET(
    config, 
    '$.settings.background_image', 
    'certificates/backgrounds/1764537260_1764532105-gilosz-a4-pionowy.png'
) 
WHERE id = 5;
```

Lub przez Tinker:
```php
$db = DB::connection('pneadm');
$template = $db->table('certificate_templates')->where('id', 5)->first();
$config = json_decode($template->config, true) ?? [];
$config['settings']['background_image'] = 'certificates/backgrounds/1764537260_1764532105-gilosz-a4-pionowy.png';
$db->table('certificate_templates')->where('id', 5)->update(['config' => json_encode($config)]);
```

## 🔍 Weryfikacja
Przed naprawą:
- Background image: `certificate-backgrounds/q3qIczUxD7ZTBvnfLUOFC1nSU1gWFmuUn0k21Y5T.png`
- File exists: NO ❌

Po naprawie:
- Background image: `certificates/backgrounds/1764537260_1764532105-gilosz-a4-pionowy.png`
- File exists: YES ✅

## ✅ Status
- ✅ Ścieżka tła zaktualizowana w bazie danych
- ✅ Plik tła istnieje w pakiecie
- ✅ Szablon normalizuje ścieżkę poprawnie
- ✅ Tło powinno się teraz wyświetlać na certyfikatach

## 📝 Uwagi
- Jeśli tło nadal nie wyświetla się, sprawdź czy:
  1. `show_background` jest ustawione na `1` w ustawieniach szablonu
  2. Plik tła istnieje w pakiecie: `/var/www/pne-certificate-generator/storage/certificates/backgrounds/`
  3. Symlink działa: `pnedu/public/storage/certificates/backgrounds -> /var/www/pne-certificate-generator/storage/certificates/backgrounds`








