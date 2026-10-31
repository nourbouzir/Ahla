# AHLA - Project plan: phases and issues

> Two people, one codebase. The plan is built so that **Nour** and **Salma** each own whole modules, work in parallel inside every phase, and meet only through agreed interfaces (contracts) and short, reviewed pull requests.

- **Source of truth for tasks:** the GitHub issues linked below (one milestone per phase, one assignee per issue).
- **Source of truth for requirements:** `docs/requirements/Ahla_SRS_FV.docx` (what to build) and `docs/requirements/Ahla_SDD.docx` (how). Contradictions between them are settled in **P0-2 / ADR 0004**.
- **Scope:** all *Must* and *Should* requirements of SRS FV (FR-01 to FR-20, NFR-01 to NFR-21). Candidate features FR-21 to FR-25 are parked (see the end).
- **Sizes:** S = up to 1 day, M = 2-3 days, L = 4-5 days of one person's work. No calendar dates are promised; the team sets sprint dates.

## 1. How we keep our work separate and still merge it

1. **Contracts first (P0-4).** Cross-module needs are interfaces in `app/Contracts` with fakes: `PaymentGateway`, `Translator`, `HostEligibility`, `AvailabilityChecker`, `AlternativeHostFinder`, plus domain events. Each person builds against the fake and swaps in the real class later - nobody waits.
2. **One module = one owner.** Each owner works almost only inside their own module folders (table below). Editing the other person's module means asking them, and GitHub requests their review through `CODEOWNERS` (P0-3).
3. **No shared edit hot spots (P0-1).** Every module registers its own routes, migrations, language files, views and seeders through a `ModuleServiceProvider`. Two people therefore never edit the same `routes` file, language file or seeder.
4. **Schema in two halves (P0-5, P0-6).** Nour owns the identity and catalog tables, Salma the booking, payment, message and audit tables. P0-5 merges first because bookings reference it.
5. **Small, short-lived branches.** One branch per issue: `feat/<code>-<short-name>`. PRs under ~400 lines, reviewed by the *other* person within 24 h, squash-merged, rebased on `main` every morning.
6. **Shared files get tiny PRs.** `composer.*`, `package*.json`, `.env.example`, `AI_CONTEXT.md` and `docs/architecture.md` change in their own small PR so conflicts stay trivial. `docs/CHANGELOG.md`: one line per PR.
7. **Phase gates.** At the end of each phase we both stop, merge everything, run the full test suite on `main` and tick the gate below before the next phase starts.

> Required PRs, approval, and CI branch protection are not available on this private repo's current GitHub plan. This is **deferred to Nour** and remains a team convention backed by `CODEOWNERS` until the plan changes. Automatic branch deletion after merge is enabled. CODEOWNERS review requests may not be available on this plan.

### Who owns what

| Owner | Modules and folders |
|-------|---------------------|
| **Nour** (@nourbouzir) | `Auth` (accounts, roles, profiles), `Verification`, `Admin`, `Experience` (experiences, availability), `Matching` (search, recommendations, alternative hosts), `Notification`; base layout, localisation, accessibility, security, deployment |
| **Salma** (@SalmaGz) | `Booking`, `Payment`, `Messaging` (chat + translation), `app/Contracts`, `app/Events`, `app/Jobs`, audit log; booking, cancellation and chat screens; reliability tests |
| **Both** | `composer.*`, `package*.json`, `.github/`, `AI_CONTEXT.md`, `docs/architecture.md` |

## 2. Phases at a glance

| Phase | Goal | Nour | Salma |
|-------|------|------|-------|
| **0 - Foundation and contracts** | One runnable Laravel skeleton on main, agreed decisions, and shared interfaces with fakes, so from Phase 1 on each of us can work alone without waiting for the other. | 3 issues | 3 issues |
| **1 - Accounts and payment engine** | Nour builds who the users are (accounts, roles, profiles, layout). Salma builds how money moves (gateway adapter, refunds, audit log). The two tracks share no files. | 4 issues | 4 issues |
| **2 - Hosts and booking core** | Nour builds the supply side (host verification, admin review, experiences and availability). Salma builds the booking engine (state machine, overlap and 2-per-day rules, payment-confirmed bookings). They meet only through HostEligibility and AvailabilityChecker. | 4 issues | 4 issues |
| **3 - Discovery and cancellation** | Nour builds search, filters and recommendations with human-readable reasons, plus the real alternative-host finder. Salma builds host cancellation, full refund and the booking lifecycle jobs, consuming the finder through its contract. | 4 issues | 3 issues |
| **4 - Communication and quality** | Salma builds messaging and translation. Nour builds notifications, the accessibility and localisation pass, and security hardening. | 3 issues | 3 issues |
| **5 - Integration, hardening and release** | Join the two tracks, prove the full journey end to end, test non-functional targets, deploy, and close the documentation. | 4 issues | 4 issues |

Total: **43 issues** - Nour 22, Salma 21.

## 3. Phase 0 - Foundation and contracts

**Goal.** One runnable Laravel skeleton on main, agreed decisions, and shared interfaces with fakes, so from Phase 1 on each of us can work alone without waiting for the other.

**Gate (done when).** `composer install` and `composer test` pass on `main` and in CI; `php artisan migrate:fresh` builds the whole schema; ADR 0004 is merged; every ADR question is either answered or explicitly marked **Deferred** with an owner; and `app/Contracts`, its fakes, and events are merged and reviewed by both owners.

**Gate status:** implementation and documentation are on `feat/phase-0-foundation`; the gate is not complete until the changes are reviewed and merged and the checks pass on `main`.

### Nour's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#1](https://github.com/nourbouzir/Ahla/issues/1) | P0-1 | Create the Laravel 12 skeleton with module layout and green CI | Nour | M | must | - |
| [#3](https://github.com/nourbouzir/Ahla/issues/3) | P0-3 | Team workflow: CODEOWNERS, review rules and conflict rules | Nour | S | must | - |
| [#5](https://github.com/nourbouzir/Ahla/issues/5) | P0-5 | Schema: users, languages, host profiles, verification, experiences, availability | Nour | M | must | #1 |

### Salma's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#2](https://github.com/nourbouzir/Ahla/issues/2) | P0-2 | Reconcile SRS FV and SDD, and record ADR 0004 | Salma | M | must | - |
| [#4](https://github.com/nourbouzir/Ahla/issues/4) | P0-4 | Define cross-module contracts, domain events and fakes | Salma | M | must | #1 |
| [#6](https://github.com/nourbouzir/Ahla/issues/6) | P0-6 | Schema: bookings, payments, messages, notifications, audit log | Salma | M | must | #1, #5 |

## 4. Phase 1 - Accounts and payment engine

**Goal.** Nour builds who the users are (accounts, roles, profiles, layout). Salma builds how money moves (gateway adapter, refunds, audit log). The two tracks share no files.

**Gate (done when).** A user can register, log in and edit a profile in EN/FR/AR; a fake or sandbox payment can be authorised and refunded through the PaymentGateway interface; audit log records status changes.

### Nour's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#7](https://github.com/nourbouzir/Ahla/issues/7) | P1-1 | Base layout, EN/FR/AR localisation and right-to-left support | Nour | M | must | #1 |
| [#8](https://github.com/nourbouzir/Ahla/issues/8) | P1-2 | Registration, login, logout and password reset | Nour | M | must | #5, #7 |
| [#9](https://github.com/nourbouzir/Ahla/issues/9) | P1-3 | Role-based access control and the role/action matrix | Nour | S | must | #8 |
| [#10](https://github.com/nourbouzir/Ahla/issues/10) | P1-4 | Tourist and host profiles with private-field visibility | Nour | M | must | #8, #9 |

### Salma's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#11](https://github.com/nourbouzir/Ahla/issues/11) | P1-5 | Spike: choose the payment provider (ADR 0005) | Salma | S | must | - |
| [#12](https://github.com/nourbouzir/Ahla/issues/12) | P1-6 | Payment gateway adapter, webhook endpoint and payment statuses | Salma | L | must | #4, #6, #11 |
| [#13](https://github.com/nourbouzir/Ahla/issues/13) | P1-7 | Refund service: full refund on host cancellation, tourist policy per ADR 0004 | Salma | M | must | #4, #12 |
| [#14](https://github.com/nourbouzir/Ahla/issues/14) | P1-8 | Audit log service | Salma | S | must | #6 |

## 5. Phase 2 - Hosts and booking core

**Goal.** Nour builds the supply side (host verification, admin review, experiences and availability). Salma builds the booking engine (state machine, overlap and 2-per-day rules, payment-confirmed bookings). They meet only through HostEligibility and AvailabilityChecker.

**Gate (done when).** An admin can verify a host; a host can publish availability; a tourist can request a booking that is confirmed only after successful payment; concurrency tests prove no double booking. Fakes for HostEligibility and AvailabilityChecker are still allowed.

### Nour's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#15](https://github.com/nourbouzir/Ahla/issues/15) | P2-1 | Host verification application with identity evidence upload | Nour | M | must | #10 |
| [#16](https://github.com/nourbouzir/Ahla/issues/16) | P2-2 | Admin panel: review applications, schedule video call, set host status | Nour | L | must | #15, #17, #9, #14 |
| [#17](https://github.com/nourbouzir/Ahla/issues/17) | P2-3 | Host state machine and the 'Verified-only' booking guard | Nour | M | must | #4, #5 |
| [#18](https://github.com/nourbouzir/Ahla/issues/18) | P2-4 | Host experiences and availability management | Nour | L | must | #10 |

### Salma's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#19](https://github.com/nourbouzir/Ahla/issues/19) | P2-5 | Booking request and BookingState machine | Salma | L | must | #4, #6, #9, #12 |
| [#20](https://github.com/nourbouzir/Ahla/issues/20) | P2-6 | No overlap and max 2 confirmed bookings per day, in a locked transaction | Salma | L | must | #19 |
| [#21](https://github.com/nourbouzir/Ahla/issues/21) | P2-7 | Confirm a booking only after successful payment; role-based status view | Salma | L | must | #19, #12 |
| [#22](https://github.com/nourbouzir/Ahla/issues/22) | P2-8 | Booking UI: request form, My bookings, host booking list | Salma | M | must | #21, #7 |

## 6. Phase 3 - Discovery and cancellation

**Goal.** Nour builds search, filters and recommendations with human-readable reasons, plus the real alternative-host finder. Salma builds host cancellation, full refund and the booking lifecycle jobs, consuming the finder through its contract.

**Gate (done when).** A tourist can search, see why a host matches, and book; a host cancellation refunds in full and shows alternative hosts using the real finder.

### Nour's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#23](https://github.com/nourbouzir/Ahla/issues/23) | P3-1 | Search and filter hosts | Nour | L | must | #17, #18, #10 |
| [#24](https://github.com/nourbouzir/Ahla/issues/24) | P3-2 | Recommendation engine with human-readable reasons | Nour | L | must | #23 |
| [#25](https://github.com/nourbouzir/Ahla/issues/25) | P3-3 | Search form, results with 'why this host', and host profile pages | Nour | M | must | #23, #24, #7 |
| [#26](https://github.com/nourbouzir/Ahla/issues/26) | P3-4 | Alternative-host finder (same city, Verified, available) | Nour | M | must | #24, #18, #4 |

### Salma's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#27](https://github.com/nourbouzir/Ahla/issues/27) | P3-5 | Host cancellation: full refund request and alternative suggestion | Salma | L | must | #13, #21, #4 |
| [#28](https://github.com/nourbouzir/Ahla/issues/28) | P3-6 | Cancellation and alternative-host screens | Salma | M | must | #27, #22 |
| [#29](https://github.com/nourbouzir/Ahla/issues/29) | P3-7 | Scheduler and booking lifecycle jobs | Salma | M | must | #21 |

## 7. Phase 4 - Communication and quality

**Goal.** Salma builds messaging and translation. Nour builds notifications, the accessibility and localisation pass, and security hardening.

**Gate (done when).** Tourist and host can chat with translated text and see the original; notifications are delivered for in-scope events; accessibility and security checklists pass.

### Nour's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#33](https://github.com/nourbouzir/Ahla/issues/33) | P4-4 | Notifications for booking, payment, cancellation, refund and message events | Nour | M | should | #4, #21, #27, #30 |
| [#34](https://github.com/nourbouzir/Ahla/issues/34) | P4-5 | Accessibility and localisation pass (WCAG 2.2 AA, EN/FR/AR, RTL) | Nour | M | must | #25, #22 |
| [#35](https://github.com/nourbouzir/Ahla/issues/35) | P4-6 | Security and privacy hardening | Nour | M | must | #9, #15 |

### Salma's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#30](https://github.com/nourbouzir/Ahla/issues/30) | P4-1 | In-app messaging per booking | Salma | M | must | #19, #9 |
| [#31](https://github.com/nourbouzir/Ahla/issues/31) | P4-2 | Translation between English, Arabic and French (original kept) | Salma | L | must | #30, #4 |
| [#32](https://github.com/nourbouzir/Ahla/issues/32) | P4-3 | Chat UI with translated text and 'show original' | Salma | M | must | #30, #31, #7 |

## 8. Phase 5 - Integration, hardening and release

**Goal.** Join the two tracks, prove the full journey end to end, test non-functional targets, deploy, and close the documentation.

**Gate (done when).** End-to-end journey passes; fakes are replaced by real implementations in production config; NFR tests reported; app deployed; documentation matches what was built.

### Nour's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#37](https://github.com/nourbouzir/Ahla/issues/37) | P5-2 | Replace fakes with real implementations and remove dead stubs | Nour | S | must | #26, #17, #18 |
| [#38](https://github.com/nourbouzir/Ahla/issues/38) | P5-3 | Performance tests with k6: pages, search, recommendations | Nour | M | must | #25, #31 |
| [#41](https://github.com/nourbouzir/Ahla/issues/41) | P5-6 | Production deployment | Nour | M | must | #35, #37 |
| [#43](https://github.com/nourbouzir/Ahla/issues/43) | P5-8 | Final documentation refresh and setup-report close-out | Nour | M | must | #36, #41 |

### Salma's lane

| Issue | Code | Title | Owner | Size | Priority | Depends on |
|-------|------|-------|-------|------|----------|------------|
| [#36](https://github.com/nourbouzir/Ahla/issues/36) | P5-1 | End-to-end acceptance test of the main journey | Salma | L | must | #25, #28, #22 |
| [#39](https://github.com/nourbouzir/Ahla/issues/39) | P5-4 | Reliability: concurrency soak test, backups and recovery runbook | Salma | M | must | #20, #29 |
| [#40](https://github.com/nourbouzir/Ahla/issues/40) | P5-5 | Demo seed data and demo script | Salma | S | should | #25, #28 |
| [#42](https://github.com/nourbouzir/Ahla/issues/42) | P5-7 | Usability test with six first-time tourists | Salma | M | must | #40, #41 |

## 9. Hand-offs between the two tracks

### Hard dependencies (one of us must wait for the other's merged PR)

| Waiting issue | Owner | Needs | Owner |
|---------------|-------|-------|-------|
| [#4](https://github.com/nourbouzir/Ahla/issues/4) P0-4 | Salma | [#1](https://github.com/nourbouzir/Ahla/issues/1) P0-1 | Nour |
| [#6](https://github.com/nourbouzir/Ahla/issues/6) P0-6 | Salma | [#1](https://github.com/nourbouzir/Ahla/issues/1) P0-1 | Nour |
| [#6](https://github.com/nourbouzir/Ahla/issues/6) P0-6 | Salma | [#5](https://github.com/nourbouzir/Ahla/issues/5) P0-5 | Nour |
| [#16](https://github.com/nourbouzir/Ahla/issues/16) P2-2 | Nour | [#14](https://github.com/nourbouzir/Ahla/issues/14) P1-8 | Salma |
| [#17](https://github.com/nourbouzir/Ahla/issues/17) P2-3 | Nour | [#4](https://github.com/nourbouzir/Ahla/issues/4) P0-4 | Salma |
| [#19](https://github.com/nourbouzir/Ahla/issues/19) P2-5 | Salma | [#9](https://github.com/nourbouzir/Ahla/issues/9) P1-3 | Nour |
| [#22](https://github.com/nourbouzir/Ahla/issues/22) P2-8 | Salma | [#7](https://github.com/nourbouzir/Ahla/issues/7) P1-1 | Nour |
| [#26](https://github.com/nourbouzir/Ahla/issues/26) P3-4 | Nour | [#4](https://github.com/nourbouzir/Ahla/issues/4) P0-4 | Salma |
| [#30](https://github.com/nourbouzir/Ahla/issues/30) P4-1 | Salma | [#9](https://github.com/nourbouzir/Ahla/issues/9) P1-3 | Nour |
| [#32](https://github.com/nourbouzir/Ahla/issues/32) P4-3 | Salma | [#7](https://github.com/nourbouzir/Ahla/issues/7) P1-1 | Nour |
| [#33](https://github.com/nourbouzir/Ahla/issues/33) P4-4 | Nour | [#4](https://github.com/nourbouzir/Ahla/issues/4) P0-4 | Salma |
| [#33](https://github.com/nourbouzir/Ahla/issues/33) P4-4 | Nour | [#21](https://github.com/nourbouzir/Ahla/issues/21) P2-7 | Salma |
| [#33](https://github.com/nourbouzir/Ahla/issues/33) P4-4 | Nour | [#27](https://github.com/nourbouzir/Ahla/issues/27) P3-5 | Salma |
| [#33](https://github.com/nourbouzir/Ahla/issues/33) P4-4 | Nour | [#30](https://github.com/nourbouzir/Ahla/issues/30) P4-1 | Salma |
| [#34](https://github.com/nourbouzir/Ahla/issues/34) P4-5 | Nour | [#22](https://github.com/nourbouzir/Ahla/issues/22) P2-8 | Salma |
| [#36](https://github.com/nourbouzir/Ahla/issues/36) P5-1 | Salma | [#25](https://github.com/nourbouzir/Ahla/issues/25) P3-3 | Nour |
| [#38](https://github.com/nourbouzir/Ahla/issues/38) P5-3 | Nour | [#31](https://github.com/nourbouzir/Ahla/issues/31) P4-2 | Salma |
| [#40](https://github.com/nourbouzir/Ahla/issues/40) P5-5 | Salma | [#25](https://github.com/nourbouzir/Ahla/issues/25) P3-3 | Nour |
| [#42](https://github.com/nourbouzir/Ahla/issues/42) P5-7 | Salma | [#41](https://github.com/nourbouzir/Ahla/issues/41) P5-6 | Nour |
| [#43](https://github.com/nourbouzir/Ahla/issues/43) P5-8 | Nour | [#36](https://github.com/nourbouzir/Ahla/issues/36) P5-1 | Salma |

### Soft hand-offs (fakes cover the gap, so nobody is blocked)

| Real implementation | Owner | Consumed by | Owner | Fake used until then |
|---------------------|-------|-------------|-------|----------------------|
| `HostEligibility` - [#17](https://github.com/nourbouzir/Ahla/issues/17) | Nour | [#19](https://github.com/nourbouzir/Ahla/issues/19) booking request | Salma | `AlwaysBookable` |
| `AvailabilityChecker` - [#18](https://github.com/nourbouzir/Ahla/issues/18) | Nour | [#19](https://github.com/nourbouzir/Ahla/issues/19) / [#20](https://github.com/nourbouzir/Ahla/issues/20) booking rules | Salma | `OpenAvailability` |
| `AlternativeHostFinder` - [#26](https://github.com/nourbouzir/Ahla/issues/26) | Nour | [#27](https://github.com/nourbouzir/Ahla/issues/27) host cancellation | Salma | `EmptyAlternativeFinder` |
| Domain events - [#4](https://github.com/nourbouzir/Ahla/issues/4) | Salma | [#33](https://github.com/nourbouzir/Ahla/issues/33) notifications, [#16](https://github.com/nourbouzir/Ahla/issues/16) admin audit | Nour | - (events exist from Phase 0) |
| RBAC matrix - [#9](https://github.com/nourbouzir/Ahla/issues/9) | Nour | [#19](https://github.com/nourbouzir/Ahla/issues/19), [#30](https://github.com/nourbouzir/Ahla/issues/30) policies | Salma | - |
| `AuditLogger` - [#14](https://github.com/nourbouzir/Ahla/issues/14) | Salma | [#16](https://github.com/nourbouzir/Ahla/issues/16) admin decisions | Nour | - |
| Base layout - [#7](https://github.com/nourbouzir/Ahla/issues/7) | Nour | [#22](https://github.com/nourbouzir/Ahla/issues/22), [#28](https://github.com/nourbouzir/Ahla/issues/28), [#32](https://github.com/nourbouzir/Ahla/issues/32) screens | Salma | - |

## 10. Critical path

The longest chain of issues that must happen one after another (everything else can run in parallel and has slack):

P0-1 (#1) -> P0-5 (#5) -> P1-2 (#8) -> P1-3 (#9) -> P1-4 (#10) -> P2-4 (#18) -> P3-1 (#23) -> P3-2 (#24) -> P3-4 (#26) -> P5-2 (#37) -> P5-6 (#41) -> P5-7 (#42)

Start **P0-1** first; while it is being built, Salma can already do **P0-2** and **P1-5**, and Nour **P0-3**.

## 11. Parked - not in this plan

- SRS FV Candidate features, until the team approves them one by one: **FR-21** like/pass and mutual matches, **FR-22** favorites, **FR-23** ratings and reviews, **FR-24** broad admin tools and statistics, **FR-25** social login.
- SDD-only features that SRS FV does not mention (commission and host payouts, strikes and automatic suspension, disputes, review publication rules) are out of v1 per ADR 0004. Reintroducing any requires a new issue and ADR.
- Native Android/iOS apps (SRS FV NFR-16 lists them; the SDD is web-only). Decided in P0-2.

## 12. Working rules (short)

- Pick an issue assigned to you whose *Depends on* boxes are all done; tick the issue's task boxes as you go.
- Branch `feat/<code>-<short-name>`, PR with `Closes #<issue>`, the other person reviews, squash-merge.
- Behaviour changed? Add one line to `docs/CHANGELOG.md`. Real decision? Add an ADR in `docs/decisions/`.
- Blocked for more than half a day? Comment on the issue and mention the other person - do not edit their module.
- Requirement unclear? Do not guess: leave it for ADR 0004 (P0-2) or ask.
