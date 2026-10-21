<?php

namespace App\Domain\School\Actions;

use App\Domain\School\Models\School;
use Illuminate\Support\Facades\DB;

class CreateSchool
{
    public function __construct(private SyncSchoolRoles $syncRoles) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): School
    {
        return DB::transaction(function () use ($data) {
            $school = School::create($data);
            $this->syncRoles->handle($school);

            return $school;
        });
    }
}
