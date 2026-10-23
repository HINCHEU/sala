<?php

namespace App\Domain\Teaching\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Teaching\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;

class SaveTeachingAssignments
{
    /**
     * @param  list<array{subject_id: int, teacher_id: ?int, hours_per_week?: ?int}>  $assignments
     */
    public function handle(Classroom $classroom, array $assignments): void
    {
        DB::transaction(function () use ($classroom, $assignments) {
            foreach ($assignments as $item) {
                $subjectId = (int) $item['subject_id'];
                $teacherId = ! empty($item['teacher_id']) ? (int) $item['teacher_id'] : null;
                $hours = isset($item['hours_per_week']) ? (int) $item['hours_per_week'] : 0;

                if ($teacherId === null) {
                    TeachingAssignment::where('classroom_id', $classroom->id)
                        ->where('subject_id', $subjectId)
                        ->delete();
                } else {
                    TeachingAssignment::updateOrCreate(
                        [
                            'classroom_id' => $classroom->id,
                            'subject_id' => $subjectId,
                        ],
                        [
                            'school_id' => $classroom->school_id,
                            'academic_year_id' => $classroom->academic_year_id,
                            'teacher_id' => $teacherId,
                            'hours_per_week' => max(0, $hours),
                        ]
                    );
                }
            }

            activity()
                ->performedOn($classroom)
                ->causedBy(auth()->user())
                ->withProperties(['classroom_id' => $classroom->id, 'count' => count($assignments)])
                ->log('teaching.assigned');
        });
    }
}
