# Porzucona płatność online — Etap 1 (blokada e-mail)

Data: 2026-08-22  
Projekt: **pnedu** (logika formularza) · baza **pneadm** (`form_orders`)

## Problem

Po nieudanej lub porzuconej płatności online zamówienie zostaje w bazie bez `cancelled_at`. Walidacja unikalności e-mail + kurs blokowała ponowne złożenie zamówienia z fakturą odroczoną tym samym adresem uczestnika.

## Etap 1 — wdrożone

### 1. Zwalnianie slotu e-mail

`OrderFormParticipantService` nie traktuje jako konfliktu zamówień online, które są:

- `payment_status` = **cancelled** lub **failed** — od razu, **albo**
- `payment_status` = **awaiting_payment** i minęło ≥ **60 min** od ostatniej aktywności (data zamówienia lub ostatnia próba w `online_payment_orders`).

**Nie zwalnia** slotu dla: faktury odroczonej, opłaconego online, zamówień z FV, `status_completed`.

Konfiguracja: `config/order_form.php` → `online_abandonment_minutes` (env: `ORDER_FORM_ONLINE_ABANDONMENT_MINUTES`, domyślnie 60).

### 2. Auto-anulowanie przy fakturze odroczonej

Przed walidacją e-maila przy zapisie zamówienia odroczonego (`storeDeferredOrder`, `storeOrderForm` z `payment_type=deferred`):

- wyszukiwane są nieopłacone zamówienia online (`online_gateway`, bez FV, bez `paid`) z tym samym e-mailem uczestnika na tym kursie,
- ustawiane: `cancelled_at`, `cancelled_reason = 'zastąpione zamówieniem odroczonym'`,
- **`payment_status` bez zmian** (historia bramki zostaje).

Serwis: `App\Services\FormOrderOnlineAbandonmentService`.

## Testy

```bash
cd pnedu
sail test --filter=FormOrderOnlineAbandonment
```

## Deploy (pnedu)

```bash
cd /home/srv66127/domains/pnedu.pl/app
git pull origin main
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

Brak migracji (kolumny `cancelled_at` / `cancelled_reason` już w bazie pneadm).

## Kolejne etapy (plan)

- ~~Etap 2: przycisk „Zapłać ponownie” na `/payment/pending`, mail startowy online~~ — **wdrożone** (patrz sekcja poniżej)
- ~~Etap 3: e-mail recovery (cron + ręcznie w adm)~~ — **wdrożone** (patrz sekcja poniżej)
- ~~Etap 4: badge/filtr „porzucona płatność” w adm~~ — **wdrożone** (patrz sekcja poniżej)

Pełna strategia: wątek decyzyjny 2026-08-22 (60 min, auto-anulowanie, oba linki w mailu recovery).

---

## Etap 2 — wdrożone (retry + pending + mail startowy)

### 1. Ponowienie płatności (signed URL)

- Trasa: `GET /orders/{ident}/retry-payment` (`orders.retry-payment`), middleware `signed`.
- Serwis: `App\Services\FormOrderOnlinePaymentRetryService`.
- Tworzy nowy rekord `online_payment_orders` na tym samym `form_order_id`, ustawia `payment_status = awaiting_payment`, przekierowuje do PayU/PayNow.
- Ważność linku: `config/order_form.php` → `online_retry_signed_url_days` (env: `ORDER_FORM_ONLINE_RETRY_SIGNED_URL_DAYS`, domyślnie 7).

### 2. Strona `/payment/pending/{ident}`

- Numer zamówienia (`form_orders.ident`), kwota, kontakt.
- Przycisk **Zapłać ponownie** (signed URL).
- Link **Wolę fakturę z odroczonym terminem** → formularz z `?prefill_from=` i `payment_type=deferred` (bez `order_ident`, nowe zamówienie odroczone anuluje stare online — Etap 1).

### 3. E-mail po starcie płatności online

- `App\Mail\OnlinePaymentStartedMail` — wysyłany po utworzeniu pierwszego `OnlinePaymentOrder` w checkout online oraz przy ponowieniu płatności.
- Odbiorcy: e-mail zamawiającego + uczestników (bez duplikatów).
- Zawiera: link retry, link FV odroczonej, link do strony pending.

### Testy

```bash
cd pnedu
sail test --filter=FormOrderOnlinePaymentRetry
sail test --filter=OnlinePaymentStartedMail
```

### Deploy (pnedu)

```bash
cd /home/srv66127/domains/pnedu.pl/app
git pull origin main
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

Brak migracji.

---

## Etap 3 — wdrożone (recovery e-mail: cron + adm)

### 1. Automatyczny cron (pnedu)

- Komenda: `form-orders:send-online-payment-recovery-emails` (co godzinę przez `schedule:run`).
- Wysyła **jeden** recovery e-mail na zamówienie (`online_payment_recovery_sent_at`).
- Kandydaci: porzucone nieopłacone online (`FormOrderOnlineAbandonmentService::isAbandonedUnpaidOnline`) — failed/cancelled od razu, `awaiting_payment` po ≥ 60 min.
- Mail: `OnlinePaymentRecoveryMail` — linki **Zapłać ponownie** + **FV odroczona** + pending.
- Konfiguracja: `online_recovery_enabled` (env: `ORDER_FORM_ONLINE_RECOVERY_ENABLED`).

```bash
cd pnedu
sail artisan form-orders:send-online-payment-recovery-emails --dry-run
```

### 2. Ręcznie z adm (pneadm)

- Przycisk na stronie zamówienia: **Wyślij mail recovery płatności** (pasek „Rozliczenie”).
- Wywołanie server-to-server: `POST /api/internal/form-orders/{id}/send-online-payment-recovery` (pnedu, token `PNEDU_INTERNAL_API_TOKEN`).
- Ręczna wysyłka **może powtórzyć** mail (`allow_resend=true`).

### 3. Migracja (pneadm → baza pneadm)

Kolumna `form_orders.online_payment_recovery_sent_at` (nullable timestamp).

### Testy

```bash
cd pnedu
sail test --filter=FormOrderOnlinePaymentRecovery
sail test --filter=OnlinePaymentRecoveryMail

cd ../pneadm
sail test --filter=PneduOnlinePaymentRecovery
```

### Deploy

**pnedu:**
```bash
cd /home/srv66127/domains/pnedu.pl/app
git pull origin main
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

**pneadm** (migracja + adm):
```bash
cd /home/srv66127/domains/adm.pnedu.pl/pneadm
git pull origin main
/opt/alt/php82/usr/bin/php artisan migrate --force
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

Cron pnedu: `schedule:run` musi obejmować nową komendę hourly (istniejący cron daily — sprawdź czy prod ma `* * * * * schedule:run` lub dodaj osobny wpis).

---

## Etap 4 — wdrożone (badge + filtr w adm)

Projekt: **pneadm**

### Kryteria (jak Etap 1)

Zamówienie ma badge / trafia do filtra, gdy:

- `payment_mode = online_gateway`
- bez `cancelled_at`, bez FV, bez `status_completed`, `payment_status != paid`
- oraz: `payment_status` ∈ {`cancelled`, `failed`} **albo** `awaiting_payment` i minęło ≥ **60 min** od ostatniej aktywności (`order_date` / `created_at` / max `online_payment_orders.created_at`)

Serwis: `App\Services\FormOrderOnlineAbandonmentService` (pneadm)  
Config: `config/form_orders.php` → `online_abandonment_minutes` (env: `ORDER_FORM_ONLINE_ABANDONMENT_MINUTES`)

### UI

- Lista `/form-orders`: checkbox **„Tylko porzucona płatność online”** (`abandoned_online=1`)
- Badge na karcie: **PORZUCONA PŁATNOŚĆ**
- Badge na stronie szczegółów (pasek rozliczenia)

### Testy

```bash
cd pneadm
sail test --filter=FormOrderOnlineAbandonment
sail test --filter=FormOrdersAbandonedOnline
```

### Deploy (pneadm)

```bash
cd /home/srv66127/domains/adm.pnedu.pl/pneadm
git pull origin main
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

Brak migracji (Etap 4).
