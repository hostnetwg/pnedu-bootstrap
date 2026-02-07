# Audyt pnedu.pl / nowoczesna-edukacja.pl – wymogi PayU i Paynow

**Data audytu:** 7 lutego 2026  
**Źródła:** [PayU – Przewodnik po elementach strony sklepu](https://poland.payu.com/dokumenty-prawne-do-pobrania/), [URL – wymagania w pigułce](https://poland.payu.com/dokumenty-prawne-do-pobrania/), dokumentacja Paynow

---

## 1. Wymogi PayU – podsumowanie

### ✅ SPEŁNIONE

| Wymóg | Status |
|-------|--------|
| **Oferta** – opis usługi | ✓ Opis szkoleń na stronie kursu |
| **Oferta** – cena | ✓ Cena brutto widoczna na kursie i formularzu |
| **Oferta** – panel zakupowy | ✓ Formularz płatności online |
| **Oferta** – zdjęcie poglądowe | ✓ Dla usług (szkoleń) nie jest wymagane; zdjęcia instruktorów są |
| **Język = waluta** | ✓ Polski + PLN |
| **Regulamin** – widoczny na stronie | ✓ Link w stopce |
| **Regulamin** – informacje rejestrowe | ✓ §1: nazwa, NIP, adres, e-mail, telefon |
| **Regulamin** – polityka zwrotów | ✓ §8: 14 dni, wyjątki dla usług cyfrowych |
| **Regulamin** – reklamacje | ✓ §9: kontakt e-mail, termin 14 dni |
| **Regulamin** – wzór formularza odstąpienia | ✓ Załącznik 1 |
| **Polityka prywatności** | ✓ Pełna, z danymi administratora |
| **Polityka prywatności** – PayU jako odbiorca | ✓ §6: PayU S.A., mElements S.A. (Paynow) |
| **Polityka prywatności** – RODO, cele, ochrona, uprawnienia | ✓ |
| **SSL** | ✓ pnedu.pl dostępne przez HTTPS |

---

### ⚠️ DO UZUPEŁNIENIA / KOREKTY

#### 1. Akceptacja Regulaminu w formularzu zakupu (krytyczne)

**Wymóg PayU:**  
> Klient przed dokonaniem zakupu powinien mieć możliwość zapoznania się z Regulaminem i **musi go zaakceptować w formularzu zakupu**.

**Aktualny stan:**  
Formularz płatności online (`/courses/{id}/pay-online`) **nie zawiera** checkboxa akceptacji Regulaminu. Formularz z odroczonym terminem płatności ma taki checkbox (`consent`).

**Rekomendacja:**  
Dodać przed przyciskiem „Przejdź do płatności” pole:
```
☐ Akceptuję [Regulamin](link) oraz [Politykę prywatności](link). *
```

---

#### 2. Tekst przycisku potwierdzającego zakup

**Wymóg PayU:**  
> Przycisk potwierdzający zakup typu „**zamawiam i płacę**” – ma uświadamiać, że zamówienie pociąga za sobą obowiązek zapłaty.

**Aktualny stan:**  
- Formularz pay-online: **„Przejdź do płatności”**  
- Regulamin §5.2 mówi o „Kupuję i płacę”

**Rekomendacja:**  
Zmienić tekst przycisku na „**Zamawiam i płacę**” lub „**Kupuję i płacę**” (zgodnie z Regulaminem), aby jasno informować o obowiązku zapłaty.

---

#### 3. Adres do zwrotów (polityka zwrotów)

**Wymóg PayU:**  
> Polityka zwrotów: **adres do zwrotów** (może różnić się od adresu rejestracji), 14 dni.

**Aktualny stan:**  
Regulamin §8 określa prawo odstąpienia i sposób złożenia oświadczenia (e-mail, poczta), ale nie zawiera wprost **adresu do zwrotów** w sekcji zwrotów. Adres Usługodawcy jest w §1.

**Rekomendacja:**  
W §8 dodać wprost:  
*„Zwroty i reklamacje kierować na adres: ul. A. Zamoyskiego 30/14, 09-320 Bieżuń lub e-mail: kontakt@nowoczesna-edukacja.pl.”*

---

#### 4. Czas realizacji zamówienia

**Wymóg PayU:**  
> Czas realizacji zamówienia wyrażony w **dniach roboczych** lub **dobach**.

**Aktualny stan:**  
§7 Regulaminu opisuje, kiedy dostarczane są usługi („po opłaceniu”, „w terminie wskazanym w opisie”), ale bez wyraźnego zapisu typu „średni czas realizacji: X dni roboczych”.

**Rekomendacja:**  
Dla usług cyfrowych/szkoleń można dodać np.:  
*„Dostęp do kursu/szkolenia udzielany jest w ciągu 24 h od zaksięgowania płatności”* lub *„w dniu szkolenia”* – w zależności od faktycznego sposobu realizacji.

---

#### 5. Spójność domen – regulamin a strona

**Aktualny stan:**  
- Regulamin i Polityka prywatności odwołują się do **nowoczesna-edukacja.pl**
- Płatności realizowane są na **pnedu.pl**

**Rekomendacja:**  
Uzupełnić Regulamin i Politykę o informację, że dotyczą także serwisu pnedu.pl (np. *„Serwis (w tym pnedu.pl, nowoczesna-edukacja.pl)”*), jeśli oba serwisy należą do tego samego usługodawcy.

---

## 2. Wymogi Paynow – podsumowanie

Paynow (mElements S.A.) jest bramką płatności mBanku. Wymagania są zbliżone do PayU i wynikają głównie z ustawy o prawach konsumenta oraz ustawy o świadczeniu usług drogą elektroniczną.

| Wymóg | Status |
|-------|--------|
| Regulamin sklepu | ✓ |
| Polityka prywatności | ✓ |
| Dane sprzedawcy | ✓ |
| Obsługa reklamacji i zwrotów | ✓ |
| Akceptacja regulaminu przed płatnością | ⚠️ Brak w formularzu pay-online |

**Uwaga:** Paynow wymaga pozytywnej weryfikacji sklepu i umowy z mBankiem. Wymogi informacyjne na stronie są analogiczne do PayU.

---

## 3. Rekomendacje priorytetowe

| Priorytet | Działanie |
|-----------|-----------|
| **1 – Wysoki** | Dodać checkbox akceptacji Regulaminu i Polityki prywatności w formularzu płatności online |
| **2 – Wysoki** | Zmienić tekst przycisku na „Zamawiam i płacę” lub „Kupuję i płacę” |
| **3 – Średni** | Uzupełnić §8 Regulaminu o adres do zwrotów |
| **4 – Średni** | Doprecyzować czas realizacji w §7 Regulaminu |
| **5 – Niski** | Ujednolicić nazewnictwo domen (pnedu.pl / nowoczesna-edukacja.pl) w Regulaminie i Polityce |

---

## 4. Pliki do modyfikacji

- `resources/views/courses/pay-online.blade.php` – checkbox akceptacji Regulaminu, tekst przycisku
- `resources/views/regulamin.blade.php` – adres do zwrotów, czas realizacji, spójność domen
- `resources/views/polityka-prywatnosci.blade.php` – spójność domen (opcjonalnie)
- `app/Http/Controllers/CourseController.php` – walidacja nowego pola `accept_regulamin` (jeśli checkbox zostanie dodany)

---

## 5. Źródła

- [PayU – Dokumenty do pobrania](https://poland.payu.com/dokumenty-prawne-do-pobrania/) – Przewodnik po elementach strony sklepu, URL – wymagania w pigułce
- [PayU – Poradnik integracji](https://developers.payu.com/europe/pl/docs/)
- [Paynow – Strona główna](https://www.paynow.pl/)
