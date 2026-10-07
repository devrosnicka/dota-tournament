#!/bin/sh
set -e

cd /app

# The storage volume is empty on the first start.
mkdir -p \
    storage/app/public \
    storage/backups \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    "$(dirname "$DB_DATABASE")"
[ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"

php artisan migrate --force
php artisan optimize

exec "$@"
