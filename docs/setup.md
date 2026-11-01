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
# then edit .env: at least DB_USERNAME / DB_PASSWORD. Local/test contract
# bindings use fakes; no payment or translation provider is selected in v1.

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

# 7. Run tests and the style check (the same two commands CI runs)
composer test
composer lint        # composer lint:fix repairs style problems automatically
```

Tests use an in-memory SQLite database (set in `phpunit.xml`), so they need no
MySQL. Running the app and `php artisan migrate` do need MySQL from step 5.
The Phase 0 schema is split by module migrations and can be created from a clean
database with `php artisan migrate:fresh --seed`. The test suite migrates an
in-memory SQLite database. Production must explicitly configure concrete
implementations for every interface in `config/ahla.php`; the app fails at boot
if a binding is missing or does not implement its contract.

### How the project skeleton was made (issue #1, already done)

The repo is a **Laravel 12** application (Laravel 11 was planned, but Composer
blocks every 11.x release because of security advisories — see ADR 0002). It was
created with `composer create-project laravel/laravel "^12.0"` in a scratch
folder and copied in, keeping our own `.env.example`, `.gitignore`, `.editorconfig`,
`.gitattributes`, `README.md` and `LICENSE`. You do not need to repeat this.

### Working inside a module

Feature code lives in `app/Modules/<Module>/` (nine modules, listed in
`app/Modules/Modules.php`). Each module is self-contained: put routes in its
`routes/web.php` or `routes/api.php`, migrations in `Database/Migrations/`, views in
`Views/` (`view('booking::name')`), strings in `lang/{en,fr,ar}/` (`__('booking::messages.key')`)
and seed data in its `Database/Seeders/` class. No file outside the module needs
editing, which is what lets two people work in parallel without merge conflicts.
To add a module, copy an existing folder, rename the classes and add its name to
`app/Modules/Modules.php` — `tests/Feature/ModulesTest.php` fails if you forget.

Cross-module interfaces and scalar-ID domain events live in `app/Contracts/`
and `app/Events/`. Local fake bindings support explicit success, decline, and
timeout behavior for payment tests; the provider remains unselected (ADR 0004).

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
