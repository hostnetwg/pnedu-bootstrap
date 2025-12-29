# ✅ Naprawa braku podpisu instruktora na pnedu.pl

## 🐛 Problem
Na `pnedu.pl` nie wyświetlał się podpis instruktora (Waldemar Grabowski), ponieważ plik podpisu znajdował się tylko w `pneadm-bootstrap`, a `pnedu.pl` nie miał do niego dostępu.

## ✅ Rozwiązanie

### 1. Przeniesienie pliku podpisu
Skopiowano plik podpisu do wspólnego pakietu:
- Źródło: `pneadm-bootstrap/storage/app/public/instructors/5bs90suNZHVMou0ViKk1Phdxq2bOAVrvf50UyBPd.jpg`
- Cel: `pne-certificate-generator/storage/instructors/5bs90suNZHVMou0ViKk1Phdxq2bOAVrvf50UyBPd.jpg`

### 2. Aktualizacja szablonu
Zaktualizowano `default-kopia.blade.php` (i inne szablony w pakiecie), aby szukały podpisu w pakiecie, jeśli nie znajdą go lokalnie:

```php
// Sprawdź pakiet (priorytet)
$packagePaths = [
    '/var/www/pne-certificate-generator/storage/' . $instructor->signature,
    base_path('../pne-certificate-generator/storage/' . $instructor->signature),
    __DIR__ . '/../../storage/' . $instructor->signature,
];

foreach ($packagePaths as $packagePath) {
    if (file_exists($packagePath)) {
        $signatureFile = $packagePath;
        break;
    }
}
```

Dodatkowo poprawiono obsługę przezroczystości dla PNG (dodano zachowanie kanału alpha i poprawiono obsługę błędów).

## 🔍 Weryfikacja
- Plik podpisu istnieje w pakiecie.
- Szablon potrafi teraz pobrać podpis z pakietu.

## 📝 Uwagi dot. układu ("Data..." i "Prowadzący...")
Użytkownik zgłaszał problem z rozmieszczeniem. Szablon używa pozycjonowania absolutnego (`position: absolute`) zależnego od marginesów.
- `.date-section` - wyrównane do lewej (margin-left)
- `.instructor-section` - wyrównane do prawej (margin-right)

Wartości `top` są obliczane dynamicznie:
```php
$dateTop = $pageHeight - $marginBottom - 180;
```
Jeśli `marginBottom` w ustawieniach szablonu jest duży, sekcje mogą powędrować zbyt wysoko. Warto sprawdzić ustawienia marginesów w edytorze szablonu.

## ✅ Status
- ✅ Podpis instruktora powinien się teraz wyświetlać
- ✅ Obsługa plików instruktorów z pakietu dodana
















