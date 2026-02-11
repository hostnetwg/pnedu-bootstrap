#!/bin/bash
set -e

echo "Creating pneadm database..."

# Użyj root password jeśli jest ustawione, w przeciwnym razie spróbuj bez hasła
if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
        CREATE DATABASE IF NOT EXISTS \`pneadm\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        GRANT ALL PRIVILEGES ON \`pneadm\`.* TO '${MYSQL_USER}'@'%';
        FLUSH PRIVILEGES;
EOSQL
else
    mysql -u root <<-EOSQL
        CREATE DATABASE IF NOT EXISTS \`pneadm\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        GRANT ALL PRIVILEGES ON \`pneadm\`.* TO '${MYSQL_USER}'@'%';
        FLUSH PRIVILEGES;
EOSQL
fi

echo "pneadm database created successfully!"

