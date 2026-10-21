<?php

namespace App\Console\Commands;

use App\Domain\School\Actions\SyncSchoolRoles;
use App\Domain\School\Models\School;
use Illuminate\Console\Command;

/** Gives every school the roles and permissions in code; run on deploy after a milestone adds permissions. */
class SyncRoles extends Command
{
    protected $signature = 'sala:sync-roles';

    protected $description = 'Create missing roles and sync role permissions for every school';

    public function handle(SyncSchoolRoles $sync): int
    {
        School::query()->each(fn (School $school) => $sync->handle($school));
        $this->info('Roles synced for '.School::count().' schools.');

        return self::SUCCESS;
    }
}
