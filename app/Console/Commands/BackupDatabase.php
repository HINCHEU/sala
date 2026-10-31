<?php

namespace App\Console\Commands;

use App\Support\Backups\BackupRetention;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/** Dumps the database to the backups disk, then removes dumps past their retention. */
class BackupDatabase extends Command
{
    protected $signature = 'sala:backup';

    protected $description = 'Dump the database to the backups disk and prune old dumps';

    public const DIRECTORY = 'db';

    public function handle(): int
    {
        $db = config('database.connections.mysql');
        $now = CarbonImmutable::now('UTC');
        $name = 'db-'.$now->format('Ymd-His').'.sql';
        $dump = sys_get_temp_dir().'/'.$name;

        try {
            // The password goes through the environment, never the process list.
            // --skip-ssl: the image's MariaDB client insists on verifying a certificate the private MySQL does not have.
            Process::env(['MYSQL_PWD' => (string) $db['password']])->timeout(600)->run([
                'mysqldump', '--skip-ssl', '--single-transaction', '--routines', '--no-tablespaces',
                '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'],
                '--result-file='.$dump, $db['database'],
            ])->throw();
            Process::timeout(600)->run(['gzip', '-f', $dump])->throw();

            $stream = fopen("{$dump}.gz", 'r');
            Storage::disk('backups')->writeStream(self::DIRECTORY."/{$name}.gz", $stream);
            is_resource($stream) && fclose($stream);
        } finally {
            @unlink($dump);
            @unlink("{$dump}.gz");
        }

        $disk = Storage::disk('backups');
        $expired = BackupRetention::expired($disk->files(self::DIRECTORY), $now);
        $disk->delete($expired);

        $this->info("Backed up {$name}.gz; removed ".count($expired).' old dumps.');

        return self::SUCCESS;
    }
}
