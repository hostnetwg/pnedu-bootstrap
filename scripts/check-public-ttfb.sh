#!/usr/bin/env bash
# Szybki pomiar TTFB publicznych tras (Faza 7). Uruchom z hosta lub serwera.
# Uwaga: /dashboard/* bez ciasteczka sesji zwróci redirect do logowania — mierzy tylko ten redirect.

set -euo pipefail

BASE_URL="${1:-https://pnedu.pl}"

paths=(
  "/"
  "/login"
  "/szkolenia-indywidualne"
)

echo "TTFB check for ${BASE_URL}"
echo "----------------------------------------"

for path in "${paths[@]}"; do
  url="${BASE_URL%/}${path}"
  curl -s -o /dev/null -w \
    "${path}\tTTFB: %{time_starttransfer}s\tTotal: %{time_total}s\tHTTP: %{http_code}\n" \
    "$url"
done

echo "----------------------------------------"
echo "Dashboard (redirect bez sesji):"
curl -s -o /dev/null -w \
  "/dashboard/szkolenia\tTTFB: %{time_starttransfer}s\tTotal: %{time_total}s\tHTTP: %{http_code}\n" \
  "${BASE_URL%/}/dashboard/szkolenia"
