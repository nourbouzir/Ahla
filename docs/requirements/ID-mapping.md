# Requirement ID mapping: old SRS / SDD → SRS FV

The SDD (`Ahla_SDD.docx`) and old SRS (`Ahla_SRS.docx`, v1.0) use one numbering
system. The current baseline, `Ahla_SRS_FV.docx`, supersedes them and uses
another; identical numbers can mean different things. Cite **SRS FV IDs only**
in code, tests, issues, and current documentation. The decisions behind each
mapping are in [ADR 0004](../decisions/0004-reconcile-srs-fv-and-sdd.md).

## Functional requirements

| Old ID | Old meaning | SRS FV ID | Resolution |
|--------|-------------|-----------|------------|
| FR-01 | Register as tourist or host | FR-01 | Name, phone, and languages are profile fields (FR-03). |
| FR-02 | Log in/out and role-based pages | FR-02 | FV adds password reset; role access is NFR-10. |
| FR-03 | Host application with profile and ID | FR-03, FR-05 | Verification decision is FR-06; notifications are FR-20. |
| FR-04 | Admin schedules call and records decision | FR-05, FR-06 | |
| FR-05 | Active requires verification, experience, payout details | FR-07 | Only Verified is required; no Active state or payout prerequisite. |
| FR-06 | Host publishes experiences | — | Retained as design because FR-08 and FR-14 need experience/price. |
| FR-07 | Weekly availability and blocked dates | FR-11 | |
| FR-08 | Search by city/date/language/interests/group size | FR-08 | FV filters include city, language, interests, rating, and price; date and group size are booking inputs. |
| FR-09 | Rank by match score and explain | FR-09, FR-10 | |
| FR-10 | Host profile, badge, languages, rating, reviews | FR-03, FR-04 | Ratings/reviews are Candidate FR-23 and out of v1. |
| FR-11 | Request booking and authorise amount | FR-14, FR-15 | |
| FR-12 | Reject booking for daily limit, overlap, group size, or host state | FR-07, FR-12, FR-13 | Group-size maximum remains a validation rule. |
| FR-13 | Host responds within 12 hours or request expires | — | Retained; 12 hours is the default expiry. |
| FR-14 | Tourist refunds 100% / 50% / 0% | — | Configurable SDD default; final policy deferred to #13. |
| FR-15 | Host cancellation, full refund, nearby alternatives | FR-17 | Same-city, verified, available alternatives; maximum three. |
| FR-16 | Booking completion | — | `completed` state retained; reviews remain out of v1. |
| FR-17 | Booking messages; hide contact until accepted | FR-18 | One conversation per booking; contact hidden until confirmed. |
| FR-18 | Automatic translation | FR-19 | Languages EN / AR / FR only (BR-07). |
| FR-19 | Email/SMS notifications and reminders | FR-20 | In-app and email in v1; SMS/reminders out of v1. |
| FR-20 | Reviews and ratings | FR-23 | Candidate; out of v1. |
| FR-21 | Automatic suspension | — | Out of v1; manual suspension remains (FR-06). |
| FR-22 | Report user or experience | — | Out of v1; broad admin tools are Candidate FR-24. |
| FR-23 | Admin dashboard, reinstate, or ban | FR-06 | Reinstate means Verified; ban maps to Suspended with a reason. |
| FR-24 | Dispute | — | Out of v1. |
| FR-25 | Commission and host payout | — | Out of v1. |
| FR-26 | Host earnings view | — | Out of v1. |

FV functional requirements with no old equivalent: FR-04 private-field visibility,
FR-16 role-appropriate booking/payment status visibility, and Candidate FR-21
(like/pass), FR-22 (favorites), FR-24 (admin tools), FR-25 (social login).

## Non-functional requirements

| Old ID | Old meaning | SRS FV ID | Resolution |
|--------|-------------|-----------|------------|
| NFR-01 | Search in 2 s at 95% / 300 users | NFR-01, NFR-02, NFR-06 | FV also specifies page response; “thousands” remains unquantified. |
| NFR-02 | Daily limit, no overlap, locked transaction | NFR-13, NFR-14 | Locked transaction retained as implementation design. |
| NFR-03 | Hashing, HTTPS, 30-minute session, admin MFA | NFR-09, NFR-10 | 30-minute session retained; MFA deferred to #35. |
| NFR-04 | Encrypted ID evidence and 90-day deletion | NFR-12, NFR-21 | Interim default; retention/legal basis deferred to #15. |
| NFR-05 | No card data; TND DECIMAL(10,3) | NFR-11 | Decimal precision retained as design constraint. |
| NFR-06 | 99.5% availability | NFR-05 | FV target is 99%. |
| NFR-07 | Booking in five screens / under three minutes | NFR-07 | FV usability target is five of six first-time tourists succeeding unaided. |
| NFR-08 | EN/FR/AR and RTL | NFR-18 | |
| NFR-09 | WCAG 2.1 AA | NFR-08 | FV requires WCAG 2.2 AA. |
| NFR-10 | Mobile-first web / browser support | NFR-16 | V1 is mobile-first web; native apps are not in v1. |
| NFR-11 | Translation/payment failures handled | NFR-20 | |
| NFR-12 | Audit log of status changes | NFR-19 | |
| NFR-13 | Unit tests and 80% coverage | NFR-17 | 80% remains a team target, not an FV requirement. |

FV non-functional requirements with no old equivalent: NFR-03 (recommendations
in five seconds), NFR-04 (translation in five seconds), NFR-15 (daily backups
and RPO/RTO), and NFR-21 (compliance).

## Business rules

| Old ID | Old meaning | SRS FV ID | Resolution |
|--------|-------------|-----------|------------|
| BR-01 | Bookable only while Active | BR-01, BR-02 | Identity/call before Verified; only Verified hosts are bookable. |
| BR-02 | At most two accepted bookings per day | BR-03 | |
| BR-03 | Answer in 12 hours or expire | — | Retained default; see ADR 0004. |
| BR-04 | Tourist refund percentages | — | Configurable default; final answer deferred to #13. |
| BR-05 | Host refund and alternatives | BR-06 | |
| BR-06 | Automatic suspension | — | Out of v1. |
| BR-07 | Commission and payout | — | Out of v1. |
| BR-08 | Experience duration/group-size limits | — | Retained as validation; see ADR 0004. |

FV business rules with no old equivalent: BR-04 (no overlap), BR-05 (successful
payment required for confirmation), BR-07 (EN/AR/FR), and BR-09 (timezone and
calendar boundaries, settled in ADR 0004).

## Use cases and acceptance tests

Old `UC-xx` and `AT-xx` labels may remain informal names or test scenarios, but
tests cite the FV requirement they verify. Issue P5-1 owns the requirement-to-test
traceability file.
