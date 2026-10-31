#!/usr/bin/env bash
# Restore a backup made by scripts/backup.sh.
#
#   scripts/restore.sh <backup-folder> [target-database] [--with-files]
#
# By default the dump is loaded into the target database (default: DB_DATABASE from .env) and the
# private files are NOT touched. Rehearse into a spare database first, for example:
#   scripts/restore.sh storage/backups/20261101-023000 ahla_restore_check
# --with-files unpacks files.tar.gz over storage/app/private (use it on a real recovery).
set -euo pipefail

cd "$(dirname "$0")/.."
SRC="${1:?usage: restore.sh <backup-folder> [target-database] [--with-files]}"
TARGET="${2:-}"
WITH_FILES=false
for arg in "$@"; do [ "$arg" = "--with-files" ] && WITH_FILES=true; done
[ "$TARGET" = "--with-files" ] && TARGET=""
MYSQL="${MYSQL_BIN:-mysql}"

env_value() {
  local name="$1" default="${2:-}"
  if [ -n "${!name:-}" ]; then printf '%s' "${!name}"; return; fi
  if [ -f .env ]; then
    local line
    line=$(grep -E "^${name}=" .env | tail -n1 || true)
    if [ -n "$line" ]; then
      line="${line#*=}"; line="${line%\"}"; line="${line#\"}"
      printf '%s' "$line"; return
    fi
  fi
  printf '%s' "$default"
}

DB_HOST=$(env_value DB_HOST 127.0.0.1)
DB_PORT=$(env_value DB_PORT 3306)
DB_USER=$(env_value DB_USERNAME)
DB_PASS=$(env_value DB_PASSWORD)
DB_NAME="${TARGET:-$(env_value DB_DATABASE)}"

echo "Checking the backup is intact"
( cd "$SRC" && sha256sum -c SHA256SUMS )

echo "Restoring the database into $DB_NAME"
MYSQL_PWD="$DB_PASS" "$MYSQL" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
  -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4;"
gunzip -c "$SRC/db.sql.gz" | MYSQL_PWD="$DB_PASS" "$MYSQL" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" "$DB_NAME"

if $WITH_FILES; then
  echo "Restoring private files"
  mkdir -p storage/app
  tar -xzf "$SRC/files.tar.gz" -C storage/app
fi

echo "Restore finished. Now run: php artisan migrate --force && php artisan about"
