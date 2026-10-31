# 4. Reconcile the SRS FV and the SDD

- **Status:** Proposed; becomes accepted when merged after review
- **Date:** 2026-10-30

## Context

The SDD (`Ahla_SDD.docx`) was written against the first SRS (`Ahla_SRS.docx`,
v1.0). The current baseline, `Ahla_SRS_FV.docx`, supersedes that SRS; the two
documents disagree on numbering, states, flows, and scope. The SRS FV is the
source for **what** to build; the SDD supplies **how** only when needed to satisfy
an FV requirement. Otherwise, SDD-only behavior is outside v1. The requirement
mapping is in [`docs/requirements/ID-mapping.md`](../requirements/ID-mapping.md).

## Decision

### 1. Baseline and identifiers

Use SRS FV IDs in code, tests, issues, and current documentation. Do not edit the
source `.docx` files. Keep `UC-xx` labels only as informal names in diagrams.

### 2. Host states and eligibility

The host states are `pending`, `verified`, `rejected`, and `suspended` (FV FR-06,
BR-01). SDD `Applied` maps to `pending`; `Active` is removed; `Banned` maps to
`suspended` with a reason. Admins may verify or reject after the identity check
and video call, suspend with a reason, reinstate, or allow a rejected host to
re-apply. Only `verified` hosts are eligible for booking and search (FR-07,
BR-02); an experience or payout account is not an eligibility prerequisite.
There is no automatic suspension in v1.

### 3. Booking and payment flow

ADR 0003 is confirmed with this refinement: authorise payment when a booking is
requested; capture when the host accepts; release if the host declines or the
request expires. A booking is `confirmed` only after a successful capture
(FR-15, BR-05). If the selected gateway cannot authorise and capture separately,
charge at request time and refund in full on decline or expiry. A pending,
failed, or timed-out gateway response is never treated as success.

Booking states: `requested`, `confirmed`, `declined`, `expired`, `cancelled`,
`completed`. Payment states: `authorised`, `captured`, `released`, `refunded`,
`failed`. A request may become confirmed only on host acceptance plus successful
capture; confirmed bookings may be cancelled or completed. The two-per-day limit
and overlap check count confirmed bookings only and must be repeated inside the
locked transaction when the host accepts (NFR-14).

Requests expire after 12 unanswered hours (`ahla.request_ttl_hours = 12`).
Host cancellation always refunds 100% (FR-17). Until FV defines tourist refunds,
the configurable default is 100% at least 48 hours before start, 50% from 24 to
48 hours (exactly 24 hours is 50%), and 0% less than 24 hours before start.
“Less than 24 hours” is strictly less than 24 hours.

### 4. Alternative hosts

After a host cancels less than 24 hours before a confirmed booking, suggest up
to three other verified and available hosts in the **same city**, excluding the
cancelling host (FR-17, BR-06). Rank by match score; shared language affects
ranking but is not a filter. An empty result is valid and must be communicated
to the tourist.

### 5. Platform and communication

V1 is a responsive, mobile-first web application. Native Android and iOS apps
are not in v1; WCAG 2.2 AA applies to the web UI (NFR-08, NFR-16).

One conversation exists per booking, between its tourist and host, starting
when the booking is `requested` (FR-18). No conversation exists before a
booking. Phone and email remain hidden until the booking is `confirmed`.

Notifications use in-app storage and queued email with three retries. SMS,
24-hour/two-hour reminders, and match notifications are out of v1; match
notifications depend on Candidate FR-21.

### 6. Time, experience, and matching rules

The application timezone and calendar-day boundary are `Africa/Tunis` (UTC+1,
no daylight saving). Booking date and times are local. Intervals are half-open
`[start, end)`: one booking may start exactly when another ends; a booking may
not cross midnight (BR-09).

Keep the SDD `Experience` entity because FV needs price to display and filter
(FR-08, FR-14): title, description, interests, duration from one to six hours,
price per person in TND, maximum group size six, and meeting point. Compute the
booking total on the server as price per person times group size.

Use the SDD weighted score for recommendations (language 0.4, interests 0.3,
proximity 0.2, rating 0.1) and give every result a reason (FR-09, FR-10).
Proximity is `1 - distance / 15 km` when both sides have coordinates; otherwise,
it is 1 for the same city and 0 for a different city. No LLM is in v1.
`rating_avg` is nullable; until review data exists, unrated hosts score as 4.0
and appear unless a minimum rating is selected.

### 7. Security and out-of-scope behavior

Use Laravel's default password hashing, require at least eight characters,
throttle login, and set idle sessions to 30 minutes. Admin MFA is not in v1.

Store identity evidence on the private disk, readable only by the owner and
admins, and delete it 90 days after the decision as the interim default.
Retention and legal basis require legal confirmation.

Commission and host payouts, strikes and automatic suspension, disputes,
reviews and ratings, reports, and reminders are not built in v1. Do not create
`reviews`, `disputes`, or `reports` tables; commission/payout columns; a Trust
module; or payout/review/reminder jobs. Manual host suspension remains in scope.
Candidate FR-21 to FR-25 remain parked until individually approved.

The payment provider is not selected (tracked by ADR 0005 and issue #11). V1
may use the sandbox or fake gateway if no suitable provider is chosen.

## Deferred questions and owners

Every item below has a current default, an explicit deferred status, and a
responsible owner. The issue is the place to resolve and record the final answer.

| Question | Current answer/default | Status | Owner | Issue |
|----------|------------------------|--------|-------|-------|
| Tourist cancellation refund percentages | Configurable 100% / 50% / 0% policy above | Deferred | Salma | #13 |
| Source of ratings before reviews exist | Nullable `rating_avg`; unrated score is 4.0; minimum-rating filter excludes unrated hosts | Deferred | Nour | #23 |
| Whether deterministic ranking satisfies “AI-generated” | Weighted score above; no LLM | Deferred pending instructor confirmation | Nour | #24 |
| ID-evidence retention and legal basis | Private disk; 90-day deletion default | Deferred pending legal confirmation | Nour | #15 |
| Admin two-factor authentication | Not in v1 | Deferred | Nour | #35 |
| Payment provider | Sandbox/fake until provider selection | Deferred | Salma | #11 |
| Backup RPO/RTO and quantified load profile | No numeric targets yet; proposals belong to testing issues | Deferred | Salma / Nour | #39 / #38 |
| Reissue the SDD as v1.1 | Keep the ADR and `architecture.md` current; do not edit the `.docx` for now | Deferred | Salma | — |

## Consequences

- One vocabulary and one set of states are used throughout; SDD and SRS v1.0 IDs
  are historical only.
- V1 has a smaller schema and job list: no payouts, reviews, disputes, strikes,
  or reminders.
- P0-5 uses the four host states and nullable `rating_avg`. P0-6 stores host,
  cancellation, booking, payment, message, notification, and audit data without
  payout or review fields.
- ADR 0003 is accepted and refined here. The deferred items above are not
  blockers for implementation because each has an interim default and owner.
