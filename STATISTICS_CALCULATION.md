# 📊 Jak obliczane są wskaźniki statystyk na stronie głównej

## ⏱️ Interwał czasowy

### Cache Laravel
- **Interwał odświeżania**: **1 godzina (3600 sekund)**
- **Mechanizm**: Cache Laravel (`Cache::remember()`)
- **Lokalizacja**: `app/Services/StatisticsService.php` - stała `CACHE_TTL = 3600`

### Jak to działa:
1. **Przy pierwszym otwarciu strony** - statystyki są obliczane z bazy danych i zapisywane w cache
2. **Przy kolejnych otwarciach** (w ciągu 1 godziny) - statystyki są pobierane z cache (bardzo szybko)
3. **Po 1 godzinie** - cache wygasa, przy następnym otwarciu strony statystyki są ponownie obliczane

### Ręczne odświeżanie:
```bash
sail artisan statistics:refresh
```

---

## 📈 Szczegóły obliczeń każdego wskaźnika

### 1. **Przeszkolonych nauczycieli**

**Metoda**: `getTrainedTeachersCount()`

**Logika**:
- Liczy **unikalnych uczestników** z tabeli `participants` w bazie `pneadm`
- **Dwa sposoby liczenia**:
  1. Uczestnicy z emailem: `COUNT(DISTINCT email)` gdzie email IS NOT NULL
  2. Uczestnicy bez emaila: `COUNT(DISTINCT CONCAT(first_name, ' ', last_name))`
- **Suma obu grup** = całkowita liczba przeszkolonych nauczycieli

**Zapytanie SQL**:
```sql
-- Unikalni po emailu
SELECT COUNT(DISTINCT email) FROM participants 
WHERE email IS NOT NULL AND email != ''

-- Unikalni po imię+nazwisko (bez emaila)
SELECT COUNT(DISTINCT CONCAT(first_name, ' ', last_name)) 
FROM participants 
WHERE email IS NULL OR email = ''
```

**Zakres danych**: Wszystkie uczestnicy od początku (bez ograniczeń czasowych)

---

### 2. **Szkoleń rocznie**

**Metoda**: `getCoursesThisYearCount()`

**Logika**:
- Liczy szkolenia z **ostatnich 12 miesięcy** od daty obliczenia
- Używa `now()->subMonths(12)` do określenia zakresu dat
- Liczy wszystkie szkolenia (aktywne i nieaktywne) z datą `start_date >= 12 miesięcy temu`

**Zapytanie SQL**:
```sql
SELECT COUNT(*) FROM courses 
WHERE start_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
AND start_date IS NOT NULL
```

**Zakres danych**: Ostatnie 12 miesięcy (ruchomy zakres - zawsze od teraz wstecz)

**Uwaga**: Wartość zmienia się dynamicznie - każde odświeżenie cache liczy ostatnie 12 miesięcy od nowa

---

### 3. **Średnia ocena**

**Metoda**: `getAverageRating()`

**Logika**:
1. Pobiera wszystkie ankiety (`surveys`) z bazy `pneadm`
2. Dla każdej ankiety:
   - Pobiera pytania typu `rating` z tabeli `survey_questions`
   - Pobiera wszystkie odpowiedzi z tabeli `survey_responses`
   - Dla każdej odpowiedzi dekoduje JSON `response_data`
   - Sumuje wszystkie oceny (wartości numeryczne) z pytań ratingowych
3. Oblicza średnią dla każdej ankiety
4. Oblicza średnią ze wszystkich ankiet (średnia ze średnich)

**Zapytanie SQL** (uproszczone):
```sql
-- Pobiera ankiety
SELECT surveys.id FROM surveys 
JOIN courses ON surveys.course_id = courses.id

-- Dla każdej ankiety:
SELECT * FROM survey_questions 
WHERE survey_id = ? AND question_type = 'rating'

SELECT * FROM survey_responses 
WHERE survey_id = ?

-- Następnie przetwarzanie JSON response_data w PHP
```

**Zakres danych**: Wszystkie ankiety od początku (bez ograniczeń czasowych)

**Format odpowiedzi**: JSON w kolumnie `response_data` z kluczami = tekst pytania, wartości = odpowiedź

---

### 4. **Wskaźnik poleceń (NPS)**

**Metoda**: `getNPS()`

**Logika**:
1. Pobiera wszystkie ankiety z odpowiedziami (używając modeli Eloquent)
2. Dla każdej odpowiedzi:
   - Sprawdza czy pytanie pasuje do wzorców NPS (regex):
     - `/czy.*poleci.*szkolenie.*innym/i`
     - `/poleci.*szkolenie.*innym/i`
     - `/poleci.*innym.*osobom/i`
     - `/czy.*poleci.*innym/i`
     - `/poleci.*innym/i`
   - Jeśli pasuje i odpowiedź jest numeryczna (1-5), dodaje do listy
3. Klasyfikuje odpowiedzi:
   - **Promoters**: 4-5
   - **Detractors**: 1-2
   - **Passives**: 3
4. Oblicza NPS: `(promoters% - detractors%)`

**Formuła NPS**:
```
NPS = (Promoters / Total) * 100 - (Detractors / Total) * 100
```

**Zakres wartości**: -100 do +100 (wyświetlane z symbolem %)

**Zakres danych**: Wszystkie ankiety od początku (bez ograniczeń czasowych)

---

## 🔄 Proces obliczania

### Krok po kroku:

1. **Wywołanie**: `HomeController::index()` → `StatisticsService::getStatistics()`

2. **Sprawdzenie cache**:
   ```php
   Cache::remember('homepage_statistics', 3600, function() {
       return $this->calculateStatistics();
   });
   ```

3. **Jeśli cache istnieje** (mniej niż 1 godzina):
   - Zwraca dane z cache (bez zapytań do bazy)

4. **Jeśli cache wygasł** (więcej niż 1 godzina):
   - Wykonuje wszystkie 4 metody obliczeniowe
   - Zapisuje wyniki w cache na 1 godzinę
   - Zwraca wyniki

---

## 📊 Wydajność

### Czas odpowiedzi:
- **Z cache**: ~10-50ms (pobranie z pamięci)
- **Bez cache**: ~500-2000ms (obliczenia + zapytania do bazy)

### Obciążenie bazy danych:
- **Z cache**: 0 zapytań (dane z pamięci)
- **Bez cache**: ~10-20 zapytań SQL (w zależności od liczby ankiet)

### Pamięć cache:
- **Rozmiar**: ~1-2 KB na zestaw statystyk
- **Typ**: Zależny od konfiguracji Laravel (Redis/File/Database)

---

## 🛠️ Zarządzanie cache

### Wyświetlenie aktualnych statystyk:
```bash
sail artisan statistics:refresh
```

### Wyczyszczenie cache ręcznie:
```bash
sail artisan cache:clear
# lub
sail artisan cache:forget homepage_statistics
```

### Sprawdzenie czasu ostatniej aktualizacji:
Cache przechowuje również timestamp w kluczu `homepage_statistics_timestamp`

---

## 📝 Uwagi techniczne

1. **Baza danych**: Wszystkie zapytania idą do bazy `pneadm` (połączenie `pneadm` w `config/database.php`)

2. **Obsługa błędów**: Każda metoda ma try-catch i loguje błędy do `storage/logs/laravel.log`

3. **Fallback wartości**: W widoku używane są wartości domyślne (`?? 0`, `?? 4.9`) na wypadek braku danych

4. **Formatowanie**: 
   - Liczby całkowite: bez miejsc dziesiętnych
   - Średnia ocena: 1 miejsce po przecinku (4.9)
   - NPS: 1 miejsce po przecinku (96.2%)

---

## 🔍 Debugowanie

### Sprawdzenie logów:
```bash
tail -f storage/logs/laravel.log | grep -i "statistics\|nps\|rating"
```

### Testowanie bezpośrednio:
```bash
sail artisan tinker
$service = new \App\Services\StatisticsService();
$stats = $service->calculateStatistics();
print_r($stats);
```

---

**Ostatnia aktualizacja**: 2025-01-20




