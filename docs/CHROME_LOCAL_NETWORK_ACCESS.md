# Chrome — prompt „Dostęp do innych aplikacji…” (Local Network Access)

Data: 2026-07-13  
Plik: `resources/views/layouts/analytics-head.blade.php`

## Problem

Od Chrome 142 publiczne strony (np. `https://pnedu.pl`) przy próbie połączenia z **localhost** lub siecią prywatną (`127.0.0.1`, `192.168.*`) mogą pokazać prompt:

> Dostęp do innych aplikacji i usług na tym urządzeniu

Często wywołują go tagi z **GTM/GA4** (skrypty agencji), nie logowanie Laravel.

## Rozwiązanie (opcja 1 — wdrożone)

Przed załadowaniem GTM/GA wczesny skrypt blokuje `fetch()` i `XMLHttpRequest` do hostów loopback / prywatnych. Analityka na zewnętrznych domenach Google działa bez zmian.

**Nie obejmuje:** `<script src="http://127.0.0.1/...">`, iframe, `sendBeacon` — wtedy audyt kontenera GTM u agencji.

## Deploy prod (`pnedu.pl`)

```bash
cd ~/domains/pnedu.pl/app
git pull origin main
/opt/alt/php82/usr/bin/php artisan view:clear
/opt/alt/php82/usr/bin/php artisan view:cache
```

Weryfikacja: `/login` w Chrome — brak promptu (po hard refresh). Źródło strony: przed `gtm.js` widać `Blocked local network request` w skrypcie ochronnym.

## Użytkownik kliknął „Zablokuj”

Kłódka przy adresie → ustawienia witryny → **Dostęp do sieci lokalnej** → Zezwól.

## Opcja 2 (nie wdrożona)

Wyłączenie GTM/GA na `/login`, `/register` — zero promptu na auth, brak pageview w GA na tych URL-ach.
