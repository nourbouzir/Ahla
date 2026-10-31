#!/usr/bin/env bash
# Daily backup of the Ahla database and the private files (ID evidence).
#
#   scripts/backup.sh [backup-dir]
#
# Reads DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD from the environment or from .env.
# Writes   <dir>/<timestamp>/db.sql.gz, files.tar.gz and SHA256SUMS, then removes backups
# older than AHLA_BACKUP_KEEP_DAYS (default 14). Run it from cron, for example:
#   30 2 * * * cd /var/www/ahla && scripts/backup.sh /var/backups/ahla >> storage/logs/backup.log 2>&1
# Copy the newest folder off the server afterwards (another machine or object storage):
# a backup that lives only on the same disk is not a backup.
set -euo pipefail

cd "$(dirname "$0")/.."
DEST_ROOT="${1:-storage/backups}"
KEEP_DAYS="${AHLA_BACKUP_KEEP_DAYS:-14}"
DUMP="${MYSQLDUMP:-mysqldump}"

# Read a value from .env when it is not already in the environment.
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
DB_NAME=$(env_value DB_DATABASE)
DB_USER=$(env_value DB_USERNAME)
DB_PASS=$(env_value DB_PASSWORD)
[ -n "$DB_NAME" ] || { echo "DB_DATABASE is not set" >&2; exit 1; }

STAMP=$(date +%Y%m%d-%H%M%S)
OUT="$DEST_ROOT/$STAMP"
mkdir -p "$OUT"

echo "[$STAMP] dumping database $DB_NAME"
# --single-transaction: a consistent snapshot without locking the site.
MYSQL_PWD="$DB_PASS" "$DUMP" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
  --single-transaction --no-tablespaces --routines --triggers --default-character-set=utf8mb4 "$DB_NAME" | gzip -9 > "$OUT/db.sql.gz"

echo "[$STAMP] archiving private files"
if [ -d storage/app/private ]; then
  tar -czf "$OUT/files.tar.gz" -C storage/app private
else
  tar -czf "$OUT/files.tar.gz" --files-from /dev/null
fi

( cd "$OUT" && sha256sum db.sql.gz files.tar.gz > SHA256SUMS )
echo "[$STAMP] done: $OUT ($(du -sh "$OUT" | cut -f1))"

# Retention: delete timestamped folders older than KEEP_DAYS.
find "$DEST_ROOT" -mindepth 1 -maxdepth 1 -type d -mtime "+$KEEP_DAYS" -exec rm -rf {} + 2>/dev/null || true
