# Requirement ID mapping: old SRS / SDD -> SRS FV

The SDD (`Ahla_SDD.docx`) and the old SRS (`Ahla_SRS.docx`, v1.0) use one numbering.
The current baseline, `Ahla_SRS_FV.docx`, **supersedes** it and uses another. The same
number means different things in the two (for example old FR-12 is "reject a booking",
FV FR-12 is "no overlapping bookings"), so **never copy an id from the SDD into code,
tests or issues**. From now on we cite SRS FV ids only. The decisions behind each
"none" and each difference are in [ADR 0004](../decisions/0004-reconcile-srs-fv-and-sdd.md).

How to read the tables: **old id** (SDD / SRS v1.0) -> **SRS FV id**, and what happens to
anything FV does not cover.

## Functional requirements

| Old | Old meaning | SRS FV | Note |
|-----|-------------|--------|------|
| FR-01 | Register as tourist or host | FR-01 | Name, phone and languages become profile fields (FR-03) |
| FR-02 | Log in / out, role-based pages | FR-02 | Role access is NFR-10 in FV; password reset is new in FV |
| FR-03 | Host applies with profile and ID upload | FR-05, FR-03 | |
| FR-04 | Admin schedules video call, records decision | FR-05, FR-06 | Notifying the host of the decision is FR-20 |
| FR-05 | Host is "Active" only when verified + experience + payout details | FR-07 | FV has no "Active": Verified is bookable (ADR 0004, decision 2) |
| FR-06 | Host publishes experiences | none | Kept as design (Experience entity, decision 13); FV needs it for price (FR-08, FR-14) |
| FR-07 | Weekly availability and blocked dates | FR-11 | |
| FR-08 | Search by city, date, language, interests, group size | FR-08 | FV filters: city, language, interests, rating, price. Date and group size stay as booking inputs |
| FR-09 | Rank by match score and show why | FR-09, FR-10 | |
| FR-10 | Host profile with badge, languages, rating, reviews | FR-03, FR-04 | Ratings and reviews depend on Candidate FR-23 |
| FR-11 | Booking request, amount authorised not charged | FR-14, FR-15 | |
| FR-12 | Reject booking: 2 per day, overlap, group size, not active | FR-07, FR-12, FR-13 | Group-size limit is a validation rule, not an FV requirement |
| FR-13 | Host accepts/declines within 12 h, else expires | none | Kept (decisions 3 to 5); FR-15 says a booking is confirmed only after payment succeeds |
| FR-14 | Tourist cancels: refund 100% / 50% / 0% | none | FV leaves tourist refunds undefined; SDD policy adopted as configurable default (decision 6) |
| FR-15 | Host cancels: full refund, up to 3 hosts within 10 km | FR-17 | FV: an available host in the same city (decision 7) |
| FR-16 | Booking completed (host confirms or automatic) | none | `completed` status kept; Candidate FR-23 needs it |
| FR-17 | In-app messages, phone hidden until accepted | FR-18 | Eligibility rule is open in FV (decision 9); hiding contact details follows NFR-12 |
| FR-18 | Automatic translation, original on request | FR-19 | EN / AR / FR only (FV BR-07) |
| FR-19 | Email and SMS notifications, 24 h / 2 h reminders | FR-20 | FV: Should, scope TBD. SMS and reminders out of v1 (decision 10) |
| FR-20 | Reviews and ratings | FR-23 | Candidate in FV: out of v1 |
| FR-21 | Auto-suspension: 2 late cancels in 30 days or rating < 3.0 | none | Out of v1; an admin can still set Suspended (FR-06) |
| FR-22 | Report a user or experience | none | Out of v1 (FR-24 admin tools is Candidate) |
| FR-23 | Admin dashboard, reinstate or ban | FR-06 | Reinstating = admin sets Verified again; ban = Suspended with a reason. Dashboards: Candidate FR-24 |
| FR-24 | Dispute within 24 h | none | Out of v1 |
| FR-25 | Host payout minus 15% commission | none | Out of v1 |
| FR-26 | Host earnings view | none | Out of v1 |

FV requirements with no old equivalent: **FR-04** (private-field visibility), **FR-16**
(booking and payment status visible to the right roles), and the Candidates **FR-21**
(like/pass), **FR-22** (favorites), **FR-24** (admin tools), **FR-25** (social login).

## Non-functional requirements

| Old | Old meaning | SRS FV | Note |
|-----|-------------|--------|------|
| NFR-01 | Search 2 s for 95% at 300 users | NFR-02, NFR-01, NFR-06 | FV: search 2 s, pages 3 s; "thousands of users" is not yet quantified |
| NFR-02 | No more than 2 bookings per day, no double booking, locked transaction | NFR-13, NFR-14 | The locked transaction stays as our design |
| NFR-03 | bcrypt, HTTPS, 30 min session, admin 2FA | NFR-09, NFR-10 | 30 min kept (decision 15); admin 2FA deferred |
| NFR-04 | ID documents encrypted, deleted after 90 days | NFR-12, NFR-21 | Retention and legal basis open in FV (decision 14) |
| NFR-05 | No card data, money as DECIMAL(10,3) | NFR-11 | DECIMAL(10,3) is a design rule, not an FV one |
| NFR-06 | 99.5% availability | NFR-05 | FV says 99% |
| NFR-07 | Booking in 5 screens, under 3 minutes | NFR-07 | FV: 5 of 6 first-time tourists succeed unaided |
| NFR-08 | EN / FR / AR, right-to-left | NFR-18 | |
| NFR-09 | WCAG 2.1 AA | NFR-08 | FV says WCAG 2.2 AA |
| NFR-10 | Mobile-first, current Chrome / Safari / Firefox | NFR-16 | FV lists Android, iOS and web (decision 8) |
| NFR-11 | Translation and payment failure handled | NFR-20 | |
| NFR-12 | Audit log of status changes | NFR-19 | |
| NFR-13 | Business rules unit-tested, 80% coverage | NFR-17 | FV asks for modularity only; 80% stays as our team target |

FV non-functional requirements with no old equivalent: **NFR-03** (recommendations in
5 s), **NFR-04** (translation in 5 s), **NFR-15** (daily backups, RPO/RTO), **NFR-21**
(compliance).

## Business rules

| Old | Old meaning | SRS FV |
|-----|-------------|--------|
| BR-01 | Bookable only when Active | BR-02 (only Verified hosts); BR-01 is "identity and video call before Verified" |
| BR-02 | At most 2 accepted bookings per day | BR-03 |
| BR-03 | Host answers in 12 h or the request expires | none (decision 5) |
| BR-04 | Tourist refund 100% / 50% / 0% | none (decision 6) |
| BR-05 | Host cancels: 100% refund, alternatives if under 24 h | BR-06 |
| BR-06 | Auto-suspension rules | none (out of v1) |
| BR-07 | 15% commission, payout 24 h after completion | none (out of v1) |
| BR-08 | Experiences last 1 to 6 h, at most 6 people | none (kept as validation, decision 13) |

FV business rules with no old equivalent: **BR-04** (no overlapping confirmed bookings),
**BR-05** (confirm only after successful payment), **BR-07** (initial languages EN / AR / FR),
**BR-09** (calendar time zone, unresolved in FV; settled in ADR 0004, decision 11).

## Use cases and acceptance tests

The old SRS has use cases `UC-01` to `UC-12` and acceptance tests `AT-01` to `AT-26`; the SRS FV has
neither. We keep the `UC-xx` labels only as informal names in `docs/architecture.md`. The `AT-xx`
scenarios (for example AT-14: 120 TND, cancelled 40 h before, refund 60 TND) are still useful as
test data, but each test must cite the **FV** requirement it checks. Issue P5-1 builds the
requirement -> test traceability file.
