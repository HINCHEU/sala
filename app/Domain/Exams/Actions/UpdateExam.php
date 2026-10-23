<?php

namespace App\Domain\Exams\Actions;

use App\Domain\Exams\Enums\ExamType;
use App\Domain\Exams\Models\Exam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateExam
{
    /**
     * @param  array{academic_year_id?: int, term_id?: ?int, name: string, type: string, month?: ?int, start_date: string, end_date: string, description?: ?string}  $data
     * @param  list<int>  $classroomIds
     */
    public function handle(Exam $exam, array $data, array $classroomIds): Exam
    {
        if ($exam->isLocked()) {
            throw ValidationException::withMessages([
                'status' => __('exams.errors.locked_cannot_be_edited'),
            ]);
        }

        return DB::transaction(function () use ($exam, $data, $classroomIds) {
            $exam->update([
                'term_id' => ! empty($data['term_id']) ? (int) $data['term_id'] : null,
                'name' => $data['name'],
                'type' => ExamType::from($data['type']),
                'month' => ! empty($data['month']) ? (int) $data['month'] : null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'description' => $data['description'] ?? null,
            ]);

            $exam->syncClassrooms($classroomIds);

            activity()
                ->performedOn($exam)
                ->causedBy(auth()->user())
                ->withProperties(['name' => $exam->name])
                ->log('exam.updated');

            return $exam;
        });
    }
}
