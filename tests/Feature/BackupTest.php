<?php

use App\Support\Files\FileStore;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('backups');
});

/** Stands in for mysqldump and gzip, writing the files the real ones would. */
function fakeDump(): void
{
    Process::fake(function (PendingProcess $process) {
        $command = (array) $process->command;
        if ($command[0] === 'mysqldump') {
            $file = substr(collect($command)->first(fn ($a) => str_starts_with($a, '--result-file=')), 14);
            file_put_contents($file, 'dump');
        } else {
            rename(end($command), end($command).'.gz');
        }

        return Process::result();
    });
}

it('uploads a compressed dump and keeps the password out of the arguments', function () {
    fakeDump();
    config(['database.connections.mysql.password' => 'hunter2']);

    $this->artisan('sala:backup')->assertSuccessful();

    $files = Storage::disk('backups')->files('db');
    expect($files)->toHaveCount(1)->and($files[0])->toMatch('/^db\/db-\d{8}-\d{6}\.sql\.gz$/');
    Process::assertRan(fn (PendingProcess $p) => ((array) $p->command)[0] === 'mysqldump'
        && ! in_array('hunter2', (array) $p->command, true)
        && $p->environment['MYSQL_PWD'] === 'hunter2');
});

it('fails, uploading nothing, when the dump fails', function () {
    Process::fake(fn () => Process::result(exitCode: 1, errorOutput: 'denied'));

    expect(fn () => $this->artisan('sala:backup'))->toThrow(Exception::class);
    expect(Storage::disk('backups')->files('db'))->toBeEmpty();
});

it('prunes expired dumps but never a file it does not recognise', function () {
    fakeDump();
    Storage::disk('backups')->put('db/db-20200105-010000.sql.gz', 'x');
    Storage::disk('backups')->put('db/notes.txt', 'x');

    $this->artisan('sala:backup')->assertSuccessful();

    Storage::disk('backups')->assertMissing('db/db-20200105-010000.sql.gz');
    Storage::disk('backups')->assertExists('db/notes.txt');
});

it('copies only the school files not backed up yet', function () {
    Storage::fake(FileStore::PRIVATE_DISK);
    Storage::fake(FileStore::PUBLIC_DISK);
    Storage::disk(FileStore::PRIVATE_DISK)->put('schools/1/a.pdf', 'new');
    Storage::disk(FileStore::PRIVATE_DISK)->put('schools/1/b.pdf', 'changed locally');
    Storage::disk(FileStore::PUBLIC_DISK)->put('logo.png', 'logo');
    Storage::disk('backups')->put('media/media_private/schools/1/b.pdf', 'already here');

    $this->artisan('sala:backup-media')->expectsOutput('Copied 2 files.')->assertSuccessful();

    expect(Storage::disk('backups')->get('media/media_private/schools/1/a.pdf'))->toBe('new')
        ->and(Storage::disk('backups')->get('media/media_public/logo.png'))->toBe('logo')
        ->and(Storage::disk('backups')->get('media/media_private/schools/1/b.pdf'))->toBe('already here');
});
