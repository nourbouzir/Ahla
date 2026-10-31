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
| Framework   | Laravel 12 (routing, Eloquent ORM, events, queues, scheduler, Gates)  |
| Database    | MySQL 8 (InnoDB); encrypted private disk for ID documents             |
| Package mgr | Composer (PHP), npm (front-end assets only, via Vite)                 |
| Testing     | PHPUnit + Laravel feature tests; k6 (load); SonarCloud (quality)      |
| Maps        | OpenStreetMap + Leaflet in the browser                                |
| Deploy      | One Linux VPS: Nginx + PHP-FPM 8.3, MySQL 8, supervised queue worker, cron running `schedule:run` every minute. Dev: Laravel Sail or XAMPP |

> The payment provider is decided in [ADR 0005](docs/decisions/0005-payment-provider.md): v1 runs on `SandboxPaymentGateway`; the first real adapter is ClicToPay. Code against the `PaymentGateway` interface only (authorise, capture, release, refund).

## 3. Project structure

The repo is the Laravel application root (Laravel 12 skeleton, issue #1 — see `docs/setup.md`).
Modules are groups of controller + service + model + repository; modules never touch another module's tables directly.

- `app/Modules/<Module>/` — one self-contained folder per module: `Auth`, `Verification`, `Experience`, `Matching`, `Booking`, `Payment`, `Messaging`, `Notification`, `Admin` (no `Trust` module in v1: reviews, strikes and disputes are out of v1, ADR 0004)
- `app/Modules/<Module>/<Module>ModuleServiceProvider.php` — extends `App\Modules\ModuleServiceProvider`, which loads the module's own `routes/web.php` (web group), `routes/api.php` (api group, `/api` prefix), `Database/Migrations/`, `Views/` (`view('booking::name')`) and `lang/{en,fr,ar}/` (`__('booking::messages.key')`). Nothing outside the module folder is edited to add a route, migration, view or string.
- `app/Modules/<Module>/Database/Seeders/<Module>DatabaseSeeder.php` — module seed data; `database/seeders/DatabaseSeeder.php` calls them in order and is not edited per module.
- `app/Modules/Modules.php` — the single list of modules (provider registration and seeding order). Adding a module = copy a module folder + add its name there; `tests/Feature/ModulesTest.php` fails if the two disagree.
- `app/Modules/Booking/States/`, `app/Modules/Verification/States/` — State pattern classes (`BookingState`, `HostState`)
- `app/Modules/Matching/Strategies/` — `MatchingStrategy` implementations
- `app/Modules/Payment/Policies/` — `RefundPolicy` implementations; `app/Modules/Payment/Gateways/` — `PaymentGateway` adapters
- `app/Events/`, `app/Listeners/` — domain events and listeners (observer pattern)
- `app/Jobs/` — scheduled/queued jobs (v1: `ExpireRequests`, `AutoComplete`, `PurgeIdDocuments`; reminders, payouts and review publication are out of v1)
- `resources/views/`, `resources/css/`, `resources/js/` — only the shared layout and assets; module screens live in `app/Modules/<Module>/Views/`
- `routes/web.php`, `routes/api.php` — app-wide routes only (health check etc.); feature routes live in the module's own `routes/` folder
- `database/migrations/`, `database/seeders/` — app-wide schema and the root seeder; module tables live in `app/Modules/<Module>/Database/Migrations/`
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
- **Privacy:** ID documents go on the encrypted private disk, readable by admins only. Phone numbers and email stay hidden until a booking is `confirmed`.
- **Localisation:** no hard-coded user-facing text; use the module's `lang/{en,fr,ar}` files (`__('booking::messages.key')`). Layouts must work right-to-left for Arabic and from 360 px wide.
- **Slow work** (email, translation, alternative-host suggestions) goes on the queue via events/listeners, not inline in a request.

## 5. What NOT to do

- Don't invent requirements — if something is unclear, ask or leave a `TODO` with your name.
- Don't implement **Candidate** features (FR-21 to FR-25 in the current SRS: like/pass, favorites, reviews, broad admin tools, social login) until the team approves them.
- Don't delete or rewrite large sections you weren't asked to touch.
- Don't commit generated files, build output, `vendor/`, `node_modules/`, or `.env`.
- Don't paste private user data or credentials into AI chats.
- Don't call external services (payment, translation) directly from controllers or models — go through the adapter interfaces.

## 6. Decisions that settle the SDD / SRS FV differences (ADR 0004)

The SDD was written against an older SRS. **SRS FV is the baseline: cite its FR / NFR / BR ids only** (the old ids mean different things; see `docs/requirements/ID-mapping.md`). The SDD supplies the design. [ADR 0004](docs/decisions/0004-reconcile-srs-fv-and-sdd.md) fixed the conflicts; follow these and ignore the SDD wherever it differs:

- **Host states:** `pending`, `verified`, `rejected`, `suspended`. Only `verified` hosts are bookable and searchable (FR-07). No "Active" or "Banned" state.
- **Booking states:** `requested`, `confirmed`, `declined`, `expired`, `cancelled`, `completed`. "Accepted" in the SDD means `confirmed`.
- **Payment:** authorise on request, capture when the host accepts, release on decline or expiry. A booking is `confirmed` only after a **successful capture** (FR-15, BR-05); pending, failed or timed out is never success. Payment states: `authorised`, `captured`, `released`, `refunded`, `failed`.
- **Rules:** a request expires after 12 h. The 2-per-day limit (BR-03) and the overlap check (BR-04) count `confirmed` bookings only. Intervals are half-open `[start, end)`, so a booking may start when another ends; no booking crosses midnight; the time zone and "calendar day" are Africa/Tunis.
- **Cancellation:** a host cancelling always refunds 100%; less than 24 h before the start the tourist also gets up to 3 `verified` hosts in the **same city** who are free at that time (FR-17). A tourist cancelling gets 100% / 50% / 0% (48 h / 24 h) from config.
- **Platform:** responsive web app only in v1; mobile browsers satisfy NFR-16. **Messaging:** one conversation per booking, from `requested`, between its tourist and host; contact details hidden until `confirmed`. **Notifications:** in-app + email; no SMS, no reminders.
- **Scheduled jobs in v1:** `ExpireRequests`, `AutoComplete`, `PurgeIdDocuments` only.
- **Out of v1:** commission and payouts, strikes and automatic suspension, disputes, reviews and ratings, reports, reminders, Candidate FR-21 to FR-25.
- **Still open, with a default (ADR 0004 table):** tourist refund percentages, where ratings come from before reviews exist, whether rule-based ranking counts as "AI-generated", ID-evidence retention, admin MFA, the payment provider. Use the default and say so in the PR; do not decide it silently.

## 7. Glossary / domain terms

- **Tourist** — visitor (foreign or Tunisian) who books an experience.
- **Host** — local person who offers experiences after being verified.
- **Experience** — a short activity offered by a host (1–6 hours, max 6 people, price per person in TND).
- **Booking request** — tourist's request for one experience/date/time/group size; amount is *authorised*, not yet charged.
- **Authorise / capture** — reserve the amount on the card / actually charge it.
- **Confirmed booking** — accepted by the host and paid: the capture succeeded (FR-15, BR-05).
- **Verified host** — host whose identity evidence and video call an admin approved (BR-01); the only bookable and searchable host (BR-02).
- **Late cancellation** — cancelled less than 24 h before the start.
- **Daily limit** — a host may have at most **2** `confirmed` bookings per calendar day, Africa/Tunis (FR-13, BR-03).
- **Overlap** — two `confirmed` bookings of the same host whose `[start, end)` intervals intersect; never allowed (FR-12, BR-04). Touching end-to-start is fine.
- **Refund policy (tourist)** — 100% if ≥ 48 h before, 50% if 24–48 h, 0% if < 24 h; configurable default, open question (ADR 0004). Host cancellation is always 100%.
- **TND** — Tunisian dinar, 3 decimal places.
- **MoSCoW** — Must / Should / Could / Won't priority scale; SRS FV also uses **Candidate** (not approved for launch).
- **State pattern / Strategy / Adapter / Observer** — the four design patterns in the SDD (§8).

---

_Last updated: 2026-10-31 by the AHLA team (Salma Gzara, Nour Bouzir). Review this file at the start of every work session._
