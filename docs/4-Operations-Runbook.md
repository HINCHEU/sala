# Sala operations runbook

For whoever runs the server. Commands assume the project folder on the server.

## Set up monitoring and backups

Add to `.env`, then run `make deploy`:

| Key | Value |
| --- | --- |
| `BACKUP_KEY`, `BACKUP_SECRET`, `BACKUP_REGION`, `BACKUP_BUCKET`, `BACKUP_ENDPOINT` | A bucket on a different provider from the school files |
| `TELEGRAM_BOT_TOKEN` | From @BotFather |
| `TELEGRAM_ALERT_CHAT_ID` | The chat that receives alerts |
| `HEALTH_TOKEN` | A long random string: `openssl rand -hex 32` |

Then check each part once:

```bash
docker compose exec app php artisan sala:monitor --test    # a message arrives in Telegram
docker compose exec app php artisan sala:backup            # a file appears under db/ in the bucket
docker compose exec app php artisan sala:backup-media      # school files appear under media/
curl "https://YOUR-DOMAIN/health?token=YOUR-TOKEN"         # "ok": true
```

Finally, point an outside uptime service at the `/health` URL, polling every minute, with alerts to your phone. This is required: the built-in monitor runs inside the worker and cannot report the worker or the server going down.

**Keep `APP_KEY` somewhere safe, away from the backups.** Birth certificate numbers, ID Poor levels and disability details are encrypted with it. A restored database without the same key cannot read them.

## What runs by itself

The worker container runs these:

| Task | When |
| --- | --- |
| `sala:monitor` | Every minute |
| `sala:backup` (database, prunes old dumps) | Hourly at :15 |
| `sala:backup-media` (school files) | 02:30 Phnom Penh time |
| `horizon:snapshot` | Every 5 minutes |

## Alerts

| Alert | What to do first |
| --- | --- |
| `database`, `redis` | `docker compose ps`, then `make logs s=mysql` or `make logs s=redis` |
| `queue` | `make queue-status`, `make logs s=worker` |
| `gotenberg` | `make logs s=gotenberg`. Documents show as failed and can be generated again afterwards |
| `storage` | Check the provider's status and the `MEDIA_*` keys |
| `disk` | `df -h`, `docker system df`. Old images are usually the cause: `docker image prune` |
| `failed_jobs` | `make queue-status`; Horizon lists the error. Clears 15 minutes after the last failure |
| `errors` | `make logs s=app`. 20 or more errors in about 10 minutes |
| `backups` | Run `sala:backup` by hand and read its error |

Each alert fires once when the problem starts and once when it ends.

## Restore

Practise this on staging once per term. Target: back online in 2 hours, with at most 1 hour of data lost.

1. Download the newest `db/db-YYYYMMDD-HHMMSS.sql.gz` from the backup bucket into `backups/`.
2. Run `make restore f=backups/db-YYYYMMDD-HHMMSS.sql.gz`. It overwrites the database and asks first.
3. If school files were lost, copy `media/media_private/` and `media/media_public/` from the backup bucket back to their buckets.
4. Confirm `APP_KEY` is the original, then open a student who has a birth certificate number on record.
5. Run `make doctor`.

## Deploy rules

Deploy 19:00–22:00 Phnom Penh time, never during score-entry deadlines. `make deploy` backs up first and runs `make doctor` last.
