#!/usr/bin/env bash
# Optymalizacja Laravel na produkcji (SeoHost) — uruchom w katalogu app projektu pnedu.
# Przykład: cd ~/domains/pnedu.pl/app && bash scripts/production-optimize.sh

set -euo pipefail

echo "==> Sprawdzanie SESSION_DRIVER i CACHE_STORE..."
if grep -q '^SESSION_DRIVER=database' .env 2>/dev/null; then
    echo "    UWAGA: SESSION_DRIVER=database — rozważ SESSION_DRIVER=file (Faza 3)."
fi
if grep -q '^CACHE_STORE=database' .env 2>/dev/null || grep -q '^CACHE_DRIVER=database' .env 2>/dev/null; then
    echo "    UWAGA: cache w bazie — rozważ CACHE_STORE=file (Faza 3)."
fi

echo "==> Katalogi storage..."
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

echo "==> Czyszczenie starych cache..."
php artisan optimize:clear

echo "==> Budowanie cache produkcyjnego..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Gotowe. Sprawdź: php artisan about"
