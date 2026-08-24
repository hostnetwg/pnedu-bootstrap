# Ścieżki produkcyjne (SeoHost) — pnedu.pl

Kanoniczny runbook (oba projekty, PHP, **Sail tylko dev**):  
**[`pneadm/docs/deploy/PRODUCTION_PATHS.md`](../../pneadm/docs/deploy/PRODUCTION_PATHS.md)** w repozytorium pneadm (obok w workspace).

## Laravel Sail — tylko dev

**Na produkcji nie używamy `sail`.** Sail = Docker lokalnie (WSL2). Na SeoHost:

```bash
/opt/alt/php82/usr/bin/php artisan …
```

| Dev | Prod |
|-----|------|
| `sail artisan migrate` | `/opt/alt/php82/usr/bin/php artisan migrate --force` |
| `sail test` | testy tylko lokalnie / CI |

## pnedu.pl — typowy deploy

```bash
cd ~/domains/pnedu.pl/app
# pełna: /home/srv66127/domains/pnedu.pl/app
git pull origin main
/opt/alt/php82/usr/bin/php artisan optimize:clear
/opt/alt/php82/usr/bin/php artisan view:clear
/opt/alt/php82/usr/bin/php artisan config:cache
/opt/alt/php82/usr/bin/php artisan route:cache
/opt/alt/php82/usr/bin/php artisan view:cache
```

Migracje bazy **pnedu** (domyślna): ten sam `php artisan migrate --force` w katalogu `app`.  
Migracje bazy **pneadm** (tabele szkoleń, uczestników, embed): w katalogu **adm** — patrz runbook pneadm.

## Powiązane

- Kolejka prod: `pneadm/docs/deploy/PRODUCTION_QUEUE_OPS.md`
- Forma komunikacji: `docs/AI_HUMAN_COMMUNICATION.md` (kanon: `pneadm/docs/AI_HUMAN_COMMUNICATION.md`)
