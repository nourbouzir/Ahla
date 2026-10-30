# Changelog

All notable changes to this project. Newest at the top. Keep entries short and
human — one line per change. Based on [Keep a Changelog](https://keepachangelog.com).

Categories: **Added**, **Changed**, **Fixed**, **Removed**.

## [Unreleased]

### Added
- Identity and catalog schema: users get `role`, `phone`, `preferred_language`; new tables `user_languages`, `user_interests`, `host_profiles`, `verification_requests`, `experiences`, `availability`, `blocked_dates`, with models, the `Role` and `HostStatus` enums and factories (issue #5, P0-5).
- `GET /api/health` (JSON `{"status":"ok"}`) with a feature test; `routes/api.php` is registered in `bootstrap/app.php` (no Sanctum needed).
- `composer test` and `composer lint` / `composer lint:fix` (Laravel Pint); CI now runs the lint step before the tests (issue #1, P0-1).
- Module layout: nine self-contained modules under `app/Modules/` (Auth, Verification, Experience, Matching, Booking, Payment, Messaging, Notification, Admin), each with its own `ModuleServiceProvider`, routes, migrations, views, language files and seeder; `App\Modules\Modules` is the single module list (issue #1, P0-1).
- Laravel 12 application skeleton (`app/`, `config/`, `routes/`, `database/`, `tests/`, `composer.json`, `phpunit.xml`); tests run on in-memory SQLite (issue #1, P0-1).
- Initial project scaffold from the CS 488 starter template.
- AHLA SRS (current and earlier version) and SDD under `docs/requirements/`.
- `AI_CONTEXT.md` filled in for AHLA (Laravel 12 / PHP 8.3 / MySQL 8) so Copilot, Claude and Cursor share one context.
- `docs/architecture.md` with component, ER, state-machine and sequence diagrams from the SDD.
- ADR 0002 (modular monolith on Laravel) and ADR 0003 (authorise-then-capture payments, proposed).
- PHP/Laravel job in CI (runs once `composer.json` exists); Laravel `.env.example`.
- `docs/setup-report.md` (Lab 6 report).
- `docs/PROJECT_PLAN.md`: 6 phases split into 43 GitHub issues, assigned to Nour and Salma with hand-offs and merge rules.

### Changed
- Framework is Laravel 12 instead of 11: Composer blocks every Laravel 11 release because of security advisories (see ADR 0002).
- `.gitignore`, `.editorconfig` and recommended VS Code extensions narrowed to the PHP/Node stack; the `logs/` rule is now `/logs/` so Laravel's `storage/logs` folder is kept in git.
- `scripts/update-structure.sh` lists files through git, so ignored files (`vendor/`, `.env`, caches) never appear in the README tree.
- Pint applied to three skeleton files (`User.php`, `config/auth.php`, `UserFactory.php`): import style only.

### Removed
- `stack-appendices/` (no longer needed after choosing the stack).

<!--
Example of a real entry once you start building:

## [Unreleased]

### Added
- Study-partner matching by course (#12)

### Fixed
- Crash when the search box was empty (#15)
-->
