<?php

use App\Support\Monitoring\TelegramAlerts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The worker container runs these (schedule:work under supervisord).
Schedule::command('sala:monitor')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// A backup that fails says so at once; sala:monitor also notices when none has landed for hours.
Schedule::command('sala:backup')->hourlyAt(15)->withoutOverlapping()
    ->when(fn () => filled(config('filesystems.disks.backups.bucket')))
    ->onFailure(fn () => app(TelegramAlerts::class)->send('🔴 Database backup failed'));
Schedule::command('sala:backup-media')->dailyAt('02:30')->timezone('Asia/Phnom_Penh')->withoutOverlapping()
    ->when(fn () => filled(config('filesystems.disks.backups.bucket')))
    ->onFailure(fn () => app(TelegramAlerts::class)->send('🔴 File backup failed'));
