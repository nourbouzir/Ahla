# AI Context — Single Source of Truth

> **What is this file?**
> This is the one place your team describes the project *to AI assistants*
> (GitHub Copilot, Claude, Cursor, Gemini, etc.). Every AI tool config in this
> repo points back to this file, so you only maintain **one** set of instructions.
>
> Keep it short, current, and honest. If the project changes, update this file in
> the same commit. Treat it like code.

---

## 1. Project summary

**Name:** AHLA — Local Guide Matching Platform
**One-liner:** A mobile-first web platform where tourists in Tunisia book a few hours with verified local hosts ("book a local, not a tour"), matched by language, interests, location and rating.
**Primary users:** Tourists (foreign or Tunisian visitors, often on a phone), Local Hosts (students, artisans, fishermen, retirees), and Admins (AHLA staff who verify hosts).
**The AI-assisted component:** Host recommendation (FR-09) with a human-readable reason for every result (FR-10). In the current design this is a weighted scoring service (`MatchingService`: language 0.4, interests 0.3, proximity 0.2, rating 0.1) whose two strongest criteria become the "why this host" tags. Whether an LLM is added on top is **undecided** — do not add one without a new ADR. Message translation (FR-19) is an external Translation Service behind a `Translator` adapter.

Link to your design docs so the assistant can go deeper:
- SRS / requirements (current baseline): `docs/requirements/Ahla_SRS_FV.docx` — **supersedes** the earlier SRS (`Ahla_SRS.docx`)
- Design (SDD): `docs/requirements/Ahla_SDD.docx` — written against the *earlier* SRS, see "Known document mismatches" below
- Architecture: [`docs/architecture.md`](docs/architecture.md)
- Decisions: [`docs/decisions/`](docs/decisions/)

## 2. Tech stack

| Layer       | Choice                                                                 |
|-------------|------------------------------------------------------------------------|
| Language(s) | PHP 8.3; plain JavaScript (no SPA framework); Blade templates; CSS with RTL support |
| Framework   | Laravel 11 (routing, Eloquent ORM, events, queues, scheduler, Gates)  |
| Database    | MySQL 8 (InnoDB); encrypted private disk for ID documents             |
| Package mgr | Composer (PHP), npm (front-end assets only, via Vite)                 |
| Testing     | PHPUnit + Laravel feature tests; k6 (load); SonarCloud (quality)      |
| Maps        | OpenStreetMap + Leaflet in the browser                                |
| Deploy      | One Linux VPS: Nginx + PHP-FPM 8.3, MySQL 8, supervised queue worker, cron running `schedule:run` every minute. Dev: Laravel Sail or XAMPP |

> The Payment Gateway provider is **not chosen yet** (open issue; must support authorise / capture / refund / payouts). Code against the `PaymentGateway` interface only.

## 3. Project structure

The repo is the Laravel application root (created in sprint 1 — see `docs/setup.md`).
Modules are groups of controller + service + model + repository; modules never touch another module's tables directly.

- `app/Modules/<Module>/` — one folder per module: `Auth`, `Verification`, `Experience`, `Matching`, `Booking`, `Payment`, `Messaging`, `Trust`, `Notification`, `Admin`
- `app/Modules/Booking/States/`, `app/Modules/Verification/States/` — State pattern classes (`BookingState`, `HostState`)
- `app/Modules/Matching/Strategies/` — `MatchingStrategy` implementations
- `app/Modules/Payment/Policies/` — `RefundPolicy` implementations; `app/Modules/Payment/Gateways/` — `PaymentGateway` adapters
- `app/Events/`, `app/Listeners/` — domain events and listeners (observer pattern)
- `app/Jobs/` — scheduled/queued jobs (`ExpireRequests`, `SendReminders`, `AutoComplete`, `ReleasePayouts`, `PublishReviews`, `PurgeIdDocuments`)
- `resources/views/`, `resources/lang/{en,fr,ar}/` — Blade views and language files
- `routes/api.php`, `routes/web.php` — JSON endpoints under `/api/*` and pages
- `database/migrations/`, `database/seeders/` — schema and seed data
- `tests/Unit/`, `tests/Feature/` — PHPUnit tests
- `docs/` — requirements, architecture, ADRs, changelog, setup report
- `scripts/` — helper scripts

## 4. Conventions the assistant MUST follow

- **Style:** follow the existing code style and `.editorconfig`. Don't reformat unrelated code. PHP follows PSR-12 (4-space indent).
- **Naming:** PHP classes `PascalCase`, methods/variables `camelCase`, DB tables/columns `snake_case`, routes `kebab-case`, language keys `snake_case`.
- **Comments:** explain *why*, not *what*. Match the density of surrounding code.
- **Dependencies:** do **not** add a new library without the team agreeing first. Prefer Laravel built-ins.
- **Secrets:** never hardcode keys/passwords. Use `.env` (which is gitignored). See `.env.example`.
- **Tests:** new features come with at least one test. Business rules (limits, refunds, matching, suspension) need unit tests (target ≥ 80% line coverage, NFR-13 in the old SRS).
- **Commits:** small and focused. Update `docs/CHANGELOG.md` when behavior changes.
- **Money:** store as `DECIMAL(10,3)` (TND has 3 decimals). Never use floats.
- **Business rules live in one place:** daily limit and overlap checks only in `BookingService`; refund amounts only in `RefundPolicy` classes; status transitions only in the State classes. Controllers stay thin.
- **Booking concurrency:** request and accept both run in one DB transaction that locks the host's bookings for that date (`SELECT … FOR UPDATE`) and re-counts before writing. Never check-then-insert outside that transaction.
- **Payments:** authorise on request, capture on host acceptance, release on expiry. Never store card data — only the gateway reference. A failed authorisation rolls the whole booking back.
- **Status changes:** every host / booking / payment status change is written to `audit_log` (actor, reason, timestamp).
- **Privacy:** ID documents go on the encrypted private disk, readable by admins only. Phone numbers stay hidden until a booking is accepted.
- **Localisation:** no hard-coded user-facing text; use `resources/lang/{en,fr,ar}`. Layouts must work right-to-left for Arabic and from 360 px wide.
- **Slow work** (email/SMS, translation, alternative-host suggestions) goes on the queue via events/listeners, not inline in a request.

## 5. What NOT to do

- Don't invent requirements — if something is unclear, ask or leave a `TODO` with your name.
- Don't implement **Candidate** features (FR-21 to FR-25 in the current SRS: like/pass, favorites, reviews, broad admin tools, social login) until the team approves them.
- Don't delete or rewrite large sections you weren't asked to touch.
- Don't commit generated files, build output, `vendor/`, `node_modules/`, or `.env`.
- Don't paste private user data or credentials into AI chats.
- Don't call external services (payment, translation) directly from controllers or models — go through the adapter interfaces.

## 6. Known document mismatches (read before implementing)

The SDD was written against SRS v1.0. The current requirements baseline is **SRS FV**, which supersedes it. Until the team reconciles them, follow SRS FV for *what* to build and the SDD for *how*:

| Topic | SDD / SRS v1.0 | SRS FV (current) |
|-------|----------------|------------------|
| Requirement IDs | FR-01…FR-26, NFR-01…NFR-13 | FR-01…FR-25, NFR-01…NFR-21 — **IDs are not interchangeable** |
| Host states | applied → verified → active, suspended, rejected, banned | Pending, Verified, Rejected, Suspended; only **Verified** hosts bookable (FR-07) |
| Platform | Web only; native apps out of scope | NFR-16 lists Android, iOS and web |
| Booking confirmation | Authorise on request, capture on accept | Booking confirmed only after gateway reports successful payment (FR-15) |
| Late host cancel | Alternatives within 10 km, up to 3 | Suggest an available host in the same city (FR-17) |
| Commission, strikes, disputes, payouts | Specified (15%, 2 strikes/30 days, …) | Not specified — treat as open |

If a task touches one of these rows, ask first and record the answer in an ADR.

## 7. Glossary / domain terms

- **Tourist** — visitor (foreign or Tunisian) who books an experience.
- **Host** — local person who offers experiences after being verified.
- **Experience** — a short activity offered by a host (1–6 hours, max 6 people, price per person in TND).
- **Booking request** — tourist's request for one experience/date/time/group size; amount is *authorised*, not yet charged.
- **Authorise / capture** — reserve the amount on the card / actually charge it.
- **Confirmed booking** — accepted by the host (and, per SRS FV, paid).
- **Active host** — (SDD) verified host with a published experience and payout details; only active hosts appear in search.
- **Verified host** — (SRS FV) host whose identity evidence and video call were approved by an admin.
- **Late cancellation** — cancelled less than 24 h before the start.
- **Strike** — a late host cancellation; 2 within 30 days lead to suspension (SDD only).
- **Daily limit** — a host may have at most **2** confirmed bookings per calendar day (FR-13, BR-02).
- **Overlap** — two bookings that intersect in time for the same host; never allowed (FR-12).
- **Refund policy (tourist)** — 100% if ≥ 48 h before, 50% if 24–48 h, 0% if < 24 h (SDD/BR-04). Host cancellation is always 100%.
- **TND** — Tunisian dinar, 3 decimal places.
- **MoSCoW** — Must / Should / Could / Won't priority scale; SRS FV also uses **Candidate** (not approved for launch).
- **State pattern / Strategy / Adapter / Observer** — the four design patterns in the SDD (§8).

---

_Last updated: 2026-10-30 by the AHLA team (Salma Gzara, Nour Bouzir). Review this file at the start of every work session._
