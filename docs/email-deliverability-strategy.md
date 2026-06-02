# Strategia e-mail — pnedu.pl (skrót)

**Status:** referencja do dokumentu master + stan wdrożenia Laravel + SES
**Ostatnia aktualizacja:** 2026-06-02
**Master doc:** repozytorium `pneadm`, plik `docs/email-deliverability-strategy.md`.

> Pełna strategia, mapy subdomen, plan etapów Sendy/DNS/SEO — w pliku master. Ten dokument opisuje **serwis publiczny `pnedu.pl`** i status wdrożenia maili systemowych.

---

## Decyzja kierunkowa

- **pnedu.pl** — docelowa domena frontu i aplikacji uczestnika.
- **nowoczesna-edukacja.pl** — legacy (Publigo); docelowo prawdopodobnie **301 → pnedu.pl** — **osobny program SEO**, nie blokada SES.
- Maile systemowe Laravel: **wdrożone** na SES (`info@system.pnedu.pl`, Reply-To `kontakt@pnedu.pl`).
- Treści www (regulamin, RODO, stopka) — nadal częściowo legacy; **osobny etap treści**.

---

## Kontakt

```
kontakt@pnedu.pl  →  (przekierowanie)  →  kontakt@nowoczesna-edukacja.pl
```

- Publiczny Reply-To dla maili systemowych: **`kontakt@pnedu.pl`**
- Formularz kontaktowy w aplikacji wysyła **do** `kontakt@pnedu.pl` (`ContactController`)
- Nie używać `kontakt@pnedu.pl` jako masowego **From**

---

## Kanały nadawcze (skrót)

| Kanał | From | Narzędzie w pnedu | Status |
|-------|------|-------------------|--------|
| System / transakcyjny | `info@system.pnedu.pl` | Laravel Mail → SES | **produkcja: `MAIL_MAILER=ses`** |
| Marketing ogólny | `szkolenia@news.pnedu.pl` | Sendy | poza Laravel |
| TIK / webinary | `webinary@tik.pnedu.pl` | Sendy | poza Laravel |
| Obsługa | `kontakt@pnedu.pl` | odbiorca formularza | **wdrożone** |

---

## Wiadomości wysyłane z tego repozytorium

| Wiadomość | Plik | Status |
|-----------|------|--------|
| Potwierdzenie zamówienia + PDF | `OrderNotificationMail` | **wdrożone** — SES + brand `pnedu.pl` w mailu i PDF |
| Formularz kontaktowy (do biura) | `ContactFormMail` | **wdrożone** — systemowy From; odbiorca `kontakt@pnedu.pl` |
| Powiadomienie o płatności (admin) | `PaymentNotificationMail` | **wdrożone** — wewnętrzne |
| Weryfikacja e-mail | `SystemVerifyEmail` | **wdrożone** |
| Reset hasła | `SystemResetPassword` | **wdrożone** |

Sendy (bez Laravel Mail): bez zmian w ramach strategii SES.

---

## Konfiguracja Laravel

- `config/mail.php` — sekcje `system`, `brand`; mailer `ses`.
- Lokalnie: `MAIL_MAILER=log` (`.env.example`).
- Produkcja: `MAIL_MAILER=ses`, `AWS_DEFAULT_REGION=eu-central-1`, klucze IAM SES.
- `aws/aws-sdk-php`; `config.platform.php` = `8.3.27`.
- Testy: `tests/Feature/Mail/OrderNotificationMailTest.php`, `SystemMailConfigurationTest.php`.

---

## Co dalej (poza Laravel + SES)

1. Migracja treści www `nowoczesna-edukacja.pl` → `pnedu.pl` (regulamin, RODO, stopka).
2. Sendy — nadawcy `news` / `tik` (konfiguracja poza repozytorium).
3. SES advanced: custom MAIL FROM, configuration sets, bounce/complaint monitoring (master doc, sekcje 9–13).
4. iFirma — osobna decyzja (`faktury@system.pnedu.pl`).

---

*Skrót utrzymywany w repozytorium `pnedu`. Szczegóły wdrożenia adm.pnedu.pl — w master doc.*
