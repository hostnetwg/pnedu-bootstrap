# ✅ Naprawa konwersji blocks w pakiecie CertificateGeneratorService

## 🐛 Problem
Na `pnedu.pl` generowanie certyfikatów działało, ale:
- Nie odczytywało ustawień szablonów
- Nie wyświetlało grafik (logo, tło)

## 🔍 Przyczyna
W bazie danych `template_config['blocks']` jest zapisane jako obiekt (associative array) z kluczami: `header`, `participant_info`, `course_info`, `instructor_signature`, `footer`.

W `pneadm-bootstrap` kontroler konwertował `blocks` z obiektu na tablicę numeryczną przed iteracją:
```php
if (array_keys($blocksRaw) !== range(0, count($blocksRaw) - 1)) {
    // To jest obiekt (associative array) - konwertuj na tablicę
    $blocks = array_values($blocksRaw);
}
```

Ale w pakiecie `CertificateGeneratorService` nie było tej konwersji, więc `foreach` iterował po obiekcie, co powodowało problemy z renderowaniem.

## ✅ Rozwiązanie
Dodano konwersję `blocks` z obiektu na tablicę numeryczną w `CertificateGeneratorService::getCertificateData()`:

```php
$blocksRaw = $templateConfig['blocks'] ?? [];

// Konwertuj blocks z obiektu na tablicę (jeśli jest obiektem)
$blocks = [];
if (is_array($blocksRaw)) {
    // Sprawdź czy to obiekt (associative array) czy tablica numeryczna
    if (array_keys($blocksRaw) !== range(0, count($blocksRaw) - 1)) {
        // To jest obiekt (associative array) - konwertuj na tablicę
        $blocks = array_values($blocksRaw);
    } else {
        // To już jest tablica numeryczna
        $blocks = $blocksRaw;
    }
}
```

## 🔍 Weryfikacja
Przed naprawą:
```
Blocks type: array
Is array: YES
Is numeric array: NO
Keys: header, participant_info, course_info, instructor_signature, footer
```

Po naprawie:
- `blocks` jest konwertowane na tablicę numeryczną
- `sorted_blocks` zawiera poprawnie posortowane bloki
- `header_config` i `footer_config` są poprawnie wyodrębnione
- Grafiki powinny się teraz wyświetlać

## ✅ Status
- ✅ Konwersja `blocks` dodana do pakietu
- ✅ Kompatybilność z obiektami i tablicami numerycznymi
- ✅ Wszystkie szablony powinny teraz działać poprawnie na `pnedu.pl`








