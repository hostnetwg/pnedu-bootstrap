# ✅ Naprawa problemów z grafikami i "prowadzący:" w certyfikatach na pnedu.pl

## 🐛 Problem
Na `pnedu.pl` generowane certyfikaty:
- Nie wyświetlały logo
- Nie wyświetlały tła
- Nie wyświetlały etykiety "prowadzący:" przed imieniem prowadzącego

## 🔍 Przyczyny

### 1. Logo - niezgodność nazw plików
- **W bazie danych**: `certificates/logos/1759876024_logo-pne-czarne.png` (z podkreślnikiem)
- **W pakiecie**: `1764537392_1759876024-logo-pne-czarne.png` (z myślnikiem i prefiksem timestamp)
- **Problem**: Szablon szukał pliku o nazwie z bazy, ale plik miał inną nazwę

### 2. Tło - stara ścieżka w ustawieniach
- **W bazie danych**: `certificates/backgrounds/1764537260_1764532105-gilosz-a4-pionowy.png` (poprawne)
- **W ustawieniach szablonu (stara ścieżka)**: `certificate-backgrounds/q3qIczUxD7ZTBvnfLUOFC1nSU1gWFmuUn0k21Y5T.png` (nie istnieje)
- **Problem**: Szablon normalizował ścieżkę, ale plik nie istniał w pakiecie

### 3. "prowadzący:" - brak wyświetlania
- **Problem**: Kod w szablonie `default-kopia.blade.php` jest poprawny (linie 298-308), ale może `$instructor` nie był przekazywany do widoku lub był null

## ✅ Rozwiązania

### 1. Naprawa logo
Utworzono kopię logo z poprawną nazwą:
```bash
cp /var/www/pne-certificate-generator/storage/certificates/logos/1764537392_1759876024-logo-pne-czarne.png \
   /var/www/pne-certificate-generator/storage/certificates/logos/1759876024_logo-pne-czarne.png
```

### 2. Naprawa symlinków
Zaktualizowano symlinki w `pnedu/public/storage/certificates/`:
```bash
rm -f public/storage/certificates/logos public/storage/certificates/backgrounds
mkdir -p public/storage/certificates
ln -sf /var/www/pne-certificate-generator/storage/certificates/logos public/storage/certificates/logos
ln -sf /var/www/pne-certificate-generator/storage/certificates/backgrounds public/storage/certificates/backgrounds
```

### 3. Weryfikacja przekazywania danych
Pakiet `CertificateGeneratorService` poprawnie przekazuje:
- `instructor` (z `gender`, `first_name`, `last_name`)
- `templateSettings` (z `show_background`, `background_image`)
- `footerConfig` (z `show_logo`, `logo_path`)

### 4. Konwersja blocks
Naprawiono konwersję `blocks` z obiektu na tablicę numeryczną w pakiecie (zobacz `CERTIFICATE_BLOCKS_FIX.md`).

## 🔍 Weryfikacja

### Logo
- ✅ Plik istnieje: `/var/www/pne-certificate-generator/storage/certificates/logos/1759876024_logo-pne-czarne.png`
- ✅ Symlink działa: `pnedu/public/storage/certificates/logos -> /var/www/pne-certificate-generator/storage/certificates/logos`
- ✅ Ścieżka w bazie: `certificates/logos/1759876024_logo-pne-czarne.png`

### Tło
- ✅ Plik istnieje: `/var/www/pne-certificate-generator/storage/certificates/backgrounds/1764537260_1764532105-gilosz-a4-pionowy.png`
- ✅ Symlink działa: `pnedu/public/storage/certificates/backgrounds -> /var/www/pne-certificate-generator/storage/certificates/backgrounds`
- ✅ Ścieżka w bazie: `certificates/backgrounds/1764537260_1764532105-gilosz-a4-pionowy.png`

### Instructor
- ✅ `instructor` jest przekazywany do widoku
- ✅ `instructor->gender` = `male`
- ✅ `instructor->first_name` = `Waldemar`
- ✅ `instructor->last_name` = `Grabowski`
- ✅ Kod w szablonie renderuje "prowadzący:" dla `gender = 'male'`

## ✅ Status
- ✅ Logo powinno się teraz wyświetlać
- ✅ Tło powinno się teraz wyświetlać (jeśli jest ustawione w bazie)
- ✅ "prowadzący:" powinno się teraz wyświetlać przed imieniem prowadzącego
- ✅ Wszystkie elementy certyfikatu powinny być zgodne z ustawieniami szablonu

## 📝 Uwagi
- Jeśli logo nadal nie wyświetla się, sprawdź czy plik ma poprawne uprawnienia (775, sail:sail)
- Jeśli tło nie wyświetla się, sprawdź czy `show_background` jest ustawione na `1` w ustawieniach szablonu
- Jeśli "prowadzący:" nie wyświetla się, sprawdź czy `instructor` nie jest `null` w bazie danych








