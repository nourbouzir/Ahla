# Changelog

All notable changes to this project. Newest at the top. Keep entries short and
human — one line per change. Based on [Keep a Changelog](https://keepachangelog.com).

Categories: **Added**, **Changed**, **Fixed**, **Removed**.

## [Unreleased]

### Added
- Guide space (`/host/dashboard`): overview with requests to answer (live countdown), next-7-days agenda, earnings chart and setup checklist; client list with stages, history and private notes; inbox of booking chats with unread counts; month calendar with days off; earnings page with CSV export. Admin space (`/admin`): platform overview with an attention list, user list and detail with account locking, guide monitoring, revenue page (paid, refunded, net volume, indicative revenue) with CSV export. Demo logins on the login page (development only) and 40 seeded past bookings so every chart has data (ADR 0007).

### Changed
- Full redesign: neutral product-style layout (Inter and Noto Sans Arabic, one palette of door blue, whitewash, terracotta, gold and olive), sticky header, side menus for the guide and admin spaces, scroll and load animations that switch off for reduced motion, restored arched-door logo.

### Removed
- Clean-up: 36 empty placeholder files in folders that now hold real files, the two default Laravel example tests, the unused axios bundle (front-end JavaScript went from 59 kB to 3 kB), an unused CSS class and an unused method.
- The browser-only social discovery demo (mock locals, chat and map): it used fake data and Candidate-style features outside the plan. Real search replaces it in Phase 3.

### Added
- Phase 5: demo seed data and walkthrough (`docs/demo.md`), acceptance tests named AT-FR-xx and `docs/traceability.md` (checked by a test), per-environment binding test, FR-04 phone-sharing choice, admin bookings overview, payment recovery after a lost gateway answer, sandbox fault injection, soak test, backup and restore scripts with a restore drill and RPO/RTO proposal (`docs/runbook.md`), k6 and PHP load scripts with measured numbers (`docs/performance.md`), deployment files and guide (`docs/deployment.md`, `deploy/`), usability test script (`docs/usability-test.md`) (#36-#43).
- Phase 4: in-app messaging per booking with translation (ADR 0006) and a chat page, in-app and e-mail notifications for booking, payment, cancellation, refund and message events, language-parity and route-access tests in CI, security headers and HTTPS/cookie hardening (#30-#35).

### Fixed
- The private disk is no longer served over HTTP (`serve` was on by default).
- Phase 3: host search with filters and pagination (`GET /api/search`, `/search`), weighted recommendation with human-readable reasons, host profile pages, the real alternative-host finder, cancellation that survives refund failures, and lifecycle jobs (expire, auto-complete, reconcile payments, purge ID documents) (#23-#29).
- Phase 2: host verification (encrypted ID upload, admin review with video call, suspend/reinstate), host state machine and Verified-only guard, experiences and weekly availability management, booking request/accept/decline/cancel with payment capture, overlap and 2-per-day rules under a row lock, booking screens for tourists and hosts, MySQL concurrency tests in CI (#15-#22).
- Phase 1: base layout with EN/FR/AR and right-to-left (Tailwind), registration, login, logout and password reset (rate limited), role/action matrix as Gate abilities, tourist and host profiles with private phone and email, payment engine (sandbox gateway, `PaymentService`, refund policies, signed webhook), `AuditLogger` and ADR 0005 (#7-#14).
- Phase 0 foundation: reconciled SRS FV/SDD decisions and ID mapping, documented ownership/review workflow, added configurable module contracts and fakes, and completed identity/catalog plus booking/payment/messaging/notification/audit schemas with factories and tests (#2–#6).
- Host search ranking code (`MatchingService`, weights language 0.4, interests 0.3, proximity 0.2, rating 0.1) and starter hosts for the catalogue.
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

### Removed
- The browser-only social discovery demo (mock locals, chat and map): it used fake data and Candidate-style features outside the plan. Real search replaces it in Phase 3.

### Added
- Study-partner matching by course (#12)

### Fixed
- Crash when the search box was empty (#15)
-->
