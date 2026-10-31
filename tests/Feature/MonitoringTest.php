<?php

use App\Support\Monitoring\ErrorCounter;
use App\Support\Monitoring\HealthChecks;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** Health checks that answer as told, since tests have no Redis or Horizon. */
function fakeHealth(array $problems = []): void
{
    app()->instance(HealthChecks::class, new class($problems) extends HealthChecks
    {
        public function __construct(private array $problems) {}

        public function run(): array
        {
            return [...['database' => null, 'redis' => null], ...$this->problems];
        }
    });
}

function telegramTexts(): array
{
    return Http::recorded()->map(fn ($pair) => $pair[0]['text'])->all();
}

beforeEach(function () {
    config([
        'services.telegram.bot_token' => 'token',
        'services.telegram.alert_chat_id' => '42',
        'services.monitoring.health_token' => 'secret',
        'filesystems.disks.backups.bucket' => null,
    ]);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    fakeHealth();
});

it('keeps /health behind its token', function () {
    $this->get('/health')->assertNotFound();
    $this->get('/health?token=wrong')->assertNotFound();
    config(['services.monitoring.health_token' => '']);
    $this->get('/health?token=')->assertNotFound();
});

it('reports the checks and answers 503 when one fails', function () {
    $this->get('/health?token=secret')->assertOk()->assertJson(['ok' => true, 'checks' => ['database' => 'ok']]);

    fakeHealth(['redis' => 'ConnectionException']);
    $this->get('/health?token=secret')->assertStatus(503)->assertJson(['ok' => false, 'checks' => ['redis' => 'ConnectionException']]);
});

it('checks the database, Gotenberg and storage for real', function () {
    Storage::fake('media_private');
    Http::fake(['*/health' => Http::sequence()->push('ok')->push('down', 503)]);
    $checks = new HealthChecks;

    expect($checks->database())->toBeNull()
        ->and($checks->storage())->toBeNull()
        ->and($checks->gotenberg())->toBeNull()
        ->and($checks->gotenberg())->toBe('Gotenberg is unhealthy');
});

it('alerts once when a problem starts and once when it ends', function () {
    fakeHealth(['redis' => 'ConnectionException']);
    $this->artisan('sala:monitor')->assertSuccessful();
    $this->artisan('sala:monitor')->assertSuccessful();
    expect(telegramTexts())->toHaveCount(1)->and(telegramTexts()[0])->toContain('redis: ConnectionException');

    fakeHealth();
    $this->artisan('sala:monitor')->assertSuccessful();
    expect(telegramTexts())->toHaveCount(2)->and(telegramTexts()[1])->toContain('redis: back to normal');
});

it('tries again next minute when an alert did not get through', function () {
    Http::swap(new Factory);
    Http::fake(['api.telegram.org/*' => Http::sequence()->push('nope', 500)->push(['ok' => true])]);
    fakeHealth(['redis' => 'ConnectionException']);

    $this->artisan('sala:monitor')->assertSuccessful();
    $this->artisan('sala:monitor')->assertSuccessful();
    $this->artisan('sala:monitor')->assertSuccessful();

    Http::assertSentCount(2);
});

it('alerts on recently failed jobs and an error spike', function () {
    DB::table('failed_jobs')->insert([
        'uuid' => 'u', 'connection' => 'redis', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now(),
    ]);
    foreach (range(1, 20) as $i) {
        ErrorCounter::hit();
    }

    $this->artisan('sala:monitor')->assertSuccessful();

    expect(implode("\n", telegramTexts()))->toContain('failed_jobs: 1 failed')->toContain('errors: 20 errors');
});

it('alerts when no recent backup has landed, and not when one has', function () {
    config(['filesystems.disks.backups.bucket' => 'b']);
    Storage::fake('backups');
    Storage::disk('backups')->put('db/db-'.now('UTC')->subHours(5)->format('Ymd-His').'.sql.gz', 'x');
    $this->artisan('sala:monitor')->assertSuccessful();
    expect(implode("\n", telegramTexts()))->toContain('backups: No database backup');

    cache()->store('array')->forget('monitor:newest-backup');
    Storage::disk('backups')->put('db/db-'.now('UTC')->format('Ymd-His').'.sql.gz', 'x');
    $this->artisan('sala:monitor')->assertSuccessful();
    expect(implode("\n", telegramTexts()))->toContain('backups: back to normal');
});

it('sends a test alert on request, and fails plainly when not configured', function () {
    $this->artisan('sala:monitor --test')->assertSuccessful();
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'bottoken/sendMessage') && $r['chat_id'] === '42');

    config(['services.telegram.alert_chat_id' => null]);
    $this->artisan('sala:monitor --test')->assertFailed();
});
