#!/usr/bin/env bash
# Deploy the current main branch on the server:  scripts/deploy.sh
# Run it as the web user inside /var/www/ahla. Secrets live only in the server's .env (never in git).
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo ".env is missing: copy .env.example and fill it in first (docs/deployment.md)" >&2; exit 1; }
grep -q '^APP_ENV=production' .env || { echo "APP_ENV must be production" >&2; exit 1; }
grep -q '^APP_DEBUG=false' .env || { echo "APP_DEBUG must be false" >&2; exit 1; }

echo "Backing up before the deploy"
scripts/backup.sh /var/backups/ahla

php artisan down --retry=60
git fetch --quiet origin main
git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan storage:link || true
php artisan optimize
php artisan queue:restart
php artisan up

scripts/smoke-test.sh "$(grep '^APP_URL=' .env | cut -d= -f2-)"
