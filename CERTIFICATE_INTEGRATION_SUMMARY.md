# ✅ Integracja systemu zaświadczeń w pnedu.pl - Podsumowanie

## 🎯 Cel

Umożliwienie użytkownikom pobierania zaświadczeń PDF po kliknięciu w ikonę na stronie bezpłatnych kursów.

## ✅ Co zostało zrobione

### 1. **Dodano pakiet `pne-certificate-generator`**
- ✅ Zaktualizowano `composer.json` - dodano path repository
- ✅ Dodano zależność `pne/certificate-generator`

### 2. **Utworzono modele** (połączenie z bazą `pneadm`)
- ✅ `Certificate.php` - model certyfikatu
- ✅ `Participant.php` - model uczestnika

### 3. **Utworzono kontroler**
- ✅ `CertificateController.php` - generowanie PDF zaświadczeń
  - Sprawdza czy użytkownik jest zalogowany
  - Znajduje uczestnika po email i course_id
  - Tworzy certyfikat jeśli nie istnieje
  - Generuje PDF używając pakietu
  - Zapisuje do storage i zwraca do pobrania

### 4. **Dodano routing**
- ✅ `GET /courses/{course}/certificate` - generowanie certyfikatu
- ✅ Middleware: `auth`, `verified`

### 5. **Zaktualizowano widok**
- ✅ `free.blade.php` - link do `route('certificates.generate', $course->id)`

### 6. **Rozszerzono pakiet**
- ✅ `CertificateGeneratorService` - dodano parametr `connection` w opcjach
- ✅ `getCertificateData()` - obsługa różnych połączeń baz danych

## 📋 Instrukcja instalacji

### Krok 1: Zainstaluj pakiet

```bash
cd /home/hostnet/WEB-APP/pnedu
sail composer require pne/certificate-generator
```

### Krok 2: Wyczyść cache

```bash
sail artisan config:clear
sail artisan cache:clear
sail artisan route:clear
```

### Krok 3: Sprawdź routing

```bash
sail artisan route:list | grep certificate
```

Powinno pokazać:
```
GET|HEAD  courses/{course}/certificate ................ certificates.generate
```

### Krok 4: Przetestuj

1. Zaloguj się na http://localhost:8081
2. Przejdź do: http://localhost:8081/bezplatne/tik-w-pracy-nauczyciela
3. Kliknij ikonę zaświadczenia przy kursie, w którym jesteś uczestnikiem
4. PDF powinien się wygenerować i pobrać

## 🔄 Jak to działa

### Flow:

```
Użytkownik klika ikonę
    ↓
Route: /courses/{course}/certificate
    ↓
CertificateController::generate()
    ↓
1. Sprawdza czy zalogowany
2. Znajduje Participant po email + course_id (baza pneadm)
3. Sprawdza czy Certificate istnieje
4. Jeśli nie - tworzy z numerem (CertificateNumberGenerator)
5. Generuje PDF (CertificateGeneratorService)
6. Zapisuje do storage
7. Zwraca PDF do pobrania
```

### Bazy danych:

- **pnedu** - użytkownicy (User model)
- **pneadm** - kursy, uczestnicy, certyfikaty (Course, Participant, Certificate)

## ⚠️ Wymagania

1. ✅ Użytkownik musi być zalogowany
2. ✅ Użytkownik musi być uczestnikiem kursu (sprawdzane po email w tabeli `participants`)
3. ✅ Baza `pneadm` musi być dostępna
4. ✅ Pakiet `pne-certificate-generator` musi być zainstalowany

## 🐛 Troubleshooting

### Problem: "Package not found"
```bash
# Sprawdź czy path repository jest poprawne
cat composer.json | grep pne-certificate-generator

# Zainstaluj pakiet
sail composer require pne/certificate-generator
```

### Problem: "Certificate not found for participant"
- Sprawdź czy uczestnik istnieje: `sail mysql pneadm -e "SELECT * FROM participants WHERE email = 'twoj@email.pl' AND course_id = X;"`
- Sprawdź czy email użytkownika zgadza się z emailem w tabeli `participants`

### Problem: "Template not found"
- Sprawdź czy ServiceProvider jest zarejestrowany
- Uruchom: `sail artisan vendor:publish --tag=pne-certificate-generator-views`

### Problem: "Database connection error"
- Sprawdź połączenie `pneadm` w `config/database.php`
- Sprawdź czy baza `pneadm` istnieje: `sail mysql -e "SHOW DATABASES;"`

## 📝 Testowanie

### Test 1: Sprawdź uczestnictwo
```bash
sail artisan tinker
```

```php
$user = auth()->user();
\App\Models\Participant::where('email', $user->email)->get();
```

### Test 2: Sprawdź routing
```bash
sail artisan route:list | grep certificate
```

### Test 3: Sprawdź logi
```bash
sail artisan pail
```

## ✅ Status

- [x] Pakiet dodany do composer.json
- [x] Modele utworzone
- [x] Kontroler utworzony
- [x] Routing dodany
- [x] Widok zaktualizowany
- [ ] Pakiet zainstalowany (`sail composer require`)
- [ ] Cache wyczyszczony
- [ ] Przetestowane w przeglądarce

---

**Data:** $(date)  
**Status:** ✅ Gotowe do instalacji i testowania

