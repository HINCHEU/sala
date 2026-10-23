<?php

namespace App\Domain\Teaching\Actions;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Enums\TeacherStatus;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Models\School;
use App\Domain\Teaching\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;

class CopyTeachingAssignments
{
    public function handle(School $school, AcademicYear $targetYear, AcademicYear $sourceYear): int
    {
        return DB::transaction(function () use ($school, $targetYear, $sourceYear) {
            $targetClassrooms = Classroom::where('school_id', $school->id)
                ->where('academic_year_id', $targetYear->id)
                ->with('gradeLevel')
                ->get();

            $sourceClassrooms = Classroom::where('school_id', $school->id)
                ->where('academic_year_id', $sourceYear->id)
                ->with(['gradeLevel', 'teachingAssignments'])
                ->get();

            $activeTeacherIds = Teacher::where('school_id', $school->id)
                ->where('status', TeacherStatus::Active)
                ->pluck('id')
                ->flip();

            $copiedCount = 0;

            foreach ($targetClassrooms as $targetClassroom) {
                $sourceMatch = $sourceClassrooms->first(function (Classroom $sc) use ($targetClassroom) {
                    return $sc->name === $targetClassroom->name
                        && $sc->grade_level_id === $targetClassroom->grade_level_id;
                });

                if (! $sourceMatch) {
                    continue;
                }

                foreach ($sourceMatch->teachingAssignments as $sourceAssignment) {
                    if (! isset($activeTeacherIds[$sourceAssignment->teacher_id])) {
                        continue;
                    }

                    TeachingAssignment::updateOrCreate(
                        [
                            'classroom_id' => $targetClassroom->id,
                            'subject_id' => $sourceAssignment->subject_id,
                        ],
                        [
                            'school_id' => $school->id,
                            'academic_year_id' => $targetYear->id,
                            'teacher_id' => $sourceAssignment->teacher_id,
                            'hours_per_week' => $sourceAssignment->hours_per_week,
                        ]
                    );

                    $copiedCount++;
                }
            }

            activity()
                ->performedOn($targetYear)
                ->causedBy(auth()->user())
                ->withProperties([
                    'source_year_id' => $sourceYear->id,
                    'target_year_id' => $targetYear->id,
                    'copied' => $copiedCount,
                ])
                ->log('teaching.copied');

            return $copiedCount;
        });
    }
}
