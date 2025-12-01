# 🎓 Konfiguracja systemu generowania zaświadczeń w pnedu.pl

## ✅ Co zostało zrobione

1. ✅ **Dodano pakiet `pne-certificate-generator`** do `composer.json`
2. ✅ **Utworzono modele:**
   - `Certificate.php` - połączenie z bazą `pneadm`
   - `Participant.php` - połączenie z bazą `pneadm`
3. ✅ **Utworzono `CertificateController.php`** - generowanie PDF zaświadczeń
4. ✅ **Dodano routing:** `/courses/{course}/certificate`
5. ✅ **Zaktualizowano widok** `free.blade.php` - link do generowania certyfikatu

## 📋 Co należy zrobić teraz

### Krok 1: Zainstaluj pakiet

```bash
cd /home/hostnet/WEB-APP/pnedu
sail composer require pne/certificate-generator
```

### Krok 2: Sprawdź czy ServiceProvider jest zarejestrowany

Pakiet powinien automatycznie się zarejestrować (przez `composer.json` w pakiecie). Jeśli nie, dodaj do `config/app.php`:

```php
'providers' => [
    // ...
    Pne\CertificateGenerator\CertificateGeneratorServiceProvider::class,
],
```

### Krok 3: Publikuj widoki (opcjonalnie)

Jeśli chcesz zmodyfikować szablony w projekcie:

```bash
sail artisan vendor:publish --tag=pne-certificate-generator-views
```

### Krok 4: Wyczyść cache

```bash
sail artisan config:clear
sail artisan cache:clear
sail artisan route:clear
```

### Krok 5: Sprawdź czy działa

1. **Zaloguj się** na http://localhost:8081
2. **Przejdź do:** http://localhost:8081/bezplatne/tik-w-pracy-nauczyciela
3. **Kliknij ikonę zaświadczenia** przy kursie, w którym jesteś uczestnikiem
4. **Powinno wygenerować się PDF** i automatycznie pobrać

## 🔍 Jak to działa

### Flow generowania certyfikatu:

1. **Użytkownik klika ikonę** na stronie kursów
2. **Route:** `/courses/{course}/certificate` → `CertificateController::generate()`
3. **Kontroler:**
   - Sprawdza czy użytkownik jest zalogowany
   - Znajduje uczestnika po `email` i `course_id` w bazie `pneadm`
   - Sprawdza czy certyfikat już istnieje
   - Jeśli nie istnieje, generuje numer certyfikatu
   - Używa `CertificateGeneratorService` z pakietu do generowania PDF
   - Zapisuje PDF do storage
   - Zwraca plik do pobrania

### Modele:

- **Certificate** - używa połączenia `pneadm`, tabela `certificates`
- **Participant** - używa połączenia `pneadm`, tabela `participants`
- **Course** - używa połączenia `pneadm`, tabela `courses`

### Routing:

```php
Route::get('/courses/{course}/certificate', [CertificateController::class, 'generate'])
    ->middleware(['auth', 'verified'])
    ->name('certificates.generate');
```

## ⚠️ Wymagania

1. **Użytkownik musi być zalogowany** - middleware `auth`
2. **Użytkownik musi być uczestnikiem kursu** - sprawdzane po `email` w tabeli `participants`
3. **Baza `pneadm` musi być dostępna** - połączenie `pneadm` w `config/database.php`
4. **Pakiet `pne-certificate-generator` musi być zainstalowany**

## 🐛 Troubleshooting

### Problem: "Package not found"
```bash
# Sprawdź czy pakiet jest w composer.json
cat composer.json | grep pne-certificate-generator

# Jeśli nie ma, dodaj ręcznie i uruchom:
sail composer require pne/certificate-generator
```

### Problem: "Class not found"
```bash
# Wyczyść cache autoloadera
sail composer dump-autoload
sail artisan config:clear
```

### Problem: "Certificate not found for participant"
- Sprawdź czy uczestnik istnieje w bazie `pneadm`, tabela `participants`
- Sprawdź czy email użytkownika zgadza się z emailem w tabeli `participants`
- Sprawdź czy `course_id` jest poprawne

### Problem: "Database connection error"
- Sprawdź połączenie `pneadm` w `config/database.php`
- Sprawdź czy baza `pneadm` istnieje w MySQL
- Sprawdź uprawnienia użytkownika `sail` do bazy `pneadm`

### Problem: "Template not found"
- Sprawdź czy szablony są w pakiecie: `pne-certificate-generator/resources/views/certificates/`
- Sprawdź czy ServiceProvider jest zarejestrowany
- Uruchom: `sail artisan vendor:publish --tag=pne-certificate-generator-views`

## 📝 Testowanie

### Test 1: Sprawdź czy pakiet jest zainstalowany
```bash
sail composer show pne/certificate-generator
```

### Test 2: Sprawdź routing
```bash
sail artisan route:list | grep certificate
```

Powinno pokazać:
```
GET|HEAD  courses/{course}/certificate ................ certificates.generate
```

### Test 3: Sprawdź modele w Tinker
```bash
sail artisan tinker
```

```php
// Sprawdź połączenie
\App\Models\Participant::count();

// Sprawdź uczestnika
$user = auth()->user();
\App\Models\Participant::where('email', $user->email)->first();
```

### Test 4: Sprawdź czy certyfikat można wygenerować
1. Zaloguj się
2. Przejdź do strony kursów
3. Kliknij ikonę zaświadczenia
4. Sprawdź logi: `sail artisan pail`

## ✅ Checklist

- [ ] Pakiet zainstalowany (`sail composer require pne/certificate-generator`)
- [ ] Cache wyczyszczony
- [ ] Routing działa (`sail artisan route:list | grep certificate`)
- [ ] Modele działają (test w Tinker)
- [ ] Użytkownik może pobrać certyfikat (test w przeglądarce)
- [ ] PDF generuje się poprawnie
- [ ] Certyfikat zapisuje się w bazie `pneadm`

---

**Data konfiguracji:** $(date)  
**Status:** ✅ Gotowe do testowania

