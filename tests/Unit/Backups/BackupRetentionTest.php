<?php

use App\Support\Backups\BackupRetention;
use Carbon\CarbonImmutable;

$now = fn () => CarbonImmutable::parse('2026-10-31 12:00:00', 'UTC');
$name = fn (string $at) => 'db/db-'.CarbonImmutable::parse($at, 'UTC')->format('Ymd-His').'.sql.gz';

it('keeps every dump from the last 48 hours', function () use ($now, $name) {
    expect(BackupRetention::expired([$name('2026-10-31 11:15'), $name('2026-10-31 10:15'), $name('2026-10-29 13:15')], $now()))->toBeEmpty();
});

it('keeps only the newest of each older day for 30 days', function () use ($now, $name) {
    $expired = BackupRetention::expired([$name('2026-10-20 23:15'), $name('2026-10-20 22:15'), $name('2026-10-20 01:15')], $now());

    expect($expired)->toEqualCanonicalizing([$name('2026-10-20 22:15'), $name('2026-10-20 01:15')]);
});

it('keeps the newest of each month for 12 months, then nothing', function () use ($now, $name) {
    $expired = BackupRetention::expired([$name('2026-03-28 23:15'), $name('2026-03-10 23:15'), $name('2025-03-28 23:15')], $now());

    expect($expired)->toEqualCanonicalizing([$name('2026-03-10 23:15'), $name('2025-03-28 23:15')]);
});

it('never picks a name it cannot read', function () use ($now) {
    expect(BackupRetention::expired(['db/notes.txt', 'db/db-old.sql.gz'], $now()))->toBeEmpty();
});
