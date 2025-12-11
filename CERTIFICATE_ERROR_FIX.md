# 🔧 Naprawa błędów generowania certyfikatów

## ❌ Zidentyfikowane problemy

### 1. Brakująca tabela `sessions`
**Błąd:**
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'pnedu.sessions' doesn't exist
```

**Przyczyna:** 
Laravel był skonfigurowany do używania sesji w bazie danych (`SESSION_DRIVER=database`), ale tabela `sessions` nie istniała w bazie `pnedu`.

**Rozwiązanie:**
- Utworzono migrację `2025_11_30_202837_create_sessions_table.php`
- Dodano standardowy schemat tabeli sesji Laravel:
  - `id` (string, primary key)
  - `user_id` (nullable, indexed)
  - `ip_address` (nullable)
  - `user_agent` (nullable)
  - `payload` (longtext)
  - `last_activity` (integer, indexed)
- Uruchomiono migrację: `sail artisan migrate`

### 2. Brakująca relacja `certificates()` w modelu `Course`
**Błąd:**
`CertificateNumberGenerator` próbował użyć metody `certificates()` na modelu `Course`, ale relacja nie była zdefiniowana.

**Rozwiązanie:**
- Dodano relację `certificates()` do modelu `Course`:
```php
public function certificates()
{
    return $this->hasMany(Certificate::class, 'course_id');
}
```
- Dodano import modelu `Certificate` w `Course.php`

### 3. Uprawnienia do bazy danych `pneadm`
**Błąd:**
```
Access denied for user 'sail'@'%' to database 'admpnedu'
```

**Rozwiązanie:**
- Przyznano pełne uprawnienia użytkownikowi `sail` do bazy `pneadm`:
```sql
GRANT ALL PRIVILEGES ON pneadm.* TO 'sail'@'%';
FLUSH PRIVILEGES;
```

## ✅ Wykonane kroki

1. ✅ Utworzono i uruchomiono migrację tabeli `sessions`
2. ✅ Dodano relację `certificates()` do modelu `Course`
3. ✅ Przyznano uprawnienia do bazy `pneadm` dla użytkownika `sail`
4. ✅ Wyczyszczono cache konfiguracji, routingu i widoków

## 🧪 Testowanie

Aby przetestować generowanie certyfikatów:

1. Upewnij się, że kontenery Docker są uruchomione:
```bash
cd /home/hostnet/WEB-APP/pnedu
sail up -d
```

2. Sprawdź połączenie z bazą `pneadm`:
```bash
sail artisan tinker
```
W Tinker:
```php
DB::connection('pneadm')->select('SELECT 1');
\App\Models\Course::count();
```

3. Spróbuj wygenerować certyfikat przez interfejs użytkownika lub bezpośrednio:
```php
$controller = new \App\Http\Controllers\CertificateController();
$controller->generate($courseId);
```

## 📝 Uwagi

- Tabela `sessions` jest teraz w bazie `pnedu` (domyślna baza aplikacji)
- Certyfikaty i kursy są w bazie `pneadm` (drugie połączenie)
- Wszystkie modele (`Course`, `Certificate`, `Participant`) używają połączenia `pneadm`
- Pakiet `pne-certificate-generator` poprawnie obsługuje przekazywanie nazwy połączenia przez parametr `connection`

## 🔍 Jeśli nadal występują błędy

1. Sprawdź logi Laravel:
```bash
sail artisan pail
# lub
tail -f storage/logs/laravel.log
```

2. Sprawdź konfigurację sesji w `.env`:
```env
SESSION_DRIVER=database
SESSION_CONNECTION=  # puste = domyślne połączenie (pnedu)
```

3. Sprawdź czy wszystkie migracje zostały uruchomione:
```bash
sail artisan migrate:status
```

4. Sprawdź uprawnienia użytkownika `sail`:
```bash
sail mysql -e "SHOW GRANTS FOR 'sail'@'%';"
```








