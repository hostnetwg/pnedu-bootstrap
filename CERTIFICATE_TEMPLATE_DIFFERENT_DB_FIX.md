# ✅ Problem z różnymi danymi szablonu między pneadm-bootstrap a pnedu

## 🐛 Problem
Na `pnedu.pl` generowane certyfikaty używają starego nagłówka "ZAŚWIADCZENIE" zamiast nowego "ZAŚWIADCZENIE WWW", który jest widoczny na `adm.pnedu.pl`.

## 🔍 Analiza
- **pneadm-bootstrap** (adm.pnedu.pl): widzi nagłówek "ZAŚWIADCZENIE WWW" ✅
- **pnedu** (pnedu.pl): widzi nagłówek "ZAŚWIADCZENIE" ❌

Oba projekty powinny łączyć się z tą samą bazą danych `pneadm`, ale widzą różne dane.

## 🔍 Możliwe przyczyny
1. **Różne bazy danych**: `pneadm-bootstrap` może łączyć się z inną bazą niż `pnedu`
2. **Cache**: `pneadm-bootstrap` może mieć zcache'owane dane
3. **Różne połączenia**: `pneadm-bootstrap` używa `mysql` (domyślne), `pnedu` używa `pneadm`

## ✅ Rozwiązanie
Sprawdź:
1. Czy oba projekty łączą się z tą samą bazą danych
2. Czy cache został wyczyszczony w obu projektach
3. Czy dane w bazie są aktualne

## 📝 Weryfikacja
```bash
# W pneadm-bootstrap
sail artisan cache:clear
sail artisan config:clear
sail artisan view:clear

# W pnedu
sail artisan cache:clear
sail artisan config:clear
sail artisan view:clear
```

## 🔍 Sprawdzenie bazy danych
```php
// W pneadm-bootstrap
$db = DB::connection('mysql');
$template = $db->table('certificate_templates')->where('id', 5)->first();
echo $db->getDatabaseName(); // Powinno być: pneadm

// W pnedu
$db = DB::connection('pneadm');
$template = $db->table('certificate_templates')->where('id', 5)->first();
echo $db->getDatabaseName(); // Powinno być: pneadm
```

Jeśli oba pokazują tę samą bazę, ale różne dane, sprawdź czy:
- Cache został wyczyszczony
- Dane w bazie są aktualne
- Nie ma problemów z replikacją/synchronizacją bazy danych

