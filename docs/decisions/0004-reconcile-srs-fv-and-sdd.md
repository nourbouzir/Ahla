# 4. Reconcile the SRS FV and the SDD

- **Status:** Accepted (by merging the PR that adds it)
- **Date:** 2026-10-30

## Context

The SDD (`Ahla_SDD.docx`) was written against the first SRS (`Ahla_SRS.docx`, v1.0). The
current baseline, `Ahla_SRS_FV.docx`, says it supersedes that SRS, and the two disagree on
numbering, states, flows and scope. The SRS FV also leaves several rules open on purpose
(its section 19). Code, tests and issues cannot be written until each of these has one answer.

Rule used to decide: **SRS FV says *what* to build; the SDD says *how*.** Where FV is silent,
we keep the SDD behaviour only if it is needed to make an FV requirement work. Everything
else from the SDD is left out of v1 rather than half-built. Each decision below says what it
replaces and which issues it affects. The id-by-id table is
[`docs/requirements/ID-mapping.md`](../requirements/ID-mapping.md).

## Decision

### 1. Baseline and ids
SRS FV is the requirements baseline. Code, tests, issues and docs cite **SRS FV ids**
(FR, NFR, BR) only. The two `.docx` files are not edited; this ADR, the mapping file and
`docs/architecture.md` are the up-to-date design. `UC-xx` labels stay as informal names
in diagrams.

### 2. Host states
Four states, as in FV (FR-06, BR-01): `pending`, `verified`, `rejected`, `suspended`.
SDD `Applied` = `pending`; `Verified` + `Active` = `verified`; `Banned` = `suspended` with a reason.

| From | To | Who and when |
|------|----|--------------|
| (new host application) | `pending` | Host submits profile and ID |
| `pending` | `verified` or `rejected` | Admin, after the ID check and the video call |
| `verified` | `suspended` | Admin, with a reason |
| `suspended` | `verified` | Admin reinstates |
| `rejected` | `pending` | Host re-applies |

Only `verified` hosts are bookable and appear in search (FR-07, BR-02). A published experience and
payout details are **not** conditions of being bookable; a verified host with no availability is
simply never available. There is no automatic suspension in v1 (decision 12).

### 3. Payment flow
ADR 0003 is confirmed: authorise the card when the booking is requested, capture when the host accepts,
release if the request is declined or expires. A booking becomes `confirmed` only after the gateway
reports a **successful capture**; pending, failed or timed-out is never success (FR-15, BR-05).
If the provider chosen in ADR 0005 cannot authorise and capture separately, fall back to charging at
request time: the booking stays `requested` until the host accepts, and decline or expiry refunds in full.
The statuses below do not change.

### 4. Booking and payment states
The host's accept/decline step stays: FV counts *confirmed* bookings (FR-13, BR-03, BR-04) and says hosts
"manage bookings", which only makes sense if something sits before *confirmed*.

- Booking: `requested`, `confirmed`, `declined`, `expired`, `cancelled`, `completed`.
  SDD `Accepted` is renamed `confirmed` (FV's word); `Disputed` is dropped (decision 12).
- Payment: `authorised`, `captured`, `released`, `refunded`, `failed`.
- Transitions: `requested` -> `confirmed` (host accepts and the capture succeeds) | `declined` |
  `expired`; `confirmed` -> `cancelled` (tourist or host) | `completed` (2 hours after the end time, automatic).
- The 2-per-day limit and the overlap check count **`confirmed` bookings only**, and both are re-checked
  inside the locked transaction when the host accepts (NFR-14).

### 5. Request expiry
A `requested` booking expires after **12 hours** without an answer. FV is silent; the SDD value is kept
because the authorisation must not be held indefinitely. Stored as config `ahla.request_ttl_hours = 12`.

### 6. Refunds
- **Host cancels** a confirmed booking: always **100%** (FR-17). Cancelling less than 24 hours before
  the start also triggers alternative hosts (decision 7).
- **Tourist cancels**: FV leaves this undefined. We adopt the SDD policy as a configurable default:
  **100%** if 48 hours or more before the start, **50%** from 24 to 48 hours, **0%** under 24 hours
  (a cancellation at exactly 24 h gets 50%). Thresholds live in `config/ahla.php`, so stakeholders can
  change them without code changes. See the open list below.
- "Less than 24 hours" means strictly less than 24 h between now and the start.

### 7. Alternative hosts (FR-17, BR-06)
Same **city** as the cancelled booking (replaces the SDD's 10 km radius); `verified`; available at the
same date and time (`AvailabilityChecker`); not the cancelling host; ordered by match score; **at most 3**
(`ahla.alternatives_max`). A shared language is a ranking factor, not a filter. An empty result is valid:
the tourist is told no host is available.

### 8. Platform
v1 is a responsive, mobile-first **web app** (Blade). Working in current Chrome and Safari on Android
and iOS, and on desktop browsers, is how v1 meets NFR-16. Native Android and iOS apps are not in v1.
WCAG 2.2 AA (NFR-08) applies to the web UI.

### 9. Messaging eligibility (FR-18)
One conversation per booking, open from the moment the booking is `requested`, between that booking's
tourist and host only. No messaging before a booking exists in v1. Phone number and email stay hidden
until the booking is `confirmed`.

### 10. Notifications (FR-20, Should)
Channels: in-app (always stored) and email (queued, 3 retries). SMS is out of v1. Events: booking requested
(to host); confirmed, declined, expired (to tourist); cancelled (to the other party); refund requested and
payment failed (to tourist); new message (to recipient). **Not in v1:** 24 h / 2 h reminders, and "match"
events (they belong to Candidate FR-21).

### 11. Time zone and boundaries (BR-09)
The application time zone and the definition of "calendar day" is **Africa/Tunis** (UTC+1, no daylight
saving). Booking date, start and end are stored in local time. Intervals are half-open, `[start, end)`:
a booking **may start exactly when another ends** (not an overlap). A booking may not cross midnight.

### 12. Out of v1
Commission and host payouts, strikes and automatic suspension, disputes, reviews and ratings, reports,
and reminders are **not built** in v1. FV does not specify them (reviews are Candidate FR-23).
Consequences: no `reviews`, `disputes` or `reports` tables; no `commission` or `payout_*` columns; no
`Trust` module; the jobs `ReleasePayouts`, `PublishReviews` and `SendReminders` are dropped; an admin can
still suspend a host by hand (FR-06). Each returns later through its own issue and ADR.

### 13. Experiences stay
FV has no experience entity, but it needs a price to show and filter on (FR-08, FR-14), so the `Experience`
from the SDD stays: title, description, interests, duration 1 to 6 hours, price per person (TND), maximum
group of 6, meeting point. The booking total is price x group size, **computed on the server**.

### 14. ID evidence
Stored on the private disk, readable only by the owner and admins, **deleted 90 days after the decision**
(SDD value). FV leaves retention and legal basis open (NFR-12, NFR-21); this is a default that still needs
legal confirmation (open list).

### 15. Passwords, sessions, admin MFA
Minimum 8 characters, Laravel's default hashing, login throttling, **30 minutes** idle session timeout.
Admin two-factor authentication is **deferred** to the security-hardening issue (#35).

### 16. Recommendations (FR-09, FR-10)
Keep the SDD weighted score (language 0.4, interests 0.3, proximity 0.2, rating 0.1; weights in config),
with a reason for every result. Proximity: `1 - distance / 15 km` when both sides have coordinates,
otherwise 1 for the same city and 0 for a different city. No LLM in v1 (it would need its own ADR).

### 17. Candidate features
FR-21 to FR-25 stay parked until approved one by one.

### 18. Payment provider
Still open (ADR 0005, issue #11). v1 may ship on the sandbox or fake gateway if nothing fits.

## Left open, with an owner

| Question | Why open | Default until answered | Owner | Issue |
|----------|----------|------------------------|-------|-------|
| Tourist cancellation refund percentages | FV leaves tourist refunds undefined | Decision 6 policy, from config | Salma | #13 |
| Where ratings come from before reviews exist | FR-08 filters by rating but FR-23 (reviews) is Candidate | `rating_avg` nullable; unrated hosts score as 4.0 and appear unless a minimum rating is chosen | Nour | #23 |
| Is a rule-based score acceptable as "AI-generated" (FR-09, FR-10)? | The requirement says AI; the design is deterministic | Decision 16, no LLM | Nour (with the instructor) | #24 |
| ID evidence retention and legal basis | NFR-12, NFR-21 | 90 days, decision 14 | Nour | #15 |
| Admin two-factor authentication | FV does not decide | Not in v1 | Nour | #35 |
| Payment provider | Not chosen | Fake/sandbox gateway | Salma | #11 |
| Backup RPO and RTO, load profile for "thousands of users" | NFR-15, NFR-06 not quantified | Proposals in the testing issues | Salma / Nour | #39, #38 |
| Reissue the SDD as v1.1 for the course? | The `.docx` is now out of date | Not reissued; this ADR and `architecture.md` are current | Salma | - |

## Consequences

- One vocabulary and one list of states for both of us; the SDD and SRS v1.0 become history.
- Smaller v1: no payouts, reviews, disputes, strikes or reminders, which also shrinks the schema and the job list.
- The weakest points are the ones in the open list; none blocks coding because each has a default.
- Changes to the plan: #5 uses the four host states, no `payout_account`, nullable `rating_avg`. #6 adds `host_id`,
  `cancelled_by`, `cancelled_at` and `cancellation_reason` to `bookings` and drops `late_host_cancel`, commission
  and payout columns. #13 enables the tourist policy from config. #26 uses same city, at most 3. #29 schedules
  only `ExpireRequests` and `AutoComplete`. #30 and #33 follow decisions 9 and 10.
- ADR 0003 is now Accepted and is refined here (states renamed, fallback added).
