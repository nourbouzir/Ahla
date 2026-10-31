# Runbook: reliability, backups and recovery (issue #39)

## 1. What is protected and how it was tested

| Risk | Protection | Evidence |
|------|------------|----------|
| Two hosts or two clicks confirm overlapping or a third booking in a day | Host row lock, then re-count, in one transaction (`BookingService`) | `BookingConcurrencyTest` (real parallel processes on MySQL). Removing the lock makes it fail; checked. |
| Many tourists and hosts at once, with the payment gateway failing | Idempotent gateway steps, rollback on failed authorise, `ReconcilePayments` recovery | `BookingSoakTest`: 12-18 parallel requests, every booking accepted twice in parallel, 20-30% of gateway calls failing on purpose (timeout, decline, **answer lost after the gateway acted**). Afterwards: never more than 2 confirmed per host per day, no overlap, every confirmed booking paid, nothing captured for an unconfirmed booking. Without the lock the same test fails; checked. |
| Gateway captured the money but we never heard back | `ReconcilePayments` asks the gateway, then confirms, or refunds and declines if the slot was taken | `ReliabilityTest` |
| Refund failed after a cancellation | Cancellation stays; the refund is retried every 15 minutes with the clock of the cancellation | `CancellationAndJobsTest` |
| Translation service down | Original text shown, message never lost | `MessagingTest` |
| Mail provider down | In-app notification already stored; mail retried 3 times; action unaffected | `NotificationTest` |

Run the heavy tests yourself:

```bash
AHLA_MYSQL_TESTS=1 AHLA_SOAK_PROCESSES=18 AHLA_SANDBOX_FAULT_RATE=0.3 AHLA_SOAK_VERBOSE=1 \
DB_CONNECTION=mysql DB_DATABASE=ahla_test DB_USERNAME=... DB_PASSWORD=... php artisan test --filter="BookingSoak|BookingConcurrency"
```

Known residual risk: if the gateway authorises but the answer is lost, the reservation exists at the gateway with no booking on our side. With the sandbox it does no harm; with a real provider, ask it for a list of unmatched authorisations once a day (add to the adapter when the provider is chosen).

## 2. Backups

| What | How | Where |
|------|-----|-------|
| MySQL database | `scripts/backup.sh` (`mysqldump --single-transaction`, gzip) | `<dir>/<timestamp>/db.sql.gz` |
| ID evidence and other private files | same script (`tar.gz` of `storage/app/private`; the files are already encrypted) | `<dir>/<timestamp>/files.tar.gz` |
| Integrity | `SHA256SUMS` in each folder, checked again by the restore script | |
| Retention | 14 days on the server (`AHLA_BACKUP_KEEP_DAYS`) | |
| Off-site copy | copy the newest folder to another machine or storage every day (rsync, rclone) | set up at deployment |

Cron (server): `30 2 * * * cd /var/www/ahla && scripts/backup.sh /var/backups/ahla >> storage/logs/backup.log 2>&1`

## 3. Restore (what to do when something is lost)

1. Put the site in maintenance mode: `php artisan down`.
2. Pick the newest good folder in the backup directory.
3. `scripts/restore.sh <folder> [database] --with-files` (it verifies the checksums first).
4. `php artisan migrate --force` (applies migrations newer than the backup), then `php artisan up`.
5. Log in as admin, open Bookings and the audit log, and check the latest rows against what users report.
6. Run `php artisan queue:restart`. The scheduler's `ReconcilePayments` repairs half-finished payments on its next run.

## 4. Restore drill (done 2026-10-31, local machine)

| Step | Result |
|------|--------|
| Source | `ahla_db` on MySQL 8.4 with the demo seed (15 users, 2 bookings, 6 audit rows, payments total 346.000 TND) plus a sample private file |
| Backup | `scripts/backup.sh` finished, folder 21 KB, checksums written |
| Restore | `scripts/restore.sh <folder> ahla_restore_check`: checksums OK, database created and loaded in **about 3 seconds** |
| Check | exact counts in the restored database: users 15 = 15, bookings 2 = 2, audit rows 6 = 6, payments sum 346.000 = 346.000 |
| Found and fixed | `mysqldump` needed the PROCESS privilege for tablespaces: the script now passes `--no-tablespaces`. |

A drill on the real server with real data volume must be repeated after deployment and the date added here.

## 5. Proposed RPO and RTO (for the team to approve; SRS FV NFR-15 leaves them open)

| Target | Proposal | Reasoning |
|--------|----------|-----------|
| **RPO** (data we can lose) | **24 hours** now; **1 hour** once MySQL binary logs are copied off-site | A daily dump is the cheapest thing that works. Losing a day of bookings is painful but survivable for a pilot; payments are also recorded at the gateway. |
| **RTO** (time to be back) | **4 hours** | Restore took seconds here; most of the time is provisioning a server, DNS and checking data. |

## 6. Everyday checks

- `php artisan schedule:list` shows ExpireRequests, AutoComplete, ReconcilePayments and PurgeIdDocuments.
- `php artisan queue:failed` should be empty; failed mail or translation jobs land there.
- Admin > Audit log shows who changed what.
