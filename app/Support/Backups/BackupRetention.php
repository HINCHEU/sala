<?php

namespace App\Support\Backups;

use Carbon\CarbonImmutable;

/**
 * Which database dumps to keep: all from the last 48 hours, the newest of each day for 30 days,
 * the newest of each month for 12 months.
 */
final class BackupRetention
{
    /**
     * @param  list<string>  $paths  dump paths named db-YYYYMMDD-HHMMSS.sql.gz
     * @return list<string> the paths to delete; a name that does not parse is never deleted
     */
    public static function expired(array $paths, CarbonImmutable $now): array
    {
        $dated = [];
        foreach ($paths as $path) {
            if (preg_match('/db-(\d{8}-\d{6})\.sql\.gz$/', $path, $m)) {
                $dated[$path] = CarbonImmutable::createFromFormat('Ymd-His', $m[1], 'UTC');
            }
        }
        arsort($dated);

        $keep = [];
        $days = [];
        $months = [];
        foreach ($dated as $path => $at) {
            $newestOfDay = ! isset($days[$at->format('Ymd')]);
            $newestOfMonth = ! isset($months[$at->format('Ym')]);
            $days[$at->format('Ymd')] = true;
            $months[$at->format('Ym')] = true;

            if ($at->gte($now->subHours(48))
                || ($newestOfDay && $at->gte($now->subDays(30)))
                || ($newestOfMonth && $at->gte($now->subMonths(12)))) {
                $keep[] = $path;
            }
        }

        return array_values(array_diff(array_keys($dated), $keep));
    }
}
