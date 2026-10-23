<?php

namespace App\Domain\Exams\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Teaching\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionExamStatus
{
    public function handle(Exam $exam, ExamStatus $targetStatus): Exam
    {
        if (! $exam->status->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => __('exams.errors.invalid_status_transition', [
                    'from' => $exam->status->label(),
                    'to' => $targetStatus->label(),
                ]),
            ]);
        }

        return DB::transaction(function () use ($exam, $targetStatus) {
            $oldStatus = $exam->status;

            // When opening the exam, initialize subject status records for all participating classrooms
            if ($targetStatus === ExamStatus::Open) {
                $exam->loadMissing('classrooms');

                foreach ($exam->classrooms as $classroom) {
                    $curriculumSubjects = CurriculumSubject::where('academic_year_id', $exam->academic_year_id)
                        ->where('grade_level_id', $classroom->grade_level_id)
                        ->where(function ($q) use ($classroom) {
                            $q->whereNull('track_id');
                            if ($classroom->track_id) {
                                $q->orWhere('track_id', $classroom->track_id);
                            }
                        })
                        ->pluck('subject_id');

                    // If curriculum subjects are not yet defined for this grade/track, also check assigned subjects
                    if ($curriculumSubjects->isEmpty()) {
                        $curriculumSubjects = TeachingAssignment::where('classroom_id', $classroom->id)
                            ->pluck('subject_id');
                    }

                    foreach ($curriculumSubjects as $subjectId) {
                        ExamSubjectStatus::firstOrCreate(
                            [
                                'school_id' => $exam->school_id,
                                'exam_id' => $exam->id,
                                'classroom_id' => $classroom->id,
                                'subject_id' => $subjectId,
                            ],
                            [
                                'status' => SubjectExamStatus::Draft,
                            ]
                        );
                    }
                }
            }

            $exam->update(['status' => $targetStatus]);

            activity()
                ->performedOn($exam)
                ->causedBy(auth()->user())
                ->withProperties([
                    'from' => $oldStatus->value,
                    'to' => $targetStatus->value,
                ])
                ->log('exam.status_changed');

            return $exam;
        });
    }
}
