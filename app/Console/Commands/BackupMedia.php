<?php

namespace App\Console\Commands;

use App\Support\Files\FileStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Copies school files missing from the backups disk. Stored files never change, so a copy stays good. */
class BackupMedia extends Command
{
    protected $signature = 'sala:backup-media';

    protected $description = 'Copy new school files to the backups disk';

    public function handle(): int
    {
        $backups = Storage::disk('backups');
        $copied = 0;

        foreach ([FileStore::PRIVATE_DISK, FileStore::PUBLIC_DISK] as $name) {
            $source = Storage::disk($name);
            $existing = array_flip($backups->allFiles("media/{$name}"));

            foreach ($source->allFiles() as $path) {
                $target = "media/{$name}/{$path}";
                if (isset($existing[$target])) {
                    continue;
                }
                $stream = $source->readStream($path);
                $backups->writeStream($target, $stream);
                is_resource($stream) && fclose($stream);
                $copied++;
            }
        }

        $this->info("Copied {$copied} files.");

        return self::SUCCESS;
    }
}
