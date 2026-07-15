#!/usr/bin/env bash
# Statyczne pliki /media/pneadm/* → storage/app/public pneadm (omija Laravel / lsphp).
#
# Produkcja (SeoHost):
#   cd ~/domains/pnedu.pl/app && bash scripts/prod-link-pneadm-media.sh
#
# Lokalnie (sąsiednie katalogi pnedu + pneadm):
#   bash scripts/prod-link-pneadm-media.sh
#
# Własne ścieżki:
#   bash scripts/prod-link-pneadm-media.sh /path/to/pnedu /path/to/pneadm
#
# Po linkach LiteSpeed/Apache serwuje plik przez RewriteCond %{REQUEST_FILENAME} !-f
# zanim trafi do index.php. Kontroler PneadmMediaController zostaje jako fallback.

set -euo pipefail

PNEDU_ROOT="${1:-}"
PNEADM_ROOT="${2:-}"

if [[ -z "$PNEDU_ROOT" ]]; then
    PNEDU_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fi

if [[ -z "$PNEADM_ROOT" ]]; then
    if [[ -d "${HOME}/domains/adm.pnedu.pl/pneadm" ]]; then
        PNEADM_ROOT="${HOME}/domains/adm.pnedu.pl/pneadm"
    elif [[ -d "$(dirname "$PNEDU_ROOT")/pneadm" ]]; then
        PNEADM_ROOT="$(cd "$(dirname "$PNEDU_ROOT")/pneadm" && pwd)"
    else
        echo "ERROR: nie znaleziono katalogu pneadm. Podaj ścieżkę:" >&2
        echo "  bash scripts/prod-link-pneadm-media.sh /path/to/pnedu /path/to/pneadm" >&2
        exit 1
    fi
fi

PNEDU_PUBLIC="${PNEDU_ROOT}/public"
PNEADM_STORAGE="${PNEADM_ROOT}/storage/app/public"
MEDIA_ROOT="${PNEDU_PUBLIC}/media/pneadm"

if [[ ! -d "$PNEDU_PUBLIC" ]]; then
    echo "ERROR: brak public/: $PNEDU_PUBLIC" >&2
    exit 1
fi

if [[ ! -d "$PNEADM_STORAGE" ]]; then
    echo "ERROR: brak storage/app/public pneadm: $PNEADM_STORAGE" >&2
    exit 1
fi

# Whitelist jak w App\Support\PneadmMedia::isAllowedPath — nie linkujemy całego storage.
# Format: "względny_katalog_w_storage → względny_pod_media/pneadm"
declare -a LINKS=(
    "courses/images:courses/images"
    "course_series:course_series"
    "online-courses/images:online-courses/images"
    "instructors:instructors"
)

echo "==> pnedu public:  $PNEDU_PUBLIC"
echo "==> pneadm storage: $PNEADM_STORAGE"
echo "==> media target:   $MEDIA_ROOT"
echo

linked=0
skipped=0

for entry in "${LINKS[@]}"; do
    src_rel="${entry%%:*}"
    dest_rel="${entry##*:}"
    src="${PNEADM_STORAGE}/${src_rel}"
    dest="${MEDIA_ROOT}/${dest_rel}"

    if [[ ! -e "$src" ]]; then
        echo "SKIP (brak źródła): ${src_rel}"
        skipped=$((skipped + 1))
        continue
    fi

    mkdir -p "$(dirname "$dest")"

    if [[ -L "$dest" ]]; then
        current="$(readlink -f "$dest" 2>/dev/null || readlink "$dest")"
        target="$(readlink -f "$src" 2>/dev/null || echo "$src")"
        if [[ "$current" == "$target" ]]; then
            echo "OK (już powiązane): /media/pneadm/${dest_rel}"
            linked=$((linked + 1))
            continue
        fi
    elif [[ -e "$dest" ]]; then
        echo "ERROR: istnieje nie-symlink, nie nadpisuję: $dest" >&2
        exit 1
    fi

    ln -sfn "$src" "$dest"
    echo "LINK: /media/pneadm/${dest_rel} → ${src}"
    linked=$((linked + 1))
done

echo
echo "==> Gotowe: powiązano ${linked}, pominięto ${skipped}."
echo "    Weryfikacja: curl -sI \"https://pnedu.pl/media/pneadm/courses/images/\$(ls \"$PNEADM_STORAGE/courses/images\" | head -1)\" | head -5"
echo "    Oczekiwane: HTTP 200, szybki TTFB, bez Set-Cookie Laravel."
