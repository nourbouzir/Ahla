# Changelog

All notable changes to this project. Newest at the top. Keep entries short and
human — one line per change. Based on [Keep a Changelog](https://keepachangelog.com).

Categories: **Added**, **Changed**, **Fixed**, **Removed**.

## [Unreleased]

### Added
- Initial project scaffold from the CS 488 starter template.
- AHLA SRS (current and earlier version) and SDD under `docs/requirements/`.
- `AI_CONTEXT.md` filled in for AHLA (Laravel 11 / PHP 8.3 / MySQL 8) so Copilot, Claude and Cursor share one context.
- `docs/architecture.md` with component, ER, state-machine and sequence diagrams from the SDD.
- ADR 0002 (modular monolith on Laravel) and ADR 0003 (authorise-then-capture payments, proposed).
- PHP/Laravel job in CI (runs once `composer.json` exists); Laravel `.env.example`.
- `docs/setup-report.md` (Lab 6 report).
- `docs/PROJECT_PLAN.md`: 6 phases split into 43 GitHub issues, assigned to Nour and Salma with hand-offs and merge rules.
- ADR 0004 (reconcile SRS FV and the SDD: host and booking states, payment flow, refunds, alternative hosts, scope of v1) and `docs/requirements/ID-mapping.md` (old SRS/SDD ids -> SRS FV ids) (issue #2, P0-2).

### Changed
- `AI_CONTEXT.md` section 6 and `docs/architecture.md` (states, ER diagram, jobs, flows, open questions) now follow ADR 0004; ADR 0003 is Accepted.
- `.gitignore`, `.editorconfig` and recommended VS Code extensions narrowed to the PHP/Node stack.

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
