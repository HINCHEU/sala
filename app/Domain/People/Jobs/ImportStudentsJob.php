<?php

namespace App\Domain\People\Jobs;

use App\Domain\People\Actions\ImportStudents;
use App\Domain\People\Enums\ImportStatus;
use App\Domain\People\Models\StudentImport;
use App\Domain\School\Models\School;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Carries school_id and restores the current school before touching school-owned models. */
class ImportStudentsJob implements ShouldQueue
{
    use Queueable;

    // A retry would import the rows that already went in a second time.
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $schoolId, public int $importId) {}

    public function handle(CurrentSchool $current, ImportStudents $import): void
    {
        $current->runAs(School::findOrFail($this->schoolId), function () use ($import) {
            $import->handle(StudentImport::findOrFail($this->importId));
        });
    }

    public function failed(?Throwable $e): void
    {
        StudentImport::withoutGlobalScope('school')->whereKey($this->importId)
            ->update(['status' => ImportStatus::Failed, 'failure' => $e?->getMessage()]);
    }
}
