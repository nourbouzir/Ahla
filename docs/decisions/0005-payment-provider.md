# 5. Payment provider: sandbox gateway in v1, ClicToPay as the first real adapter

- **Status:** Accepted
- **Date:** 2026-10-31
- **Issue:** #11 (P1-5)

## Context

Ahla needs to reserve ("authorise") a card payment when a tourist asks for a booking, charge ("capture") it when the host accepts, give it back ("release") if the request is declined or expires, and refund it after a cancellation (ADR 0003, ADR 0004 section 3).

What we found when comparing the options for a business in Tunisia:

| Option | Fits Tunisia? | Authorise + capture + refund | Verdict |
|--------|---------------|------------------------------|---------|
| Stripe / PayPal-style global gateways | Stripe does not support merchants registered in Tunisia (its public country list does not include Tunisia) | Yes, but unusable for us | Rejected |
| Konnect, Paymee, Flouci | Local, quick to sign up, mostly wallet or direct-charge style | Direct charge and refund; a separate authorise-then-capture step is not documented | Fallback only |
| ClicToPay (SMT / Monétique Tunisie, bank-backed) | Works for Tunisian merchants and Tunisian cards | The card scheme supports pre-authorisation and deposit; availability depends on the merchant contract | **First real adapter** |

Two things are not decided by us: a bank merchant contract takes weeks, and we cannot test against a provider without credentials. Developers and the demo still need a gateway that behaves the same way.

## Decision

1. Keep the `PaymentGateway` interface (authorise, capture, release, refund, status) as the only door to a provider. No controller or model talks to a provider.
2. v1 ships on `SandboxPaymentGateway`: it follows the same rules as a real provider (idempotency keys, declines, timeouts, refund only after capture) but moves no money. It keeps its state in the cache, so a payment authorised in one request can be captured in the next.
3. The first real adapter is written for ClicToPay once the merchant contract exists. If ClicToPay cannot authorise and capture separately, ADR 0004 section 3 already defines the fallback: charge at request time, keep the booking `requested`, and refund in full on decline or expiry. The payment statuses do not change.
4. Switching is a one-line change in `.env` (`AHLA_PAYMENT_GATEWAY=<adapter class>`). Webhooks are signed with `AHLA_PAYMENT_WEBHOOK_SECRET`; an empty secret turns the endpoint off.
5. Payouts to hosts are out of v1 (ADR 0004).

## Consequences

- Everything else (booking flow, refunds, audit, tests) is finished and demonstrable without a bank contract.
- Real money cannot move until the adapter and the contract exist. This is the largest open launch risk and is stated in the setup report.
- We added `release()` to the interface: without it an unused reservation would stay on the tourist's card until the bank expires it.
- Provider facts above come from public sources and must be re-checked with the bank before the adapter is built.
