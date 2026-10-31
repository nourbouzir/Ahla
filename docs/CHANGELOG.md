# Changelog

All notable changes to this project. Newest at the top. Keep entries short and
human — one line per change. Based on [Keep a Changelog](https://keepachangelog.com).

Categories: **Added**, **Changed**, **Fixed**, **Removed**.

## [Unreleased]

### Added
- Phase 0 foundation: reconciled SRS FV/SDD decisions and ID mapping, documented ownership/review workflow, added configurable module contracts and fakes, and completed identity/catalog plus booking/payment/messaging/notification/audit schemas with factories and tests (#2–#6).
- Social-first Ahla demo: discover Tunisian locals, inspect profiles and interests, pass/connect, accept a friend request, chat with persistent demo messages, explore an interactive Tunisia map, and browse local experiences.
- A persistent, editable traveller profile with connection and activity cards, plus mobile bottom navigation and a full-screen phone chat with an accessible conversation list.
- An accurate Tunisia outline and islands from Natural Earth 1:50m country-boundary data, with city pins placed from geographic coordinates (Natural Earth public domain data).
- A database-backed discovery page for verified local hosts, with city, language, interest, rating and price filters, weighted match explanations, English/French/Arabic UI, demo listings, and `GET /api/experiences`.
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
- ADR 0004 (reconcile SRS FV and the SDD: host and booking states, payment flow, refunds, alternative hosts, scope of v1) and `docs/requirements/ID-mapping.md` (old SRS/SDD ids -> SRS FV ids) (issue #2, P0-2).

### Changed
- `AI_CONTEXT.md` section 6 and `docs/architecture.md` (states, ER diagram, jobs, flows, open questions) now follow ADR 0004; ADR 0003 is Accepted.
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
