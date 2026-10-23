<?php

namespace App\Domain\Exams\Actions;

use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\ExamType;
use App\Domain\Exams\Models\Exam;
use App\Domain\School\Models\School;
use Illuminate\Support\Facades\DB;

class CreateExam
{
    /**
     * @param  array{academic_year_id: int, term_id?: ?int, name: string, type: string, month?: ?int, start_date: string, end_date: string, description?: ?string}  $data
     * @param  list<int>  $classroomIds
     */
    public function handle(School $school, array $data, array $classroomIds): Exam
    {
        return DB::transaction(function () use ($school, $data, $classroomIds) {
            $exam = Exam::create([
                'school_id' => $school->id,
                'academic_year_id' => (int) $data['academic_year_id'],
                'term_id' => ! empty($data['term_id']) ? (int) $data['term_id'] : null,
                'name' => $data['name'],
                'type' => ExamType::from($data['type']),
                'month' => ! empty($data['month']) ? (int) $data['month'] : null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => ExamStatus::Draft,
                'description' => $data['description'] ?? null,
            ]);

            if (! empty($classroomIds)) {
                $exam->syncClassrooms($classroomIds);
            }

            activity()
                ->performedOn($exam)
                ->causedBy(auth()->user())
                ->withProperties(['name' => $exam->name, 'type' => $exam->type->value])
                ->log('exam.created');

            return $exam;
        });
    }
}
