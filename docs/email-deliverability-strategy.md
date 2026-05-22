# Strategia e-mail — pnedu.pl (skrót)

**Status:** referencja do dokumentu master  
**Master doc:** dokument master znajduje się w repozytorium `pneadm`, w pliku `docs/email-deliverability-strategy.md`.

> Pełna strategia, mapy subdomen, plan etapów i ryzyka — w pliku master. Ten dokument opisuje **tylko to, co dotyczy serwisu publicznego `pnedu.pl`**.

---

## Decyzja kierunkowa (2026-05)

- **pnedu.pl** — docelowa domena frontu i aplikacji uczestnika.
- **nowoczesna-edukacja.pl** — legacy (Publigo); docelowo prawdopodobnie **301 → pnedu.pl** — **osobny program**, nie teraz.
- Nie wykonywać hurtowej podmiany domen w kodzie przy konfiguracji e-mail.

---

## Kontakt

```
kontakt@pnedu.pl  →  (przekierowanie)  →  kontakt@nowoczesna-edukacja.pl
```

- Publiczny Reply-To dla maili systemowych i marketingu: **`kontakt@pnedu.pl`**
- Nie używać `kontakt@pnedu.pl` ani `kontakt@nowoczesna-edukacja.pl` jako masowego **From**
- W kodzie nadal występuje `kontakt@nowoczesna-edukacja.pl` — migracja treści to osobny etap

---

## Kanały nadawcze (skrót)

| Kanał | From | Narzędzie w pnedu |
|-------|------|-------------------|
| System / transakcyjny | `info@system.pnedu.pl` | Laravel Mail → SES (docelowo) |
| Marketing ogólny | `szkolenia@news.pnedu.pl` | Sendy (zapis na listy z kodu) |
| TIK / webinary | `webinary@tik.pnedu.pl` | Sendy |
| Obsługa | `kontakt@pnedu.pl` | odbiorca formularza kontaktowego |

**system.pnedu.pl** — na razie tylko tożsamość SES, nie publiczna strona www.

---

## Wiadomości wysyłane z tego repozytorium

| Wiadomość | Plik | Kanał docelowy |
|-----------|------|----------------|
| Potwierdzenie zamówienia + PDF | `OrderNotificationMail` | **system** — From `info@system.pnedu.pl`; wysyłka **zostaje w pnedu** |
| Formularz kontaktowy (do biura) | `ContactFormMail` | odbiorca docelowo `kontakt@pnedu.pl`; w kodzie obecnie `kontakt@nowoczesna-edukacja.pl` |
| Powiadomienie o płatności (admin) | `PaymentNotificationMail` | wewnętrzne |
| Weryfikacja e-mail | Laravel Breeze | **system** |
| Reset hasła | Laravel Breeze | **system** |

Sendy (bez Laravel Mail):

- `LIST_TIK_NAUCZYCIEL` → kanał **tik**
- `LIST_NAUCZYCIELE`, listy płatnych / per-kurs (`sendy_suppression_list_id`) → **news** (lub **tik** dla webinarów)

---

## Linki publiczne (docelowo)

Preferencja: tokeny na **pnedu.pl**, nie **adm.pnedu.pl**, np.:

- `pnedu.pl/certificates/{token}`
- `pnedu.pl/certificate/{token}/{course_id}`
- `pnedu.pl/uzupelnij-dane/{token}` (do przeniesienia z adm — **nie implementować teraz**)

---

## Zasady (skrót)

- Nie wysyłać maili do klientów z From `@adm.pnedu.pl`.
- Nie mieszać marketingu z kanałem `system.pnedu.pl`.
- Nie robić cutover całej bazy TIK (~62k) naraz.
- Nie zaostrzać DMARC bez monitoringu.

---

## Konfiguracja (stan obecny — bez zmian)

- `config/mail.php` — domyślny mailer `smtp` / Mailpit lokalnie; FROM fallback `kontakt@nowoczesna-edukacja.pl`
- `config/services.php` — Sendy (`SENDY_URL`, `SENDY_API_KEY`), połączenie z adm (`PNEADM_*`)
- Brak aktywnego mailera SES — do wdrożenia w fazie C planu master

---

## Następne kroki (pnedu)

1. Czytać pełny plan w repozytorium `pneadm` (`docs/email-deliverability-strategy.md`), sekcje 13–15.
2. Przy wdrożeniu: mailer `system` + SES `eu-central-1`, `OrderNotificationMail` jako pierwszy kandydat produkcyjny po testach.
3. Osobno: migracja treści `nowoczesna-edukacja.pl` → `pnedu.pl` (SEO).

---

*Ostatnia aktualizacja: 2026-05-22*
