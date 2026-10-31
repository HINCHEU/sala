# 0009 — M8 pilot hardening choices

Date: 2026-10-28 · Status: accepted

## Decisions

1. **The activity log gains `school_id`.** It had no link to a school, so a director could not be shown only their own. `SchoolActivity` stamps new rows from the current school, or from the subject when the event is about a school. It has no global scope, because the platform logs without a current school; readers filter themselves. Older rows stay null and are not shown.
2. **Only the director sees the log** (`school.view-activity`). It shows plain old and new values only; a model event's attribute arrays are left out.
3. **Hourly dumps replace the plan's nightly dump plus hourly binlog.** A school's database is small, and `mysqldump --single-transaction` does not block writes, so the same one-hour limit on lost data comes with no binlog shipping to run and restore. Retention: everything for 48 hours, the newest per day for 30 days, the newest per month for 12 months.
4. **Files are copied by `sala:backup-media`, not `rclone`.** It uses the app's own disks and needs no new tool in the image. Stored file names are unique and never rewritten, so copying what is missing is enough. Deleted files stay in the backup.
5. **Alerts fire when a problem starts and when it ends**, not every minute. A problem counts as told only once Telegram accepts it, so a failed send retries next minute.
6. **Monitoring state lives in the file cache**, so Redis failing does not take its own alert with it.
7. **`/health` needs a token and sits outside the web middleware.** Its answer describes the system, and it must respond when sessions or tenancy cannot. `/up` stays public.
8. **`sala:monitor` cannot report its own death.** It runs in the worker, so an outside poller of `/health` is required to catch the worker or server going down.

## Performance

Measured by `tests/Feature/PerformanceTest.php` on MySQL 8.0 on a development machine, with one request at a time:

| Measure | Target | Result |
| --- | --- | --- |
| Score grid, 50 students × 15 subjects | under 1 s | 0.13 s, 38 queries |
| Class result sheet, 50 × 15 | none set | 0.10 s, 30 queries |
| Results for a grade of 300 students | under 10 s | 0.31 s |
| Results for a grade of 1,800 students | under 10 s | 1.7 s |

No code change was needed. The server's 2 vCPU droplet and real load remain to be measured.

## Not yet

- Error tracking with stack traces. The alert gives only a count of errors, and the details are in the logs.
- Weekly look at slow queries; the MySQL slow query log is not switched on.
