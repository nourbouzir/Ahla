# Usability test with six first-time tourists (issue #42)

> **Status: script ready, sessions NOT run.** The result of a usability test is what real people do. It cannot be produced by the team's code or by an AI assistant, and the issue stays open until six real sessions are recorded below. Do not fill in numbers that were not observed.

## Decisions for the open points in SRS FV

| Question | Decision |
|----------|----------|
| Locale | Three sessions in English, two in French, one in Arabic (checks right-to-left on a phone) |
| Payment included? | Yes, with the sandbox gateway. Tell participants "no real money moves"; the card step is the reservation message. When a real gateway exists, repeat two sessions. |
| Environment | Deployed staging server (`docs/deployment.md`) with the demo data (`docs/demo.md`), participants on their own phones |
| Participants | Six people who have never seen Ahla and have travelled abroad; not students of this course; mixed ages |

## Task script (read aloud, one task at a time, say nothing else)

1. "You are visiting Tunis next week and like food. Find a local host who speaks French."
2. "Say why the site thinks this host fits you."
3. "Create an account and request a booking with this host for two people."
4. "The host accepted. Where do you see that, and what can you see now that you could not before?"
5. "Ask the host if they can meet 30 minutes later."
6. "Something came up. Cancel the booking. What do you expect to get back?"

## What to record per participant

| Field | Notes |
|-------|-------|
| Language, device, age band | |
| Time for tasks 1, 3, 6 (seconds) | stopwatch from reading the task to the participant saying "done" |
| Errors | wrong taps, dead ends, wrong expectation |
| Quotes | exact words, in the participant's language |
| Satisfaction | 1 (bad) to 5 (good) at the end |

## Pass / fail target (proposed, for the team to approve)

- At least 5 of 6 finish tasks 1 to 4 without help.
- Median time to request a booking (task 3, including sign-up) under 5 minutes.
- No participant fails to find the cancellation.
- Every problem seen twice or more becomes a GitHub issue labelled `bug`.

## Results

| # | Language | Task 1 | Task 3 | Task 6 | Errors | Score | Main problem |
|---|----------|--------|--------|--------|--------|-------|--------------|
| 1 | | | | | | | |
| 2 | | | | | | | |
| 3 | | | | | | | |
| 4 | | | | | | | |
| 5 | | | | | | | |
| 6 | | | | | | | |

**Verdict against the target:** _pending until the six sessions are done._
