<?php

namespace App\Console\Commands;

use App\Support\Monitoring\ErrorCounter;
use App\Support\Monitoring\HealthChecks;
use App\Support\Monitoring\TelegramAlerts;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Runs every minute and tells Telegram when a problem starts and when it ends, not on every run. It lives
 * in the worker, so it cannot report the worker or the server dying: an outside poller of /health covers that.
 */
class Monitor extends Command
{
    protected $signature = 'sala:monitor {--test : Send a test alert and stop}';

    protected $description = 'Check the system and alert Telegram when something starts or stops failing';

    private const STATE_KEY = 'monitor:problems';

    private const BACKUP_MAX_AGE_HOURS = 3;

    private const ERROR_SPIKE = 20;

    public function handle(HealthChecks $health, TelegramAlerts $alerts): int
    {
        if ($this->option('test')) {
            $sent = $alerts->send('Test alert: monitoring reaches this chat.');
            $sent ? $this->info('Sent.') : $this->error('Not sent: check TELEGRAM_BOT_TOKEN and TELEGRAM_ALERT_CHAT_ID.');

            return $sent ? self::SUCCESS : self::FAILURE;
        }

        $problems = array_filter([...$health->run(), ...$this->operational()]);
        $store = ErrorCounter::store();
        $known = $store->get(self::STATE_KEY, []);

        foreach ($problems as $check => $reason) {
            // Not marked as known unless it reached Telegram, so a failed send is tried again next minute.
            if (! isset($known[$check]) && $alerts->send("🔴 {$check}: {$reason}")) {
                $known[$check] = $reason;
            }
        }
        foreach (array_diff_key($known, $problems) as $check => $reason) {
            if ($alerts->send("✅ {$check}: back to normal")) {
                unset($known[$check]);
            }
        }
        $store->forever(self::STATE_KEY, $known);

        return self::SUCCESS;
    }

    /** @return array<string, ?string> */
    private function operational(): array
    {
        return [
            'failed_jobs' => $this->failedJobs(),
            'errors' => ErrorCounter::recent() >= self::ERROR_SPIKE ? ErrorCounter::recent().' errors in 10 minutes' : null,
            'backups' => $this->backups(),
        ];
    }

    private function failedJobs(): ?string
    {
        try {
            $count = DB::table('failed_jobs')->where('failed_at', '>=', now()->subMinutes(15))->count();
        } catch (Throwable $e) {
            return class_basename($e);
        }

        return $count > 0 ? "{$count} failed in 15 minutes" : null;
    }

    private function backups(): ?string
    {
        if (! filled(config('filesystems.disks.backups.bucket'))) {
            return app()->isProduction() ? 'BACKUP_BUCKET is not set' : null;
        }

        try {
            // Listing a bucket every minute is wasteful, and a backup only moves this once an hour.
            $newest = ErrorCounter::store()->remember('monitor:newest-backup', 600, fn () => collect(Storage::disk('backups')->files(BackupDatabase::DIRECTORY))
                ->map(fn (string $path) => preg_match('/db-(\d{8}-\d{6})\.sql\.gz$/', $path, $m) ? $m[1] : null)->filter()->max());
        } catch (Throwable $e) {
            return class_basename($e);
        }

        return ! $newest || CarbonImmutable::createFromFormat('Ymd-His', $newest, 'UTC')->lt(now()->subHours(self::BACKUP_MAX_AGE_HOURS))
            ? 'No database backup in '.self::BACKUP_MAX_AGE_HOURS.' hours'
            : null;
    }
}
