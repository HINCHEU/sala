<?php

namespace App\Domain\People\Models;

use App\Domain\People\Enums\ImportStatus;
use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * An uploaded student list: checked at upload (each row with its errors), then imported by a queued job.
 *
 * @property ImportStatus $status
 * @property list<array{line: int, data: array<string, mixed>, errors: list<string>}> $rows
 */
class StudentImport extends Model
{
    use BelongsToSchool;

    protected $fillable = ['user_id', 'academic_year_id', 'original_name', 'status', 'rows', 'row_count', 'error_count', 'imported_count', 'failure'];

    protected function casts(): array
    {
        return ['status' => ImportStatus::class, 'rows' => 'array'];
    }
}
