<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Enums\ImportStatus;
use App\Domain\People\Enums\StudentStatus;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\StudentImport;
use App\Domain\People\RollNumbers;
use App\Domain\People\StudentCodes;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ImportStudents
{
    public function __construct(private LinkGuardian $linkGuardian) {}

    /**
     * Imports the rows that passed the check: student, guardian (reused by phone, so siblings share one)
     * and enrollment. Each row is its own transaction; a row that clashes with data added since the
     * check (a code someone just used) is skipped and its error recorded, and the rest carry on.
     */
    public function handle(StudentImport $import): void
    {
        $rows = $import->rows;
        $codes = (new StudentCodes)->reserve(array_map(fn (array $row) => $row['data']['student_code'], $rows));
        $guardians = []; // phone => Guardian, across rows
        $classrooms = [];
        $imported = 0;

        foreach ($rows as $i => $row) {
            if ($row['errors'] !== []) {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $import, $codes, &$guardians, &$classrooms) {
                    $data = $row['data'];

                    $student = Student::create([
                        'student_code' => $data['student_code'] ?? $codes->next(),
                        'last_name_km' => $data['last_name_km'],
                        'first_name_km' => $data['first_name_km'],
                        'last_name_en' => $data['last_name_en'],
                        'first_name_en' => $data['first_name_en'],
                        'gender' => $data['gender'],
                        'dob' => $data['dob'],
                        'birth_province' => $data['birth_province'],
                        'admitted_at' => now()->toDateString(),
                        'status' => StudentStatus::Active,
                    ]);

                    if ($data['guardian_name'] !== null || $data['guardian_phone'] !== null) {
                        $phone = $data['guardian_phone'];
                        $guardian = ($phone ? ($guardians[$phone] ?? $this->linkGuardian->findByPhone($phone)) : null)
                            ?? Guardian::create(['name_km' => $data['guardian_name'], 'phone' => $phone]);
                        if ($phone) {
                            $guardians[$phone] = $guardian;
                        }

                        $student->guardians()->attach($guardian->id, [
                            'school_id' => $student->school_id,
                            'relationship' => $data['relationship'],
                            'is_primary_contact' => true,
                            'can_pickup' => true,
                        ]);
                    }

                    if ($data['classroom_id'] !== null) {
                        Enrollment::create([
                            'academic_year_id' => $import->academic_year_id,
                            'classroom_id' => $data['classroom_id'],
                            'student_id' => $student->id,
                            'joined_at' => now()->toDateString(),
                        ]);
                        $classrooms[$data['classroom_id']] = true;
                    }
                });
                $imported++;
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $rows[$i]['errors'][] = __('people.import.errors.changed_since_check');
            }
        }

        RollNumbers::renumber(...array_keys($classrooms));

        $import->update([
            'status' => ImportStatus::Done,
            'rows' => $rows,
            'imported_count' => $imported,
            'error_count' => count(array_filter($rows, fn (array $row) => $row['errors'] !== [])),
        ]);

        activity()->performedOn($import)->causedBy($import->user_id ? User::find($import->user_id) : null)
            ->withProperties(['imported' => $imported])
            ->log('students.imported');
    }
}
