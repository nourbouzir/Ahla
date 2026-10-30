# 2. Use a layered modular monolith on Laravel 11 + MySQL 8

- **Status:** Accepted
- **Date:** 2026-10-30

## Context

AHLA is a transactional marketplace. Its most important rules — at most two
confirmed bookings per host per day, no overlapping bookings, booking confirmed
only with a successful payment, and refunds on cancellation — must be checked and
saved together or they will be broken under concurrent requests (SRS FV FR-12,
FR-13, FR-15, NFR-13, NFR-14).

The team is a small group of students with one semester, knows PHP/Laravel, and
needs slow work (email/SMS, translation, alternative-host suggestions, expiry,
payouts) to stay off the request path. Native mobile apps are not planned for the
first release (the SDD scopes a mobile-first web app, even though SRS FV NFR-16
also lists Android/iOS — see `AI_CONTEXT.md` §6).

Alternatives considered in the SDD (§2.2–2.3):
- **Microservices** — too much infrastructure; the daily limit and payment capture would need distributed transactions.
- **Pure event-driven with a broker (e.g. RabbitMQ)** — eventual consistency is risky for money and limits.
- **Structured / functional design, Waterfall** — rules would be duplicated across procedures; feedback from real hosts and tourists would come too late.

## Decision

Build AHLA as a **layered modular monolith** (MVC) with object-oriented domain
classes, using **Laravel 11 (PHP 8.3)** and **MySQL 8 (InnoDB)**:

- One codebase and one database; modules (Booking, Matching, Verification, Payment, …) talk through each other's services or domain events, never through each other's tables.
- Booking request/accept run in a single transaction that locks the host's bookings for the date (`SELECT … FOR UPDATE`) before counting.
- Internal **domain events + a database-backed job queue** handle notifications, strikes, alternative-host suggestions and audit logging; the Laravel **scheduler** owns all time-based rules.
- Payment and translation sit behind **Adapter** interfaces so providers can be swapped.
- Front end is server-rendered Blade with plain JavaScript and Leaflet; Arabic uses a right-to-left stylesheet.
- Work in Agile sprints of two weeks: search and booking first, then translation and the remaining features.

## Consequences

- Easier: consistent bookings and payments in one transaction; simple local setup (one repo, one DB); Laravel gives events, queues, scheduling, policies and testing out of the box; business rules are plain classes that are easy to unit test.
- Harder: the whole app scales as one unit, so slow modules (translation) must be isolated with the queue; module boundaries rely on team discipline, not on the network; a future native app would need the JSON API (`/api/*`) to be kept clean and versioned.
- We are committed to PHP/Composer tooling in CI and to MySQL features (row locks). The payment provider is still open and must support authorise / capture / refund / payouts (tracked in `docs/architecture.md` §7).
