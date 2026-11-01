# 8. Admin and guide tools beyond the SRS FV baseline

- **Status:** Accepted
- **Date:** 2026-11-01

## Context

SRS FV lists "broad administrator tools" as **Candidate FR-24** (not approved) and says the UI must "prevent unapproved Candidate management tools". `AI_CONTEXT.md` forbids building Candidate features without approval. The product owner has since asked, in writing, for a full admin dashboard (user, guide and revenue monitoring) and a guide dashboard (clients, private notes, inbox, calendar, earnings).

## Decision

1. **FR-24 is approved in a narrow form**, by the product owner's request: admins can view platform statistics, search users and guides, lock and unlock accounts (ADR 0007), see all bookings and read the audit timeline. Every action is role-restricted (NFR-10) and written to the audit log (FR-24 acceptance: "administrator actions are recorded").
2. **Still not approved and not built:** content moderation, user reports, disputes, reviews (FR-23), likes and favourites (FR-21/22), social login (FR-25). The `Candidate` rows in `docs/traceability.md` stay unticked.
3. **Guide tools use only data the guide already has a right to:** their own bookings and the tourists in them. A client's e-mail and phone follow the same visibility rule as everywhere else (confirmed booking, and the phone only if the tourist shares it; FR-04, NFR-12). Private notes are readable only by the guide who wrote them.
4. **The activity timeline follows NFR-12 and NFR-19:** it records who did what and when. It never stores passwords, message text, or an e-mail typed into a failed login. A failed login stores only the IP address as security metadata; a successful login stores none. Audit rows are append-only: the application refuses to edit or delete them.
5. **Idle session timeout stays 30 minutes** (ADR 0004 section 15). An expired form shows a friendly "page expired" message and does not lose the visitor's place.

## Consequences

- The Candidate approval is explicit and bounded, so a reviewer can tell what FR-24 covers.
- Anything in item 2 needs its own ADR before it is built.
- The earlier 120-minute session setting was a deviation from ADR 0004 and is reverted.
