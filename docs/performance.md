# Performance report (issue #38)

## Load profile (agreed here because SRS FV only says "thousands of users")

| Item | Value | Why |
|------|-------|-----|
| Catalogue | 400 verified hosts, 1-2 experiences each, 7 weekly slots each, 50 tourists | The tourism pilot is a few hundred hosts; 400 gives headroom (`LoadTestSeeder`). |
| Concurrent users to design for | 100 browsing, 20 searching per second at peak | A "thousands of registered users" site has a small fraction online at once. |
| Targets (p95) | pages 3 s (NFR-01), filtered search 2 s (NFR-02), recommendations 5 s (NFR-03), translation 5 s (NFR-04) | SRS FV. |
| Tool | `tests/load/ahla.js` (k6) and `scripts/loadcheck.php` (plain PHP, used for the numbers below) | k6 was not installed on the machine used here. |

## Measured numbers (development machine)

Windows laptop, PHP 8.3 built-in server (`php artisan serve`, **one worker, no opcache**), MySQL 8.4 local, 400 hosts, `php artisan optimize` (config, route and view cache), `scripts/loadcheck.php`, one client at a time:

| Path | p50 | p95 | max |
|------|----:|----:|----:|
| `/` home page | 327 ms | 365 ms | 405 ms |
| `/login` | 338 ms | 370 ms | 456 ms |
| `/hosts/5` host profile | 391 ms | 430 ms | 465 ms |
| `/search?city=Tunis` page | 418 ms | 475 ms | 585 ms |
| `/api/search?language=fr&interests[]=food&per_page=20` (ranked, with reasons) | 451 ms | 489 ms | 507 ms |
| `/api/search?per_page=50` (all 400 hosts ranked) | 604 ms | 673 ms | 751 ms |

All are far below the targets (3 s / 2 s / 5 s). About 330 ms of every request is the built-in server itself: the home page does almost no work yet costs that much, so the ranking of 400 hosts costs roughly 100-300 ms on top.

With 5 clients at once the same single worker answers in about 2 s (`/` 1.9 s p95, search 2.1 s p95, recommendations 2.2 s p95): the requests simply queue behind each other, about 2.5 requests per second per worker. That is a limit of this test setup, not of the code.

## What was done about it

- The recommendation loads hosts, languages, interests and experiences in four queries however many hosts match (a test fixes this at 8 queries or fewer with 300 hosts).
- The rating filter compares numbers in SQL (a text-versus-number bug found by tests on SQLite).
- The search API limit is a setting (`AHLA_SEARCH_RATE_LIMIT`, default 60 a minute per IP) because load tests from one machine would otherwise be refused.

## What this does NOT prove (stated honestly)

- Production numbers: Nginx + PHP-FPM with opcache and several workers will be faster per request and scale with the number of workers. The k6 script must be run against the deployed server (`k6 run -e BASE_URL=... tests/load/ahla.js`) and the result added here. This is a step in `docs/deployment.md`.
- Translation (NFR-04): translation runs on the queue against an external service that does not exist yet; its 5 s p95 cannot be measured until the service is deployed (ADR 0006).
- Ranking is computed in PHP for all matching hosts and then paged. That is fine for hundreds of hosts; at several thousand hosts, move the first filter to a pre-selected shortlist or cache the ranked list per city.

## Reproduce

```bash
php artisan migrate:fresh --seed
BCRYPT_ROUNDS=4 AHLA_LOAD_HOSTS=400 php artisan db:seed --class=LoadTestSeeder
AHLA_SEARCH_RATE_LIMIT=100000 php artisan serve --port=8099
php scripts/loadcheck.php http://localhost:8099 1 40 / "/search?city=Tunis" "/api/search?per_page=50"
```
