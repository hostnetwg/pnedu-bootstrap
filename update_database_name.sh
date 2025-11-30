#!/bin/bash
# Skrypt do zmiany nazwy bazy danych z admpnedu na pneadm

set -e

echo "🔄 Zmiana nazwy bazy danych: admpnedu → pneadm"
echo ""

# Sprawdź czy baza admpnedu istnieje
echo "📋 Sprawdzanie istniejących baz..."
EXISTS=$(sail mysql -e "SHOW DATABASES LIKE 'admpnedu';" | grep -c admpnedu || true)

if [ "$EXISTS" -eq 0 ]; then
    echo "⚠️  Baza 'admpnedu' nie istnieje. Sprawdzam czy 'pneadm' już istnieje..."
    PNEADM_EXISTS=$(sail mysql -e "SHOW DATABASES LIKE 'pneadm';" | grep -c pneadm || true)
    if [ "$PNEADM_EXISTS" -gt 0 ]; then
        echo "✅ Baza 'pneadm' już istnieje. Prawdopodobnie migracja już została wykonana."
        exit 0
    else
        echo "❌ Ani 'admpnedu' ani 'pneadm' nie istnieją. Sprawdź konfigurację."
        exit 1
    fi
fi

# Utwórz backup
echo "💾 Tworzenie backupu bazy admpnedu..."
BACKUP_FILE="backup_admpnedu_$(date +%Y%m%d_%H%M%S).sql"
sail mysqldump -u sail -ppassword admpnedu > "$BACKUP_FILE"
echo "✅ Backup utworzony: $BACKUP_FILE"

# Utwórz nową bazę
echo "📦 Tworzenie nowej bazy pneadm..."
sail mysql -e "CREATE DATABASE IF NOT EXISTS pneadm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "✅ Baza pneadm utworzona"

# Skopiuj dane
echo "📋 Kopiowanie danych z admpnedu do pneadm..."
sail mysqldump -u sail -ppassword admpnedu | sail mysql -u sail -ppassword pneadm
echo "✅ Dane skopiowane"

# Sprawdź czy kopiowanie się powiodło
echo "🔍 Sprawdzanie liczby tabel..."
TABLES_ADM=$(sail mysql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'admpnedu';" | tail -n 1)
TABLES_PNE=$(sail mysql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'pneadm';" | tail -n 1)

echo "   Tabele w admpnedu: $TABLES_ADM"
echo "   Tabele w pneadm: $TABLES_PNE"

if [ "$TABLES_ADM" -eq "$TABLES_PNE" ] && [ "$TABLES_PNE" -gt 0 ]; then
    echo "✅ Liczba tabel się zgadza!"
    echo ""
    echo "⚠️  WAŻNE: Przetestuj aplikację przed usunięciem starej bazy!"
    echo "   Aby usunąć starą bazę, uruchom:"
    echo "   sail mysql -e \"DROP DATABASE admpnedu;\""
else
    echo "⚠️  Uwaga: Liczba tabel się nie zgadza. Sprawdź ręcznie."
fi

echo ""
echo "✅ Migracja zakończona!"
echo "📝 Następne kroki:"
echo "   1. Zaktualizuj plik .env (zmień DB_ADMPNEDU_* na DB_PNEADM_*)"
echo "   2. Uruchom: sail artisan config:clear"
echo "   3. Przetestuj aplikację"
echo "   4. Jeśli wszystko działa, możesz usunąć starą bazę admpnedu"
