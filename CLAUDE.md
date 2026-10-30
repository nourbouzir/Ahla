# CLAUDE.md

This file gives Claude (Claude Code, the Claude desktop/web app, and the Claude
VS Code extension) context about this repository.

**Read [`AI_CONTEXT.md`](AI_CONTEXT.md) first — it is the single source of truth**
for the project summary, tech stack, structure, and conventions. Everything below
just adds Claude-specific working notes.

## How to work in this repo

- Follow every rule in `AI_CONTEXT.md` (style, secrets, dependencies, tests, commits).
- Before making changes, skim `docs/architecture.md` and the latest entries in
  `docs/decisions/` so your changes fit existing decisions.
- Keep changes small and focused. Explain non-obvious choices in the PR description.
- When behavior changes, update `docs/CHANGELOG.md` in the same change.
- Run the project's tests before claiming something works; report real output.

## Useful commands

```bash
# install deps:   composer install && npm install
# run the app:    php artisan serve         (queue: php artisan queue:work)
# run tests:      composer test             (in-memory SQLite, no MySQL needed)
# lint:           composer lint             (Laravel Pint, check only; CI runs this)
# fix style:      composer lint:fix
# migrate:        php artisan migrate --seed
# run schedule:   php artisan schedule:work
```

## Project-specific guidance

- Requirements: the current baseline is `docs/requirements/Ahla_SRS_FV.docx`. The SDD
  (`Ahla_SDD.docx`) uses the *older* SRS numbering — see `AI_CONTEXT.md` §6 before
  citing an FR/NFR id, and never mix the two numberings in one sentence.
- Never put the daily-limit, overlap, refund, or status-transition logic in a
  controller or model. It belongs in `BookingService`, `RefundPolicy` classes and
  the State classes respectively, and it needs a unit test.
- Anything that changes a host/booking/payment status must also write `audit_log`.
- Don't implement Candidate features (SRS FV FR-21 to FR-25) or pick a payment
  provider on your own; ask first.
