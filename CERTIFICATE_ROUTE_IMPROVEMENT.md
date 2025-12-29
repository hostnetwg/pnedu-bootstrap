# 🔧 Ulepszenie routingu generowania certyfikatów

## 📋 Problem

Link do generowania certyfikatu w projekcie `pnedu` używał tylko `course_id`:
```
http://localhost:8081/courses/414/certificate
```

Podczas gdy w projekcie `pneadm-bootstrap` link używa `participant_id`, co jest bardziej precyzyjne:
```
http://localhost:8083/certificates/generate/263864
```

## ✅ Rozwiązanie

### 1. Dodano alternatywny route z `participant_id`

**Nowy route:**
```php
Route::get('/certificates/generate/{participant}', [CertificateController::class, 'generateByParticipant'])
    ->name('certificates.generate.by-participant');
```

**Stary route (zachowany dla kompatybilności wstecznej):**
```php
Route::get('/courses/{course}/certificate', [CertificateController::class, 'generate'])
    ->name('certificates.generate');
```

### 2. Poprawiono wyszukiwanie uczestnika

**Zmiany w `CertificateController::generate()`:**
- Dodano case-insensitive wyszukiwanie uczestnika (LOWER, TRIM)
- Dodano szczegółowe logowanie błędów
- Dodano lepsze komunikaty błędów dla użytkownika

**Nowa metoda `generateByParticipant()`:**
- Przyjmuje bezpośrednio `participant_id`
- Sprawdza czy użytkownik ma dostęp do tego uczestnika (po emailu)
- Działa podobnie jak w `pneadm-bootstrap`

### 3. Zaktualizowano `CourseController`

**Zmiany:**
- Dodano mapowanie `course_id => participant_id` (`$participantIdsByCourse`)
- Wyszukiwanie uczestników używa teraz case-insensitive (LOWER, TRIM)
- Wszystkie metody zwracające widok `courses.free` przekazują `$participantIdsByCourse`

**Zaktualizowane metody:**
- `tik()` - TIK w pracy NAUCZYCIELA
- `administrator()` - Szkolny ADMINISTRATOR Office 365
- `akademiaRodzica()` - Akademia Rodzica
- `akademiaDyrektora()` - Akademia Dyrektora

### 4. Zaktualizowano widok `free.blade.php`

**Zmiany:**
- Link do certyfikatu używa teraz `participant_id` jeśli dostępne
- Fallback do `course_id` jeśli `participant_id` nie jest dostępne
- Kod:
```php
@php
    $participantId = $participantIdsByCourse[$course->id] ?? null;
    $certificateRoute = $participantId 
        ? route('certificates.generate.by-participant', $participantId)
        : route('certificates.generate', $course->id);
@endphp
<a href="{{ $certificateRoute }}" ...>
```

## 🎯 Korzyści

1. **Bardziej precyzyjne generowanie** - użycie `participant_id` eliminuje potrzebę wyszukiwania uczestnika po emailu
2. **Lepsze logowanie** - szczegółowe logi pomagają w debugowaniu problemów
3. **Case-insensitive wyszukiwanie** - eliminuje problemy z różnicami w wielkości liter w emailach
4. **Kompatybilność wsteczna** - stary route nadal działa
5. **Spójność z `pneadm-bootstrap`** - oba projekty używają teraz podobnego podejścia

## 📝 Przykłady użycia

### Route z `participant_id` (preferowane):
```
http://localhost:8081/certificates/generate/12345
```

### Route z `course_id` (fallback):
```
http://localhost:8081/courses/414/certificate
```

## 🔍 Debugowanie

Jeśli wystąpią problemy, sprawdź logi:
```bash
sail artisan pail
# lub
tail -f storage/logs/laravel.log
```

Logi zawierają:
- Email użytkownika
- ID kursu
- ID uczestnika (jeśli znaleziony)
- Lista istniejących uczestników (jeśli nie znaleziony)
- Szczegóły błędów

## ✅ Status

- ✅ Dodano route z `participant_id`
- ✅ Poprawiono wyszukiwanie uczestnika (case-insensitive)
- ✅ Dodano szczegółowe logowanie
- ✅ Zaktualizowano `CourseController` (wszystkie metody)
- ✅ Zaktualizowano widok `free.blade.php`
- ✅ Zachowano kompatybilność wsteczną
















