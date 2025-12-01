# ✅ System zaświadczeń w pnedu.pl - Gotowe!

## 🎯 Co zostało zrobione

### 1. **Pakiet zainstalowany**
- ✅ `pne/certificate-generator` zainstalowany przez Composer
- ✅ ServiceProvider automatycznie zarejestrowany
- ✅ Volume dodany do `docker-compose.yml` dla dostępu do pakietu

### 2. **Konfiguracja**
- ✅ `composer.json` - dodano repository z ścieżką `/var/www/pne-certificate-generator`
- ✅ `docker-compose.yml` - dodano volume dla pakietu
- ✅ Routing - `GET /courses/{course}/certificate` z middleware `auth`, `verified`

### 3. **Kontroler**
- ✅ `CertificateController::generate()` - używa pakietu do generowania PDF
- ✅ Sprawdza czy użytkownik jest zalogowany
- ✅ Znajduje uczestnika po email i course_id
- ✅ Tworzy certyfikat jeśli nie istnieje
- ✅ Generuje PDF używając pakietu z połączeniem `pneadm`
- ✅ Zapisuje do storage i zwraca do pobrania

### 4. **Modele**
- ✅ `Certificate` - używa połączenia `pneadm`
- ✅ `Participant` - używa połączenia `pneadm`
- ✅ `Course` - używa połączenia `pneadm`

### 5. **Widok**
- ✅ `free.blade.php` - link do `route('certificates.generate', $course->id)`
- ✅ Ikona zaświadczenia wyświetla się tylko dla uczestników

## 🔄 Jak to działa

### Flow:

```
Użytkownik klika ikonę zaświadczenia
    ↓
Route: /courses/{course}/certificate
    ↓
CertificateController::generate()
    ↓
1. Sprawdza czy zalogowany (auth middleware)
2. Znajduje Participant po email + course_id (baza pneadm)
3. Sprawdza czy Certificate istnieje
4. Jeśli nie - tworzy z numerem (CertificateNumberGenerator)
5. Generuje PDF (CertificateGeneratorService z connection='pneadm')
6. Zapisuje do storage/public/certificates/{courseId}/{certificateNumber}.pdf
7. Aktualizuje file_path w bazie
8. Zwraca PDF do pobrania
```

## 📋 Testowanie

### Krok 1: Sprawdź routing
```bash
sail artisan route:list | grep certificate
```

Powinno pokazać:
```
GET|HEAD  courses/{course}/certificate ................ certificates.generate
```

### Krok 2: Sprawdź uczestnictwo
```bash
sail artisan tinker
```

```php
$user = auth()->user();
\App\Models\Participant::where('email', $user->email)->get();
```

### Krok 3: Przetestuj w przeglądarce

1. Zaloguj się na http://localhost:8081
2. Przejdź do: http://localhost:8081/bezplatne/tik-w-pracy-nauczyciela
3. Kliknij ikonę zaświadczenia przy kursie, w którym jesteś uczestnikiem
4. PDF powinien się wygenerować i pobrać

## ⚠️ Wymagania

1. ✅ Użytkownik musi być zalogowany
2. ✅ Użytkownik musi być uczestnikiem kursu (sprawdzane po email w tabeli `participants`)
3. ✅ Baza `pneadm` musi być dostępna
4. ✅ Pakiet `pne-certificate-generator` musi być zainstalowany
5. ✅ Kurs musi mieć przypisany szablon certyfikatu (`certificate_template_id`)

## 🐛 Troubleshooting

### Problem: "Package not found"
```bash
# Sprawdź czy volume jest zamontowany
sail shell -c "ls -la /var/www/pne-certificate-generator"

# Sprawdź composer.json
cat composer.json | grep pne-certificate-generator
```

### Problem: "Certificate not found for participant"
- Sprawdź czy uczestnik istnieje: 
```bash
sail mysql pneadm -e "SELECT * FROM participants WHERE email = 'twoj@email.pl' AND course_id = X;"
```
- Sprawdź czy email użytkownika zgadza się z emailem w tabeli `participants`

### Problem: "Template not found"
- Sprawdź czy kurs ma przypisany szablon:
```bash
sail mysql pneadm -e "SELECT id, title, certificate_template_id FROM courses WHERE id = X;"
```

### Problem: "Database connection error"
- Sprawdź połączenie `pneadm` w `config/database.php`
- Sprawdź czy baza `pneadm` istnieje: `sail mysql -e "SHOW DATABASES;"`

### Problem: "View not found"
- Sprawdź czy ServiceProvider jest zarejestrowany:
```bash
sail artisan package:discover
```

- Opcjonalnie opublikuj widoki:
```bash
sail artisan vendor:publish --tag=pne-certificate-generator-views
```

## 📝 Pliki zmodyfikowane

1. `composer.json` - dodano repository i wymaganie pakietu
2. `docker-compose.yml` - dodano volume dla pakietu
3. `app/Http/Controllers/CertificateController.php` - używa pakietu
4. `routes/web.php` - routing już był dodany
5. `resources/views/courses/free.blade.php` - link już był dodany

## ✅ Status

- [x] Pakiet zainstalowany
- [x] Volume dodany do docker-compose.yml
- [x] Composer.json zaktualizowany
- [x] CertificateController używa pakietu
- [x] Routing działa
- [x] Widok z linkiem działa
- [ ] Przetestowane w przeglądarce

---

**Data:** 2024-11-30  
**Status:** ✅ Gotowe do testowania

