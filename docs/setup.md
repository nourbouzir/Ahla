# Full Setup Guide

Everything a new teammate (or your grader) needs to run the project from scratch.
Keep this accurate — test it by following it on a clean machine when you can.

## Prerequisites

- Git (on Windows, Git Bash is needed for the `scripts/*.sh` files)
- PHP 8.3 with extensions `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `xml`
- Composer 2
- Node 20+ and npm (front-end assets via Vite)
- MySQL 8 — or Docker + Laravel Sail, or XAMPP, on each laptop
- A code editor with an AI assistant (VS Code + GitHub Copilot recommended)

## Steps

```bash
# 1. Clone
git clone <your-repo-url>
cd ahla

# 2. One-time project setup (git hooks, etc.)
./scripts/setup.sh

# 3. Environment variables
cp .env.example .env
# then edit .env: at least DB_USERNAME / DB_PASSWORD. Leave PAYMENT_GATEWAY=fake
# and MAIL_MAILER=log for local work — nothing is really charged or sent.

# 4. Install dependencies
composer install
npm install
php artisan key:generate

# 5. Database setup
# create an empty database named in DB_DATABASE (default ahla_db), then:
php artisan migrate --seed

# 6. Run
php artisan serve          # http://localhost:8000
php artisan queue:work     # second terminal: emails, expiry, notifications
npm run dev                # third terminal: front-end assets (if you change CSS/JS)
php artisan schedule:work  # runs the scheduled jobs locally (ExpireRequests, ...)

# 7. Run tests
php artisan test
```

### First-time project skeleton (sprint 1, one teammate only)

The repo currently holds the template, docs and CI — not the Laravel code. To
create the skeleton without clobbering the existing files:

```bash
composer create-project laravel/laravel ../ahla-skeleton "11.*"
# copy everything from ../ahla-skeleton into this repo EXCEPT .env.example,
# .gitignore and README.md (keep ours), then:
composer install && php artisan key:generate && php artisan test
```

Commit it on a branch (`chore/laravel-skeleton`) and open a PR so CI runs the
PHP job for the first time. Update `docs/CHANGELOG.md` in the same PR.

## Troubleshooting

_Add problems and fixes as you hit them, so teammates don't lose an hour to the
same issue._

- **Problem:** `./scripts/setup.sh` won't run on Windows.
  **Fix:** run it in Git Bash, or `bash scripts/setup.sh`.
- **Problem:** `permission denied` on a script.
  **Fix:** `chmod +x scripts/*.sh`.
- **Problem:** `SQLSTATE[HY000] [2002] Connection refused` on `migrate`.
  **Fix:** start MySQL and check `DB_HOST` / `DB_PORT` in `.env`.
- **Problem:** `No application encryption key has been specified`.
  **Fix:** run `php artisan key:generate`.
- **Problem:** Arabic pages look left-to-right.
  **Fix:** check `APP_LOCALE=ar` / the language switcher and that the RTL stylesheet is loaded.
