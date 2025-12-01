#!/bin/bash
set -e

echo "Creating database 'pnedu' if it does not exist..."

mysql --user=root --password="${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`pnedu\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL ON \`pnedu\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL

echo "Database 'pnedu' created successfully!"

