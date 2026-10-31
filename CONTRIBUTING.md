# Contributing & Team Workflow

This is how our team works together on this repo. Everyone follows the same
rules so we don't step on each other or break `main`. Read it once, then keep it
handy.

## 1. Golden rules

1. **Never commit directly to `main`.** `main` always works.
2. **One feature = one branch = one Pull Request (PR).**
3. **At least one teammate reviews** every PR before it merges.
4. **CI must pass** (green check) before merging.
5. **Never commit secrets** (`.env`, API keys). They belong in `.env` only.

> Branch protection is unavailable for this private repository on the current
> plan. Enforcing required PRs, one approval, and CI on `main` is **deferred**;
> owner: Nour. Until the repository is public or the plan changes, these remain
> team rules. CODEOWNERS review requests may also be unavailable on this plan.
> Automatic branch deletion after merge is enabled.

## 2. Branch naming

```
feat/<short-name>     a new feature      e.g. feat/login-page
fix/<short-name>      a bug fix          e.g. fix/empty-cart-crash
docs/<short-name>     documentation      e.g. docs/architecture
chore/<short-name>    tooling/config     e.g. chore/add-linter
```

For a planned issue, include its code: `feat/<code>-<short-name>`, e.g.
`feat/p0-5-identity-schema`.

## 3. Daily flow

```bash
git checkout main
git pull                       # get everyone's latest work
git checkout -b feat/my-thing  # start a fresh branch

# ... do work, commit as you go ...

git push -u origin feat/my-thing   # push your branch
# then open a Pull Request on GitHub
```

## 4. Commit messages

Keep them small and descriptive. We use a light version of
[Conventional Commits](https://www.conventionalcommits.org):

```
feat: add study-partner matching by course
fix: stop crash when the search box is empty
docs: document the matching algorithm
chore: add Prettier config
```

**One logical change per commit.** If your message needs the word "and", it's
probably two commits.

## 5. Pull Requests

- Fill in the PR template (it appears automatically).
- Link the issue it closes, if any (`Closes #12`).
- Keep PRs small — easier to review, fewer merge conflicts.
- If you changed behavior, update `docs/CHANGELOG.md` **in the same PR**.
- If you made a notable design choice, add an ADR in `docs/decisions/`.

## 6. Using AI assistants responsibly

AI tools (Copilot, Claude, Cursor) are encouraged — this is an AI-First course —
but you own every line you commit:

- **Read and understand** AI-generated code before committing it. You will be
  asked to explain your own PRs.
- Keep `AI_CONTEXT.md` up to date so suggestions stay relevant.
- Never paste secrets, credentials, or private user data into an AI chat.
- If AI writes a big chunk, review it like a teammate's PR: does it fit our
  conventions? Is it tested? Does it do only what we asked?

## 7. Keeping docs alive (lightweight)

- Behavior changed? → add a line to `docs/CHANGELOG.md`.
- Made a real decision (framework, data model, trade-off)? → add an ADR.
- Moved files around? → run `./scripts/update-structure.sh`.

A git hook will gently remind you if you change code without touching docs. It
only warns — it won't block you.

## 8. Resolving disagreements

Follow your team charter's conflict rule (from Lab 0). Default: discuss, then if
still split, the person who owns that area decides, and we revisit later.

## 9. Working in parallel

The phase and issue plan is `docs/PROJECT_PLAN.md`.

- Work only on issues whose dependencies are complete. Use one issue branch and
  PR per change, with `Closes #<issue>`.
- Follow `.github/CODEOWNERS` for module ownership. Ask before changing another
  owner's module; shared contracts and events require both owners' review.
- Keep shared-file changes (Composer/npm manifests, `.env.example`,
  `AI_CONTEXT.md`, `docs/architecture.md`, provider registration, and the root
  seeder) small and in their own PR when practical.
- Never edit a migration already merged to `main`; add a later migration.
  Identity/catalog migrations precede booking/payment/message migrations.
- Rebase on `origin/main` before opening the PR. Aim for fewer than 400 changed
  lines, request the other person's review within 24 hours, and squash-merge.
- Add one changelog line per PR. If a changelog conflict occurs, keep both.
- At each phase gate, stop, merge the work, then run `composer test` and
  `composer lint` on `main`.
