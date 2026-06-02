#!/usr/bin/env bash
set -euo pipefail

DB_HOST="${OHRM_DB_HOST:-db}"
DB_PORT="${OHRM_DB_PORT:-3306}"
DB_NAME="${OHRM_DB_NAME:-orangehrm_mysql}"
DB_ROOT_PW="${OHRM_DB_ROOT_PASSWORD:-root}"
CONF_FILE="/var/www/html/lib/confs/Conf.php"

echo "[entrypoint] waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
for i in $(seq 1 60); do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -uroot -p"$DB_ROOT_PW" --silent 2>/dev/null; then
        echo "[entrypoint] MySQL is up."
        break
    fi
    sleep 2
    [ "$i" = "60" ] && { echo "[entrypoint] MySQL not reachable, aborting."; exit 1; }
done

if [ ! -f "$CONF_FILE" ]; then
    echo "[entrypoint] OrangeHRM not installed yet, running CLI installer ..."
    # Point the installer config at the docker DB service.
    sed -i \
        -e "s/^  hostName:.*/  hostName: ${DB_HOST}/" \
        -e "s/^  hostPort:.*/  hostPort: ${DB_PORT}/" \
        -e "s/^  databaseName:.*/  databaseName: ${DB_NAME}/" \
        -e "s/^  privilegedDatabasePassword:.*/  privilegedDatabasePassword: ${DB_ROOT_PW}/" \
        /var/www/html/installer/cli_install_config.yaml
    php installer/cli_install.php
    chown -R www-data:www-data /var/www/html/lib/confs /var/www/html/src/cache /var/www/html/src/log /var/www/html/src/config
    echo "[entrypoint] install complete. Admin user: Admin / Ohrm@1423"
else
    echo "[entrypoint] existing install detected, skipping installer."
fi

exec "$@"
