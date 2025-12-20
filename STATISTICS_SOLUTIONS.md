# 📊 Propozycje rozwiązań dla liczników statystyk na stronie głównej pnedu.pl

## 🎯 Cel
Zastąpienie statycznych liczników na stronie głównej rzeczywistymi danymi z bazy pneadm, przy zachowaniu wydajności serwera.

## 📈 Obecne liczniki
1. **Ilość przeszkolonych nauczycieli** - obecnie: `10000+`
2. **Ilość webinarów rocznie** - obecnie: `200+`
3. **Średnia ocena** - obecnie: `4.9`
4. **Certyfikowanych szkoleń** - obecnie: `100%`

## 🔍 Analiza danych w bazie pneadm

### Tabele do wykorzystania:
- `participants` - uczestnicy szkoleń (nauczyciele)
- `courses` - kursy/szkolenia (z polem `type` = 'online' dla webinarów)
- `surveys` + `survey_responses` - ankiety i odpowiedzi (do średniej oceny)
- `certificates` - certyfikaty (do obliczenia % certyfikowanych szkoleń)

### Zapytania SQL do obliczenia statystyk:

#### 1. Ilość przeszkolonych nauczycieli
```sql
-- Unikalni uczestnicy (po emailu lub imię+nazwisko)
SELECT COUNT(DISTINCT email) as unique_teachers
FROM participants
WHERE email IS NOT NULL AND email != ''

UNION ALL

-- Uczestnicy bez emaila (po imię+nazwisko)
SELECT COUNT(DISTINCT CONCAT(first_name, ' ', last_name))
FROM participants
WHERE email IS NULL OR email = '';
```

#### 2. Ilość webinarów rocznie
```sql
SELECT COUNT(*) as webinars_this_year
FROM courses
WHERE type = 'online'
AND YEAR(start_date) = YEAR(CURDATE())
AND is_active = 1;
```

#### 3. Średnia ocena
```sql
-- Podobnie jak w DashboardController - średnia ze wszystkich ankiet
-- Wymaga przetworzenia JSON z survey_responses.response_data
```

#### 4. Certyfikowanych szkoleń (%)
```sql
SELECT 
    (COUNT(DISTINCT c.course_id) * 100.0 / COUNT(DISTINCT co.id)) as certified_percentage
FROM courses co
LEFT JOIN certificates c ON co.id = c.course_id
WHERE co.is_active = 1;
```

---

## 🚀 Warianty rozwiązań

### **WARIANT 1: Cache Laravel (Redis/File) - REKOMENDOWANY** ⭐

#### Opis:
- Statystyki obliczane raz i przechowywane w cache Laravel
- Cache automatycznie odświeżany co określony czas (np. co godzinę)
- Najprostszy w implementacji, dobry balans wydajności/aktualności

#### Zalety:
✅ Prosta implementacja (wykorzystuje istniejący cache Laravel)  
✅ Brak dodatkowych zależności  
✅ Automatyczne odświeżanie  
✅ Możliwość ręcznego odświeżenia przez admina  
✅ Niskie obciążenie serwera (zapytania tylko przy odświeżeniu cache)  
✅ Działa z Redis lub file cache (elastyczność)

#### Wady:
❌ Dane mogą być nieaktualne do czasu odświeżenia cache (max 1h opóźnienia)  
❌ Wymaga konfiguracji cache (Redis lub file)

#### Implementacja:
- **Klasa serwisowa**: `app/Services/StatisticsService.php`
- **Cache TTL**: 3600 sekund (1 godzina)
- **Kontroler**: Modyfikacja `HomeController` do pobierania z cache
- **Komenda Artisan**: `sail artisan statistics:refresh` (opcjonalnie, do ręcznego odświeżania)

#### Wydajność:
- **Czas odpowiedzi strony**: ~50-100ms (pobranie z cache)
- **Obciążenie bazy**: 1x na godzinę (4 zapytania)
- **Pamięć**: ~1KB na statystykę

---

### **WARIANT 2: Scheduled Task (Cron Job) + Cache**

#### Opis:
- Statystyki obliczane przez zadanie cron (np. co 15 minut)
- Wyniki zapisywane w cache lub tabeli `statistics`
- Strona główna zawsze pobiera z cache/tabeli

#### Zalety:
✅ Pełna kontrola nad czasem obliczeń (można wykonać w nocy)  
✅ Brak wpływu na czas odpowiedzi strony  
✅ Możliwość przechowywania historii statystyk  
✅ Możliwość logowania błędów bez wpływu na użytkowników

#### Wady:
❌ Wymaga konfiguracji cron/scheduler  
❌ Wymaga dodatkowej tabeli (opcjonalnie)  
❌ Bardziej złożona implementacja

#### Implementacja:
- **Komenda Artisan**: `app/Console/Commands/UpdateStatistics.php`
- **Scheduler**: `app/Console/Kernel.php` (uruchamianie co 15 minut)
- **Tabela (opcjonalnie)**: `statistics` (dla historii)
- **Cache**: Jako backup/fallback

#### Wydajność:
- **Czas odpowiedzi strony**: ~10-50ms (pobranie z tabeli/cache)
- **Obciążenie bazy**: 4x na 15 minut (w tle)
- **Pamięć**: ~1KB + opcjonalna tabela

---

### **WARIANT 3: Queue Job (Asynchroniczne obliczanie)**

#### Opis:
- Statystyki obliczane asynchronicznie przez queue worker
- Trigger: przy każdej zmianie danych (dodanie uczestnika, kursu, certyfikatu) lub co X minut
- Wyniki w cache/tabeli

#### Zalety:
✅ Brak blokowania żądań HTTP  
✅ Możliwość priorytetyzacji zadań  
✅ Skalowalne rozwiązanie  
✅ Możliwość retry przy błędach

#### Wady:
❌ Wymaga działającego queue worker  
❌ Bardziej złożona architektura  
❌ Wymaga konfiguracji Redis/database queue

#### Implementacja:
- **Job**: `app/Jobs/UpdateStatisticsJob.php`
- **Event Listeners**: Automatyczne wywołanie przy zmianach danych
- **Scheduler**: Backup - uruchamianie co godzinę jeśli nie było zmian
- **Cache**: Przechowywanie wyników

#### Wydajność:
- **Czas odpowiedzi strony**: ~10-50ms
- **Obciążenie bazy**: W tle, asynchronicznie
- **Pamięć**: Zależna od konfiguracji queue

---

### **WARIANT 4: Materialized View / Tabela statystyk**

#### Opis:
- Dedykowana tabela `statistics` z aktualnymi wartościami
- Aktualizacja przez trigger'y SQL lub scheduled task
- Strona główna zawsze pobiera z tabeli (bardzo szybko)

#### Zalety:
✅ Najszybsze pobieranie danych (proste SELECT)  
✅ Możliwość przechowywania historii  
✅ Możliwość agregacji wielu metryk  
✅ Niezależność od cache

#### Wady:
❌ Wymaga utrzymania synchronizacji z danymi źródłowymi  
❌ Wymaga migracji bazy danych  
❌ Większa złożoność przy zmianach w strukturze danych

#### Implementacja:
- **Migracja**: `database/migrations/xxxx_create_statistics_table.php`
- **Model**: `app/Models/Statistics.php`
- **Aktualizacja**: Scheduled task lub event listeners
- **Kontroler**: Proste pobranie z tabeli

#### Wydajność:
- **Czas odpowiedzi strony**: ~5-20ms (jeden SELECT)
- **Obciążenie bazy**: Minimalne (tylko SELECT)
- **Pamięć**: Tabela ~10-50KB

---

### **WARIANT 5: API Endpoint z cache (pneadm-bootstrap)**

#### Opis:
- Endpoint API w pneadm-bootstrap zwracający statystyki
- Cache w pneadm-bootstrap
- pnedu pobiera przez HTTP request (z własnym cache)

#### Zalety:
✅ Centralizacja logiki statystyk w pneadm  
✅ Możliwość wykorzystania przez inne serwisy  
✅ Separacja odpowiedzialności

#### Wady:
❌ Wymaga konfiguracji API i autentykacji  
❌ Dodatkowe żądanie HTTP (nawet z cache)  
❌ Większa złożoność infrastruktury  
❌ Zależność między serwisami

#### Implementacja:
- **API Route**: `pneadm-bootstrap/routes/api.php`
- **Controller**: `pneadm-bootstrap/app/Http/Controllers/Api/StatisticsController.php`
- **Middleware**: Autentykacja tokenem
- **Client**: `pnedu/app/Services/PneadmApiService.php`

#### Wydajność:
- **Czas odpowiedzi strony**: ~50-200ms (HTTP request + cache)
- **Obciążenie**: Zależne od konfiguracji cache w obu serwisach

---

## 📊 Porównanie wariantów

| Wariant | Złożoność | Wydajność | Aktualność | Zalecenie |
|---------|-----------|-----------|------------|-----------|
| **1. Cache Laravel** | ⭐ Niska | ⭐⭐⭐ Dobra | ⭐⭐ 1h opóźnienie | ✅ **REKOMENDOWANY** |
| **2. Scheduled Task** | ⭐⭐ Średnia | ⭐⭐⭐ Doskonała | ⭐⭐ 15min opóźnienie | ✅ Dobry wybór |
| **3. Queue Job** | ⭐⭐⭐ Wysoka | ⭐⭐⭐ Doskonała | ⭐⭐⭐ Prawie real-time | ⚠️ Overkill dla tego przypadku |
| **4. Materialized View** | ⭐⭐ Średnia | ⭐⭐⭐⭐ Najlepsza | ⭐⭐ Zależne od aktualizacji | ✅ Dobry dla dużej skali |
| **5. API Endpoint** | ⭐⭐⭐ Wysoka | ⭐⭐ Średnia | ⭐⭐ Zależne od cache | ❌ Niepotrzebna złożoność |

---

## 🎯 Rekomendacja

### **Dla większości przypadków: WARIANT 1 (Cache Laravel)**

**Dlaczego?**
- Najprostszy w implementacji i utrzymaniu
- Wystarczająca wydajność (cache Laravel jest bardzo szybki)
- Opóźnienie 1h jest akceptowalne dla statystyk publicznych
- Możliwość ręcznego odświeżenia przez admina
- Działa z istniejącą infrastrukturą (Redis lub file cache)

### **Dla większej skali: WARIANT 2 (Scheduled Task)**

**Dlaczego?**
- Lepsza kontrola nad czasem obliczeń
- Możliwość przechowywania historii
- Brak wpływu na czas odpowiedzi strony
- Wykonywanie w tle (np. w nocy przy niskim ruchu)

---

## 📝 Następne kroki

Po wyborze wariantu:
1. ✅ Implementacja wybranego rozwiązania
2. ✅ Testy wydajnościowe
3. ✅ Konfiguracja cache/scheduler (jeśli wymagane)
4. ✅ Aktualizacja widoku `welcome.blade.php`
5. ✅ Dokumentacja dla zespołu

---

## 🔧 Szczegóły techniczne (dla implementacji)

### Zapytania do obliczenia statystyk:

#### 1. Ilość przeszkolonych nauczycieli
```php
// Unikalni uczestnicy (po emailu, fallback na imię+nazwisko)
$uniqueByEmail = DB::connection('pneadm')
    ->table('participants')
    ->whereNotNull('email')
    ->where('email', '!=', '')
    ->distinct('email')
    ->count('email');

$uniqueByName = DB::connection('pneadm')
    ->table('participants')
    ->where(function($query) {
        $query->whereNull('email')
              ->orWhere('email', '=', '');
    })
    ->select(DB::raw('CONCAT(first_name, " ", last_name) as full_name'))
    ->distinct()
    ->count();

$totalTeachers = $uniqueByEmail + $uniqueByName;
```

#### 2. Ilość webinarów rocznie
```php
$webinarsThisYear = DB::connection('pneadm')
    ->table('courses')
    ->where('type', 'online')
    ->whereYear('start_date', date('Y'))
    ->where('is_active', 1)
    ->count();
```

#### 3. Średnia ocena
```php
// Podobnie jak w DashboardController::generateStatistics()
// Wymaga przetworzenia survey_responses.response_data
// (logika już istnieje w pneadm-bootstrap)
```

#### 4. Certyfikowanych szkoleń (%)
```php
$totalCourses = DB::connection('pneadm')
    ->table('courses')
    ->where('is_active', 1)
    ->count();

$certifiedCourses = DB::connection('pneadm')
    ->table('certificates')
    ->distinct('course_id')
    ->count('course_id');

$certifiedPercentage = $totalCourses > 0 
    ? round(($certifiedCourses / $totalCourses) * 100, 0)
    : 0;
```

---

**Czekam na Twój wybór wariantu!** 🚀

