# 7. Indicative revenue in the admin space, and locking accounts

- **Status:** Accepted
- **Date:** 2026-11-01

## Context

The admin space needs "profit monitoring" and "user monitoring". Two things in ADR 0004 are in the way:

1. Commission and host payouts are **out of v1**: Ahla charges nothing on top of the experience price, so there is no real profit to measure.
2. There is no way to stop a bad account other than suspending a guide.

## Decision

1. **Revenue is shown as an indicative figure, never as a ledger entry.** The Revenue page and the overview show three real numbers: money paid by clients, money refunded, and net volume (paid minus refunded), counted from payments that were really captured. Next to them it shows *indicative revenue* = net volume x `AHLA_COMMISSION_RATE` (default 10%). It is labelled as a planning figure on every screen, and nothing is ever charged or paid out from it. If the team later adopts a commission, the number becomes real in one place (`AdminStats`) and this ADR is superseded.
2. **Admins can lock an account** (`users.disabled_at`, with a reason). A locked user is refused at login, and a session that is already open is closed on its next request and deleted from the sessions table. Their history stays. The action needs a reason, is written to the audit log, and can be undone. Admin accounts cannot be locked from the panel, so nobody can lock out the people who could undo it.
3. **Demo logins** are shown on the login page only outside production (`AHLA_SHOW_DEMO_LOGINS`, default on except in production). The admin password in production comes from `AHLA_ADMIN_PASSWORD` or is generated once by the seeder.

## Consequences

- The numbers can be trusted for what they are, and nobody can mistake a planning figure for accounting.
- Account locking adds one column pair and one middleware that runs on every web request (a single comparison on the already loaded user).
- Real commission, payouts to guides and VAT remain open decisions for the team.
