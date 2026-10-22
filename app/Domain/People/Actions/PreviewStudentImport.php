<?php

namespace App\Domain\People\Actions;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\People\Enums\ImportStatus;
use App\Domain\People\Import\StudentSheet;
use App\Domain\People\Models\StudentImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class PreviewStudentImport
{
    /** Reads the first sheet and checks every row. Nothing is saved but the check itself. */
    public function handle(UploadedFile $file, ?User $user): StudentImport
    {
        $cells = Excel::toArray(new class {}, $file)[0] ?? [];

        if (count($cells) - 1 > StudentSheet::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => __('people.import.too_many', ['max' => StudentSheet::MAX_ROWS])]);
        }

        $year = AcademicYear::current();
        $rows = (new StudentSheet($year))->check($cells);

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => __('people.import.empty')]);
        }

        return StudentImport::create([
            'user_id' => $user?->id,
            'academic_year_id' => $year?->id,
            'original_name' => $file->getClientOriginalName(),
            'status' => ImportStatus::Previewed,
            'rows' => $rows,
            'row_count' => count($rows),
            'error_count' => count(array_filter($rows, fn (array $row) => $row['errors'] !== [])),
        ]);
    }
}
