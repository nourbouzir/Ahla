# 3. Authorise payment on request, capture on acceptance, inside a locked transaction

- **Status:** Proposed
- **Date:** 2026-10-30

## Context

Two requirement sets disagree on the payment flow:

- **SDD / SRS v1.0:** the tourist's card is *authorised* when the request is made, *captured* when the host accepts, and the authorisation is *released* if the request expires after 12 hours.
- **SRS FV (current baseline):** "confirm a booking only after the payment gateway reports successful payment" (FR-15). It does not mention authorisation, capture, expiry, or a host acceptance step beyond "confirmed booking".

Both must satisfy FR-12 (no overlap), FR-13 (max 2 confirmed bookings per host per day), NFR-13 (booking/payment records stay consistent) and NFR-14 (concurrent requests must not create duplicate or overlapping confirmed bookings).

## Decision

Implement the SDD flow, which satisfies FR-15 (a booking is only *confirmed* after a successful capture) and is stricter about money:

1. Request: in one DB transaction, lock the host's bookings for that date (`SELECT … FOR UPDATE`), check status/overlap/daily limit/group size, insert `bookings` + `payments` rows, call `PaymentGateway::authorise()`. A refused authorisation rolls everything back (no booking created).
2. Accept: repeat the lock-and-count inside a new transaction, then `capture()`. Only now is the booking *Confirmed*.
3. Decline or expiry (scheduled `ExpireRequests` job, every 10 minutes): release the authorisation and notify the tourist.
4. Amounts are stored as `DECIMAL(10,3)` in TND; only the gateway reference is stored, never card data (NFR-11).

This ADR stays **Proposed** until the team confirms with the stakeholders that "authorise, then capture on accept" is acceptable under FR-15 and picks a gateway that supports authorise/capture/refund.

## Consequences

- Easier: the daily limit and overlap rules cannot be bypassed by concurrent requests; a tourist is never charged for a request the host never accepted.
- Harder: the payment provider must support authorise/capture/refund (and payouts if the commission/payout features are kept); authorisations expire at the card network, so the 12-hour window must stay shorter than the provider's hold period; we need concurrency tests (NFR-14) to prove the locking works.
- Open: if stakeholders insist on immediate charge at request time, `Payment` would capture immediately and the expiry job would refund instead of release.
