#!/bin/sh
# Consistent snapshot of the SQLite database, safe while the app is running.
# Run on the server in the deploy directory: ./backup.sh [target-dir]
set -e

target=${1:-backups}
file="db-$(date +%Y%m%d-%H%M%S).sqlite"

mkdir -p "$target"
docker compose exec -T app sqlite3 /app/storage/database/database.sqlite ".backup /app/storage/backups/$file"
docker compose cp "app:/app/storage/backups/$file" "$target/$file"
docker compose exec -T app rm "/app/storage/backups/$file"

echo "$target/$file"
