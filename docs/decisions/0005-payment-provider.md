# 5. Payment provider for v1

- **Status:** Proposed; selected by Salma pending review and provider capability verification
- **Date:** 2026-10-31

## Context

P1-6 needs a sandbox provider behind `PaymentGateway`, with safe webhook handling,
idempotency, TND amounts, and no card data stored by Ahla. ADR 0004 allows an
immediate charge at booking request time when a provider cannot authorize and
capture separately, but then decline/expiry requires a reliable refund path.

## Candidates reviewed

| Candidate | Verified from public documentation | Gaps against Ahla |
|-----------|------------------------------------|-------------------|
| **Konnect** | Sandbox and production accounts; hosted checkout and custom API integration; API accepts `TND`; payment initialization is documented as `type: immediate`; status lookup is available by payment reference. | Separate authorize/capture is not documented. The webhook guide documents a GET containing `payment_ref`, followed by server-side status lookup, but not a signed callback. A refund API, fees, amount units, and the complete status contract were not established in the reviewed public docs. |
| **Paymee** | Sandbox account and hosted checkout; documentation says it accepts Tunisian and international cards and supports API integration. The webhook checksum is documented as MD5 of token, payment status, and API token. Its posted pricing is 1.9% for national and 2.9% for international cards. | Separate authorize/capture and a refund API were not established in the reviewed public docs. The checksum construction should be reviewed with the provider before relying on it for production integrity. |

Sources:

- [Konnect getting started](https://docs.konnect.network/docs/en/overview/getting-started)
- [Konnect initiate-payment API](https://docs.konnect.network/docs/en/api-integration/endpoints/initiate-payment)
- [Konnect webhook guide](https://docs.konnect.network/docs/en/api-integration/endpoints/webhook)
- [Konnect payment details API](https://docs.konnect.network/docs/en/api-integration/endpoints/get-payment-details)
- [Paymee redirect integration and webhook checksum](https://www.paymee.tn/paymee-integration-with-redirection/)
- [Paymee pricing](https://www.paymee.tn/tarifs/)

## Decision

Select **Konnect as the intended v1 provider**, based on Salma's explicit
selection after reviewing its documented sandbox and TND checkout support.
Continue using `FakePaymentGateway` in development and tests until the adapter
is implemented and verified.

Konnect's published flow is immediate rather than authorize-then-capture. If
confirmed by Konnect, the integration must use ADR 0004's immediate-charge
fallback. Before production use, obtain provider confirmation and test evidence
for:

1. Refund creation, status lookup, and retry/idempotency behavior after decline
   or expiry.
2. Webhook integrity. The currently documented callback is only a
   `payment_ref`; the application must not trust callback-supplied payment
   status and must retrieve the canonical status using server credentials.
   Confirm with Konnect whether an additional signature mechanism is available.
3. Amount units and rounding for TND, fees, accepted payment methods, and all
   terminal/pending status values.

Do not treat a redirect or callback as proof of payment. The adapter must
validate the server-fetched payment reference, order identifier, currency,
amount, and final status; duplicate callbacks must be idempotent. Store only
provider references and statuses, never card numbers or CVV. Do not enable the
provider in production until the open checks above pass in sandbox integration
tests and are reviewed.

## Consequences

- P1-6 can proceed against the stable `PaymentGateway` boundary for provider-
  neutral behavior, but the live adapter and webhook endpoint require verified
  Konnect API details and a review of the payment contract/status model.
- The current contract's authorise/capture shape does not directly represent a
  pending hosted-checkout session. Any required contract or schema change must
  be reviewed by both module owners before implementation.
- Refund availability and callback integrity remain explicit release blockers,
  not assumed capabilities.
- Provider fees are account-dependent and must be reconfirmed before launch.
