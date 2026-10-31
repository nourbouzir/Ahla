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

> GitHub branch protection is not available on our plan (private repo), so rules
> 1–4 are enforced by us, not by GitHub. If the repo goes public or the plan is
> upgraded, turn on *Require a pull request*, *1 approval* and the *CI* check for
> `main` (Settings → Branches) and tick that box on issue #3.

## 2. Branch naming

```
feat/<short-name>     a new feature      e.g. feat/login-page
fix/<short-name>      a bug fix          e.g. fix/empty-cart-crash
docs/<short-name>     documentation      e.g. docs/architecture
chore/<short-name>    tooling/config     e.g. chore/add-linter
```

For a planned issue, put its code in the name: `feat/<code>-<short-name>`, e.g.
`feat/p0-5-identity-schema` for issue P0-5 (#5).

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

## 9. Working in parallel (two people, one codebase)

The plan (phases, issues, who owns what) is `docs/PROJECT_PLAN.md`. The rules
below are what keep our work separate while we build and let it merge cleanly.

**Choosing work**
- Take an issue assigned to you whose *Depends on* boxes are all ticked.
- Branch `feat/<code>-<short-name>` (see section 2); open the PR with `Closes #<issue>`.

**Staying out of each other's way**
- **Stay in your own modules.** Ownership is listed in `.github/CODEOWNERS`
  (Nour: Auth, Verification, Experience, Matching, Notification, Admin. Salma:
  Booking, Payment, Messaging, `app/Contracts`, `app/Events`, `app/Listeners`,
  `app/Jobs`). Every module keeps its own routes, migrations, views, language
  files and seeder in its folder, so you rarely edit the same file.
- **Need a change in the other person's module?** Ask on the issue, or make the
  change in its own tiny PR and request their review. Don't mix it into a feature PR.
- **Contracts need both.** After P0-4, any change to `app/Contracts/**` or
  `app/Events/**` needs both of us on the PR, because both tracks code against them.
- **Shared files get a PR of their own** and are merged first, so the other person
  rebases onto them: `composer.json` / `composer.lock`, `package.json` /
  `package-lock.json`, `.env.example`, `AI_CONTEXT.md`, `docs/architecture.md`,
  `bootstrap/providers.php`. Adding a dependency is its own tiny PR with the reason.
- **Migrations:** never edit a migration that is already on `main`; add a new one.
  Identity tables (Nour) are created before booking tables (Salma), which reference them.

**Keeping `main` mergeable**
- **Rebase every morning**, and before opening your PR:
  `git fetch origin && git rebase origin/main`. Resolve conflicts on your branch, never on `main`.
- **Small PRs:** aim for under ~400 changed lines (not counting lock files); split bigger work.
- **The other person reviews, within 24 hours.** Nobody approves their own PR. If you
  are blocked, comment on the issue and mention them.
- **Squash-merge.** The PR title becomes the commit message, so write it as a
  Conventional Commit. The branch is deleted automatically after the merge.
- **`docs/CHANGELOG.md`:** one line per PR, added at the end of the right list. If it
  conflicts, keep both lines.

**At the end of each phase** we both stop, merge everything, and run `composer test`
and `composer lint` on `main` before the next phase starts.
