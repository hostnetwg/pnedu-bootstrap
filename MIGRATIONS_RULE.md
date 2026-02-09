# 🗄️ Reguła lokalizacji migracji - WAŻNE!

## ⚠️ ZASADA LOKALIZACJI MIGRACJI

### Migracje do bazy `pneadm` → w projekcie `pneadm-bootstrap`
- **Lokalizacja**: `pneadm-bootstrap/database/migrations/`
- **Przykłady tabel**: `form_orders`, `online_payment_orders`, `payment_webhook_logs`, `courses`, `participants`, `certificates`, etc.

### Migracje do bazy `pnedu` → w projekcie `pnedu`
- **Lokalizacja**: `pnedu/database/migrations/`
- **Przykłady tabel**: `users`, `password_reset_tokens`, `sessions`, `cache`, etc.

### Migracje do bazy `certgen` → w projekcie `pneadm-bootstrap`
- **Lokalizacja**: `pneadm-bootstrap/database/migrations/`
- **Przykłady tabel**: stare zamówienia, dane historyczne

## 🔍 Jak sprawdzić do której bazy należy tabela?

1. **Sprawdź w modelu Eloquent**:
   ```php
   // Jeśli model ma:
   protected $connection = 'pneadm';
   // → migracja w pneadm-bootstrap
   
   // Jeśli model nie ma $connection lub ma:
   protected $connection = 'mysql'; // w projekcie pnedu
   // → migracja w pnedu
   ```

2. **Sprawdź w migracji**:
   ```php
   // Jeśli migracja używa:
   Schema::connection('pneadm')->create(...);
   // → migracja w pneadm-bootstrap
   
   // Jeśli migracja używa:
   Schema::create(...); // bez connection w projekcie pnedu
   // → migracja w pnedu
   ```

3. **Sprawdź w `config/database.php`** jakie są dostępne połączenia

## ✅ Przykłady poprawnych lokalizacji

```php
// ✅ DOBRZE - Migracja w pneadm-bootstrap dla tabeli w bazie pneadm
// Plik: pneadm-bootstrap/database/migrations/2026_02_09_000001_create_payment_webhook_logs_table.php
Schema::create('payment_webhook_logs', ...); // Domyślnie baza pneadm w pneadm-bootstrap

// ✅ DOBRZE - Migracja w pnedu dla tabeli w bazie pnedu
// Plik: pnedu/database/migrations/2024_01_01_000001_create_users_table.php
Schema::create('users', ...); // Domyślnie baza pnedu w pnedu
```

## 📝 Zasada ogólna

**Migracja zawsze w projekcie, który odpowiada za bazę danych, do której należy tabela!**

Więcej informacji o strukturach baz danych: [pneadm-bootstrap/SHARED_DATABASES_SETUP.md](../pneadm-bootstrap/SHARED_DATABASES_SETUP.md)
