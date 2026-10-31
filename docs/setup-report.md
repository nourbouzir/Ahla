# Lab 6 — Setup Report

**Project:** AHLA — Local Guide Matching Platform
**Course:** CS 488 — AI-First Software Engineering
**Date:** 2026-10-30 (closed out 2026-10-31)

## 1. Repository and members

- **Repo URL:** https://github.com/nourbouzir/Ahla

| Member | GitHub username | Role |
|--------|-----------------|------|
| Salma Gzara | SalmaGz | Booking, Payment, Messaging, contracts, reliability |
| Nour Bouzir | nourbouzir | Auth, Verification, Experience, Matching, Notification, layout, security, deployment |

Both members are collaborators.

## 2. Chosen stack

PHP 8.3 + Laravel 12, MySQL 8, Blade + Tailwind CSS 4 + plain JavaScript, PHPUnit.
**Why:** a layered modular monolith keeps the money and limit rules (max 2 confirmed
bookings per host per day, no overlap, payment tied to booking) in one database
transaction, and the team already knows Laravel — see
[ADR 0002](decisions/0002-use-laravel-modular-monolith.md). The payment flow is in
[ADR 0003](decisions/0003-authorise-then-capture-with-locked-transactions.md).

## 3. Design brought into the repo

- `docs/architecture.md` — component diagram, ER diagram, host and booking state machines, sequence diagrams, the AI-assisted component, and one section per phase describing the system as built.
- `docs/requirements/` — SRS FV (current baseline), SRS v1.0 and the SDD as source documents; `docs/traceability.md` maps every requirement to a test.
- Six ADRs: 0001 (record decisions), 0002 (modular monolith), 0003 (authorise then capture), 0004 (SRS FV / SDD reconciliation), 0005 (payment provider), 0006 (translation provider).

## 4. Evidence

- **Merged PRs:** by Nour #44 to #47, #49 and #55 to #58 (one PR per phase); by Salma #52 (Phase 0 integration). All CI runs were green (PHP tests, lint, MySQL concurrency tests).
- **Screenshots (merged PR, green CI):** to be added by the team as `docs/img/merged-pr.png` and `docs/img/ci-green.png`; a screenshot cannot be produced from the command line.
- **Branch protection on `main`:** not enabled. The repository is public now, so the plan no longer blocks it. Recommended: require a PR, the **CI** check and one approval. This is the owner's decision (with two people, "one approval" stops an author from merging alone).

## 5. How the AI assistants are configured

`AI_CONTEXT.md` is the single source of truth; `CLAUDE.md`,
`.github/copilot-instructions.md`, `.cursor/rules/project.mdc` and `.cursorrules`
all point to it. It holds the project summary, the exact stack, the folder layout,
the booking/payment/privacy conventions, a "what not to do" list (no Candidate
features, no direct calls to external services), a glossary and the decisions that
settle the SDD / SRS FV differences.

**Example check to run once:** ask Copilot Chat or Claude *"where would I add the rule that a host can have at most 2 confirmed bookings per day, and what must I follow?"* A good answer names `BookingService` (not a controller or model), says the check runs inside the locked transaction with a re-count, requires a unit test, and mentions the `audit_log` entry.

## 6. Status of the original to-do list

- [x] Laravel skeleton, modules, CI, tests, lint (issue #1)
- [x] SDD reconciled with SRS FV (ADR 0004, `docs/requirements/ID-mapping.md`)
- [x] Payment provider decision (ADR 0005: sandbox gateway in v1, ClicToPay adapter next)
- [x] Open questions of SRS FV answered, or given a default with an owner (ADR 0004 table)
- [x] README and architecture match the as-built system
- [ ] Screenshots of a merged PR and a green CI run (team)
- [ ] Branch protection on `main` (owner decision, see section 4)
- [ ] Production deployment (issue #41): prepared in `docs/deployment.md`, needs a server
- [ ] Usability test with six real tourists (issue #42): script ready in `docs/usability-test.md`
- [ ] Real payment adapter: needs the bank merchant contract (ADR 0005)
- [ ] Translation service running, then measure NFR-04 (ADR 0006)
- [ ] Production load numbers: run `tests/load/ahla.js` on the deployed server (`docs/performance.md`)
