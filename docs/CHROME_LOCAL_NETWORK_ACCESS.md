# Chrome / Opera — prompt „Dostęp do innych aplikacji…” (Local Network Access)

Data: 2026-08-22  
Kod: `app/Http/Middleware/DenyLocalNetworkAccessPolicy.php`, `resources/views/layouts/analytics-head.blade.php`

## Problem

Od Chrome 142 (także Opera, Edge) publiczna strona (`https://pnedu.pl`) przy próbie połączenia z **localhost** / siecią prywatną (`127.0.0.1`, `192.168.*`) pokazuje prompt:

> „pnedu.pl” prosi o dostęp do innych aplikacji i usług na tym urządzeniu.

To **nie** jest logowanie Laravel i **nie** jest zwykła analityka (pageview GA). Chromium pyta, bo jakiś skrypt na stronie — najczęściej tag z **GTM/GA4 agencji** — sondą lokalne usługi na komputerze użytkownika (debug, helper, drukarka, menedżer haseł itd.). Inne firmy nie pokazują tego okna, gdy ich tagi nie ruszają localhost.

Blokada samego `fetch` / `XMLHttpRequest` **nie wystarcza**: przeglądarka potrafi zapytać o uprawnienie zanim JS przechwyci żądanie, albo gdy tag wstawi `<script src="http://127.0.0.1/…">`.

## Rozwiązanie (wdrożone)

1. **Nagłówek HTTP** `Permissions-Policy: local-network-access=(), local-network=(), loopback-network=()`  
   Dokument **nie ma** prawa do sieci lokalnej → Chromium **nie pokazuje promptu**, żądania na localhost kończą się błędem. Analityka na `google-analytics.com` / `googletagmanager.com` działa normalnie.
2. **Wczesny skrypt** przed GTM nadal blokuje `fetch`, XHR, `sendBeacon` i `WebSocket` do hostów prywatnych (zapas, gdy nagłówek nie zadziała w danej przeglądarce).

## Deploy prod (`pnedu.pl`)

```bash
cd /home/srv66127/domains/pnedu.pl/app
git pull origin main
/opt/alt/php82/usr/bin/php artisan optimize:clear
/opt/alt/php82/usr/bin/php artisan view:clear
```

Weryfikacja:

- `/login` w Operze/Chrome — **brak** promptu (twardy refresh).
- Nagłówek odpowiedzi: `Permissions-Policy` z `local-network-access=()`.
- W DevTools → Network nie powinno być udanych requestów na `127.0.0.1`.

## Użytkownik kliknął „Zablokuj”

Kłódka przy adresie → ustawienia witryny → **Dostęp do sieci lokalnej** / **Aplikacje na urządzeniu** → Zezwól (tylko gdyby kiedyś była potrzeba; na pnedu.pl nie jest).

## Nie robić

- Nie wyłączać całej analityki na `/login` bez potrzeby — pageview logowania może zostać, skoro LNA jest zabronione nagłówkiem.
- Nie ruszać GTM u agencji jako pierwszego kroku; jeśli prompt wróci po deployu, wtedy audyt tagów sondujących localhost.
