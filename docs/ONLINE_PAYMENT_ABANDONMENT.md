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

- Etap 2: przycisk „Zapłać ponownie” na `/payment/pending`
- Etap 3: e-mail recovery (cron + ręcznie w adm)
- Etap 4: badge/filtr „porzucona płatność” w adm

Pełna strategia: wątek decyzyjny 2026-08-22 (60 min, auto-anulowanie, oba linki w mailu recovery).
