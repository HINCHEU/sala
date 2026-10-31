<?php

namespace App\Support\Monitoring;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Counts reported exceptions per five minutes, for the error spike alert. */
class ErrorCounter
{
    public static function store(): Repository
    {
        return Cache::store(config('services.monitoring.cache_store'));
    }

    public static function hit(): void
    {
        try {
            $key = self::key(now()->timestamp);
            self::store()->add($key, 0, 900);
            self::store()->increment($key);
        } catch (Throwable) {
            // Counting must never get in the way of reporting the real error.
        }
    }

    /** Errors in the current five minutes and the five before. */
    public static function recent(): int
    {
        $now = now()->timestamp;

        return (int) self::store()->get(self::key($now), 0) + (int) self::store()->get(self::key($now - 300), 0);
    }

    private static function key(int $timestamp): string
    {
        return 'monitor:errors:'.intdiv($timestamp, 300);
    }
}
