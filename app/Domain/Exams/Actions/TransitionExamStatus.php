<?php

namespace App\Domain\Exams\Actions;

use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Results\Jobs\CalculateExamResults;
use App\Domain\School\Models\School;
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

        if ($targetStatus === ExamStatus::Approved) {
            $pending = $exam->subjectStatuses()->where('status', '!=', SubjectExamStatus::Approved)->count();
            if ($pending > 0) {
                throw ValidationException::withMessages([
                    'status' => __('exams.errors.subjects_not_approved', ['count' => $pending]),
                ]);
            }
        }

        if ($targetStatus === ExamStatus::Locked) {
            $this->assertCalculable($exam);
        }
        if ($targetStatus === ExamStatus::Published && $exam->results_version === 0) {
            throw ValidationException::withMessages(['status' => __('exams.errors.results_not_ready')]);
        }

        return DB::transaction(function () use ($exam, $targetStatus) {
            $oldStatus = $exam->status;

            if ($targetStatus === ExamStatus::Locked) {
                // After commit, so the job never reads the exam before it is locked.
                CalculateExamResults::dispatch($exam->school_id, $exam->id)->afterCommit();
            }

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

    /** Locking seals the scores, so first make sure every class can be calculated: a curriculum, and a divisor if needed. */
    private function assertCalculable(Exam $exam): void
    {
        $needsDivisor = School::findOrFail($exam->school_id)->settings->averageMethod === 'divisor';
        $noCurriculum = $noDivisor = [];

        foreach ($exam->classrooms()->get() as $classroom) {
            if (CurriculumSubject::forClassroom($classroom)->isEmpty()) {
                $noCurriculum[] = $classroom->name;
            } elseif ($needsDivisor && ! GradeDivisor::where('academic_year_id', $classroom->academic_year_id)
                ->where('grade_level_id', $classroom->grade_level_id)->where('track_id', $classroom->track_id)->exists()) {
                $noDivisor[] = $classroom->name;
            }
        }

        $errors = array_filter([
            $noCurriculum ? __('exams.errors.no_curriculum', ['classes' => implode(', ', $noCurriculum)]) : null,
            $noDivisor ? __('exams.errors.no_divisor', ['classes' => implode(', ', $noDivisor)]) : null,
        ]);
        if ($errors) {
            throw ValidationException::withMessages(['status' => implode(' ', $errors)]);
        }
    }
}
