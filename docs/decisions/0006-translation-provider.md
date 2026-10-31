# 6. Translation provider: a LibreTranslate-compatible service behind the Translator adapter

- **Status:** Accepted
- **Date:** 2026-10-31
- **Issue:** #31 (P4-2)

## Context

Tourists and hosts chat in English, French or Arabic (FR-19). Messages must be translated without ever blocking or losing a message, and only the text may leave our servers.

| Option | Cost | Privacy | Arabic / French / English | Verdict |
|--------|------|---------|---------------------------|---------|
| Google Cloud Translation | Pay per character | Text goes to a third party | Excellent | Good quality, adds a recurring bill and a vendor |
| DeepL API | Pay per character | Text goes to a third party | Good | Same trade-off |
| LibreTranslate (open source, self-hosted or hosted) | Free to self-host, small server | Text stays on infrastructure we choose | Supported | **Chosen as the default adapter** |

## Decision

1. `LibreTranslateTranslator` implements the existing `Translator` contract and talks to any LibreTranslate-compatible HTTP API (`POST /translate` with text, source, target). It is configured by `AHLA_TRANSLATE_URL` (and an optional `AHLA_TRANSLATE_API_KEY`). Another provider only needs another class and one line in `.env` (`AHLA_TRANSLATOR`).
2. The message is saved first with its original text. Translation runs in the queued `TranslateMessage` job (3 attempts, 5 s then 30 s back-off, 5 s HTTP timeout). Only the message text and the two language codes are sent: no names, no contact details.
3. Source language is the sender's interface language, target is the recipient's. If they are the same, nothing is translated.
4. If the service is not configured, is down or answers badly, the chat shows the original text with "Translation unavailable". Sending is never affected.
5. Only the pairs among `en`, `fr` and `ar` are accepted.

## Consequences

- No per-message cost and no third party sees the conversations, but quality is lower than the paid services and we must run (or rent) the translation server.
- The target "under 500 characters within 5 s for 95% of requests" cannot be proven without a running server. The job measures nothing by itself; it is verified in the performance report (issue #38) once the service is deployed. Until then the claim is untested and is listed as an open risk.
- Switching to a paid provider later is a new adapter class, not a redesign.
