<?php

namespace App\Support\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/** What the system needs to work. Each check answers null when fine, or a short reason safe to show. */
class HealthChecks
{
    private const QUEUE_BACKLOG_SECONDS = 300;

    private const DISK_LIMIT_PERCENT = 80;

    /** @return array<string, ?string> */
    public function run(): array
    {
        $results = [];
        foreach (['database', 'redis', 'queue', 'gotenberg', 'storage', 'disk'] as $check) {
            try {
                $results[$check] = $this->{$check}();
            } catch (Throwable $e) {
                $results[$check] = class_basename($e);
            }
        }

        return $results;
    }

    public function database(): ?string
    {
        DB::select('select 1');

        return null;
    }

    public function redis(): ?string
    {
        Redis::connection()->ping();

        return null;
    }

    public function queue(): ?string
    {
        if (app(MasterSupervisorRepository::class)->all() === []) {
            return 'Horizon is not running';
        }

        $oldest = Queue::connection('redis')->creationTimeOfOldestPendingJob('default');

        return $oldest && now()->timestamp - (int) $oldest > self::QUEUE_BACKLOG_SECONDS ? 'Queue backlog over 5 minutes' : null;
    }

    public function gotenberg(): ?string
    {
        return Http::timeout(5)->get(config('services.gotenberg.url').'/health')->successful() ? null : 'Gotenberg is unhealthy';
    }

    public function storage(): ?string
    {
        Storage::disk('media_private')->exists('.health');

        return null;
    }

    public function disk(): ?string
    {
        $path = base_path();
        $used = (int) round((1 - disk_free_space($path) / disk_total_space($path)) * 100);

        return $used > self::DISK_LIMIT_PERCENT ? "Disk {$used}% full" : null;
    }
}
